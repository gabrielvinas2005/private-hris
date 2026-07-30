<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $procedure = "
        CREATE PROCEDURE [dbo].[SP_ProcessTimeDataAdj]
            @DateToProcess DATE = NULL,
            @PayrollPeriodId INT = NULL,
            @AdjustmentId INT = NULL  -- Optional: process specific adjustment record
        AS
        BEGIN
            SET NOCOUNT ON;

            DECLARE @EmployeeId INT
            DECLARE @WorkScheduleId INT
            DECLARE @DayOfWeek INT
            DECLARE @ScheduledAmIn TIME
            DECLARE @ScheduledPmOut TIME
            DECLARE @ScheduledWorkHours DECIMAL(8, 4)
            DECLARE @FlexiHours DECIMAL(10, 2)
            DECLARE @GracePeriod DECIMAL(10, 2)
            DECLARE @IsRestDay BIT
            DECLARE @IsShifting BIT
            DECLARE @ShiftScheduleId INT

            DECLARE @ActualAmIn TIME
            DECLARE @ActualPmOut TIME
            DECLARE @ActualWorkHours DECIMAL(8, 4)
            DECLARE @IsWfh BIT

            DECLARE @CalculatedLate DECIMAL(10, 3)
            DECLARE @CalculatedUndertime DECIMAL(10, 3)
            DECLARE @CalculatedAbsent DECIMAL(10, 2)
            DECLARE @ProcessedCount INT = 0
            DECLARE @StartTime DATETIME = DATEADD(HOUR, 8, GETUTCDATE())

            DECLARE @AttendanceStartDate DATE
            DECLARE @AttendanceEndDate DATE
            DECLARE @SelectedPeriodId INT

            DECLARE @FlexiLatestTimeIn TIME
            DECLARE @FlexiLatestTimeOut TIME
            DECLARE @CountedTimeIn TIME
            DECLARE @CountedTimeOut TIME
            DECLARE @RequiredWorkMinutes INT
            DECLARE @ActualWorkMinutes INT
            DECLARE @RawDeficitMinutes INT
            DECLARE @LateMinutes INT
            DECLARE @UndertimeMinutes INT

            DECLARE @ApprovedOtHours DECIMAL(10, 3)
            DECLARE @OtId INT
            DECLARE @OtDateTimeTo TIME
            DECLARE @ActualOtHours DECIMAL(10, 3)
            DECLARE @OtShortMinutes INT
            DECLARE @OtShortHours DECIMAL(10, 3)

            -- ========== VALIDATE PAYROLL PERIOD & DATE RANGE ==========
            IF @PayrollPeriodId IS NOT NULL
            BEGIN
                SELECT
                    @SelectedPeriodId = id,
                    @AttendanceStartDate = attendance_start_date,
                    @AttendanceEndDate = attendance_end_date
                FROM dbo.payroll_periods
                WHERE id = @PayrollPeriodId

                IF @SelectedPeriodId IS NULL RETURN
                IF @DateToProcess < @AttendanceStartDate OR @DateToProcess > @AttendanceEndDate RETURN
            END
            ELSE
            BEGIN
                SELECT TOP 1
                    @SelectedPeriodId = id,
                    @AttendanceStartDate = attendance_start_date,
                    @AttendanceEndDate = attendance_end_date
                FROM dbo.payroll_periods
                WHERE @DateToProcess BETWEEN attendance_start_date AND attendance_end_date
                  AND active = 1
                ORDER BY id DESC

                IF @SelectedPeriodId IS NULL RETURN
            END

            SET @DayOfWeek = DATEPART(WEEKDAY, @DateToProcess)
            IF @DayOfWeek = 1 SET @DayOfWeek = 7
            IF @DayOfWeek > 1 SET @DayOfWeek = @DayOfWeek - 1

            -- ========== NOTE: STEP 4 (CREATE ASSUMED RECORDS) IS EXCLUDED FOR time_data_adj ==========

            -- ========== STEP 1: CREATE ABSENT RECORDS ==========
            INSERT INTO dbo.time_data_adj (
                employee_id, work_schedule_id, date,
                am_in, pm_out,
                work_hours, late, undertime, absent, is_shifting,
                target_payroll_period_id, adjustment_type, status,
                created_at, updated_at
            )
            SELECT
                e.id, e.work_schedule_id, @DateToProcess,
                NULL, NULL,
                CAST(0.0000 AS DECIMAL(8,4)), 0.00, 0.00, 1.00, 0,
                @SelectedPeriodId, 'MANUAL', 'PENDING',
                GETDATE(), GETDATE()
            FROM dbo.employees e
            INNER JOIN dbo.fix_schedules_details fsd
                ON e.work_schedule_id = fsd.fix_schedule_id AND fsd.day_id = @DayOfWeek
            WHERE fsd.is_restday = 0
              AND e.is_shifting = 0
              AND NOT EXISTS (SELECT 1 FROM dbo.time_data_adj tda WHERE tda.employee_id = e.id AND tda.date = @DateToProcess)
              AND e.id NOT IN (SELECT employee_id FROM dbo.time_data_adj WHERE date = @DateToProcess AND is_wfh = 1)

            INSERT INTO dbo.time_data_adj (
                employee_id, work_schedule_id, date,
                am_in, pm_out,
                work_hours, late, undertime, absent,
                is_shifting, is_restday,
                target_payroll_period_id, adjustment_type, status,
                created_at, updated_at
            )
            SELECT
                e.id, e.work_schedule_id, @DateToProcess,
                NULL, NULL,
                CAST(0.0000 AS DECIMAL(8,4)), 0.00, 0.00, 0.00,
                0, 1,
                @SelectedPeriodId, 'MANUAL', 'PENDING',
                GETDATE(), GETDATE()
            FROM dbo.employees e
            INNER JOIN dbo.fix_schedules_details fsd
                ON e.work_schedule_id = fsd.fix_schedule_id AND fsd.day_id = @DayOfWeek
            WHERE fsd.is_restday = 1
              AND e.is_shifting = 0
              AND NOT EXISTS (SELECT 1 FROM dbo.time_data_adj tda WHERE tda.employee_id = e.id AND tda.date = @DateToProcess)

            INSERT INTO dbo.time_data_adj (
                employee_id, work_schedule_id, date,
                am_in, pm_out,
                work_hours, late, undertime, absent, is_shifting,
                target_payroll_period_id, adjustment_type, status,
                created_at, updated_at
            )
            SELECT
                e.id, ssd.shift_schedule_id, @DateToProcess,
                NULL, NULL,
                CAST(0.0000 AS DECIMAL(8,4)), 0.00, 0.00, 1.00, 1,
                @SelectedPeriodId, 'MANUAL', 'PENDING',
                GETDATE(), GETDATE()
            FROM dbo.employees e
            INNER JOIN dbo.shift_schedules_details ssd
                ON ssd.shift_schedule_id = e.work_schedule_id AND ssd.shift_date = @DateToProcess
            WHERE e.is_shifting = 1
              AND NOT EXISTS (SELECT 1 FROM dbo.time_data_adj tda WHERE tda.employee_id = e.id AND tda.date = @DateToProcess)
              AND e.id NOT IN (SELECT employee_id FROM dbo.time_data_adj WHERE date = @DateToProcess AND is_wfh = 1)

            -- ========== STEP 1b: HANDLE HOLIDAYS ==========
            UPDATE tda
            SET
                is_holiday = 1,
                holiday_id = h.id,
                absent = 0.00,
                updated_at = GETDATE()
            FROM dbo.time_data_adj tda
            INNER JOIN dbo.holidays h ON tda.date = h.date
            WHERE tda.date = @DateToProcess
              AND tda.absent = 1.00
              AND (tda.is_holiday IS NULL OR tda.is_holiday = 0)
              AND (tda.is_wfh IS NULL OR tda.is_wfh = 0)

            -- ========== STEP 1c: HANDLE LEAVES ==========
            UPDATE tda
            SET
                leave = 1,
                absent = 0.00,
                updated_at = GETDATE()
            FROM dbo.time_data_adj tda
            INNER JOIN WTIHRIS_PTTC.dbo.leave_headers lr
                ON tda.employee_id = lr.employee_id
                AND tda.date >= lr.date_from
                AND tda.date <= lr.date_to
            WHERE tda.date = @DateToProcess
              AND tda.absent = 1.00
              AND (tda.leave IS NULL OR tda.leave = 0)
              AND (lr.approved = 1 AND lr.approved_2 = 1 AND lr.approved_3 = 1)
              AND (tda.is_wfh IS NULL OR tda.is_wfh = 0)

            INSERT INTO dbo.time_data_adj (
                employee_id, work_schedule_id, date,
                am_in, pm_out,
                work_hours, late, undertime, absent, leave, is_shifting,
                target_payroll_period_id, adjustment_type, status,
                created_at, updated_at
            )
            SELECT
                lr.employee_id, e.work_schedule_id, @DateToProcess,
                NULL, NULL,
                CAST(0.0000 AS DECIMAL(8,4)), 0.00, 0.00, 0.00, 1, 0,
                @SelectedPeriodId, 'MANUAL', 'PENDING',
                GETDATE(), GETDATE()
            FROM WTIHRIS_PTTC.dbo.leave_headers lr
            INNER JOIN dbo.employees e ON lr.employee_id = e.id
            WHERE @DateToProcess BETWEEN lr.date_from AND lr.date_to
              AND e.is_shifting = 0
              AND (lr.approved = 1 AND lr.approved_2 = 1 AND lr.approved_3 = 1)
              AND NOT EXISTS (SELECT 1 FROM dbo.time_data_adj tda WHERE tda.employee_id = lr.employee_id AND tda.date = @DateToProcess)

            INSERT INTO dbo.time_data_adj (
                employee_id, work_schedule_id, date,
                am_in, pm_out,
                work_hours, late, undertime, absent, leave, is_shifting,
                target_payroll_period_id, adjustment_type, status,
                created_at, updated_at
            )
            SELECT
                lr.employee_id, ssd.shift_schedule_id, @DateToProcess,
                NULL, NULL,
                CAST(0.0000 AS DECIMAL(8,4)), 0.00, 0.00, 0.00, 1, 1,
                @SelectedPeriodId, 'MANUAL', 'PENDING',
                GETDATE(), GETDATE()
            FROM WTIHRIS_PTTC.dbo.leave_headers lr
            INNER JOIN dbo.employees e ON lr.employee_id = e.id
            INNER JOIN dbo.shift_schedules_details ssd
                ON ssd.shift_schedule_id = e.work_schedule_id AND ssd.shift_date = @DateToProcess
            WHERE @DateToProcess BETWEEN lr.date_from AND lr.date_to
              AND e.is_shifting = 1
              AND (lr.approved = 1 AND lr.approved_2 = 1 AND lr.approved_3 = 1)
              AND NOT EXISTS (SELECT 1 FROM dbo.time_data_adj tda WHERE tda.employee_id = lr.employee_id AND tda.date = @DateToProcess)

            -- ========== STEP 1d: HANDLE OFFICIAL BUSINESS (OB) ==========
            UPDATE tda
            SET
                am_in = CASE WHEN tda.am_in IS NULL THEN CAST(oba.date_time_from AS TIME) ELSE tda.am_in END,
                pm_out = CASE WHEN tda.pm_out IS NULL THEN CAST(oba.date_time_to AS TIME) ELSE tda.pm_out END,
                is_ob = 1,
                ob_id = oba.id,
                absent = 0.00,
                updated_at = GETDATE()
            FROM dbo.time_data_adj tda
            INNER JOIN dbo.official_business_applications oba
                ON tda.employee_id = oba.employee_id
                AND tda.date >= CAST(oba.date_time_from AS DATE)
                AND tda.date <= CAST(oba.date_time_to AS DATE)
            INNER JOIN dbo.employees e ON oba.employee_id = e.id
            WHERE tda.date = @DateToProcess
              AND e.is_shifting = 0
              AND (oba.approved = 1 AND oba.approved_2 = 1 AND oba.approved_3 = 1)
              AND (tda.is_ob IS NULL OR tda.is_ob = 0)
              AND (tda.is_wfh IS NULL OR tda.is_wfh = 0)

            UPDATE tda
            SET
                am_in = CASE WHEN tda.am_in IS NULL THEN CAST(oba.date_time_from AS TIME) ELSE tda.am_in END,
                pm_out = CASE WHEN tda.pm_out IS NULL THEN CAST(oba.date_time_to AS TIME) ELSE tda.pm_out END,
                is_ob = 1,
                ob_id = oba.id,
                absent = 0.00,
                updated_at = GETDATE()
            FROM dbo.time_data_adj tda
            INNER JOIN dbo.official_business_applications oba
                ON tda.employee_id = oba.employee_id
                AND tda.date >= CAST(oba.date_time_from AS DATE)
                AND tda.date <= CAST(oba.date_time_to AS DATE)
            INNER JOIN dbo.employees e ON oba.employee_id = e.id
            WHERE tda.date = @DateToProcess
              AND e.is_shifting = 1
              AND (oba.approved = 1 AND oba.approved_2 = 1 AND oba.approved_3 = 1)
              AND (tda.is_ob IS NULL OR tda.is_ob = 0)
              AND (tda.is_wfh IS NULL OR tda.is_wfh = 0)

            INSERT INTO dbo.time_data_adj (
                employee_id, work_schedule_id, date,
                am_in, pm_out,
                work_hours, late, undertime, absent, is_ob, ob_id, is_shifting,
                target_payroll_period_id, adjustment_type, status,
                created_at, updated_at
            )
            SELECT
                oba.employee_id, e.work_schedule_id, CAST(oba.date_time_from AS DATE),
                CAST(oba.date_time_from AS TIME), CAST(oba.date_time_to AS TIME),
                CAST(8.0000 AS DECIMAL(8,4)), 0.00, 0.00, 0.00, 1, oba.id, 0,
                @SelectedPeriodId, 'MANUAL', 'PENDING',
                GETDATE(), GETDATE()
            FROM dbo.official_business_applications oba
            INNER JOIN dbo.employees e ON oba.employee_id = e.id
            WHERE CAST(oba.date_time_from AS DATE) = @DateToProcess
              AND e.is_shifting = 0
              AND (oba.approved = 1 AND oba.approved_2 = 1 AND oba.approved_3 = 1)
              AND NOT EXISTS (SELECT 1 FROM dbo.time_data_adj tda WHERE tda.employee_id = oba.employee_id AND tda.date = CAST(oba.date_time_from AS DATE))

            INSERT INTO dbo.time_data_adj (
                employee_id, work_schedule_id, date,
                am_in, pm_out,
                work_hours, late, undertime, absent, is_ob, ob_id, is_shifting,
                target_payroll_period_id, adjustment_type, status,
                created_at, updated_at
            )
            SELECT
                oba.employee_id, ssd.shift_schedule_id, CAST(oba.date_time_from AS DATE),
                CAST(oba.date_time_from AS TIME), CAST(oba.date_time_to AS TIME),
                CAST(8.0000 AS DECIMAL(8,4)), 0.00, 0.00, 0.00, 1, oba.id, 1,
                @SelectedPeriodId, 'MANUAL', 'PENDING',
                GETDATE(), GETDATE()
            FROM dbo.official_business_applications oba
            INNER JOIN dbo.employees e ON oba.employee_id = e.id
            INNER JOIN dbo.shift_schedules_details ssd
                ON ssd.shift_schedule_id = e.work_schedule_id AND ssd.shift_date = CAST(oba.date_time_from AS DATE)
            WHERE CAST(oba.date_time_from AS DATE) = @DateToProcess
              AND e.is_shifting = 1
              AND (oba.approved = 1 AND oba.approved_2 = 1 AND oba.approved_3 = 1)
              AND NOT EXISTS (SELECT 1 FROM dbo.time_data_adj tda WHERE tda.employee_id = oba.employee_id AND tda.date = CAST(oba.date_time_from AS DATE))

            -- ========== STEP 1e: HANDLE WORK CANCELLATIONS ==========
            ;WITH CancelledWork AS (
                SELECT tda.id, tda.employee_id, tda.date
                FROM dbo.time_data_adj tda
                INNER JOIN dbo.employees e ON tda.employee_id = e.id
                LEFT JOIN dbo.fix_schedules_details fsd
                    ON e.work_schedule_id = fsd.fix_schedule_id AND fsd.day_id = @DayOfWeek AND e.is_shifting = 0
                INNER JOIN dbo.work_cancellations wc ON tda.date BETWEEN wc.date_from AND wc.date_to
                WHERE tda.date = @DateToProcess
                  AND tda.absent = 1.00
                  AND tda.am_in IS NULL
                  AND (tda.is_wfh IS NULL OR tda.is_wfh = 0)
                  AND (tda.leave IS NULL OR tda.leave = 0)
                  AND (tda.is_holiday IS NULL OR tda.is_holiday = 0)
                  AND (tda.is_ob IS NULL OR tda.is_ob = 0)
                  AND (e.is_shifting = 1 OR (fsd.is_restday IS NULL OR fsd.is_restday = 0))
            )
            UPDATE tda
            SET
                absent = 1.00,
                work_hours = CAST(0.0000 AS DECIMAL(8,4)),
                late = 0.00,
                undertime = 0.00,
                updated_at = GETDATE()
            FROM dbo.time_data_adj tda
            INNER JOIN CancelledWork cw ON tda.id = cw.id

            ;WITH LeaveOnCancelledWork AS (
                SELECT tda.id, tda.employee_id
                FROM dbo.time_data_adj tda
                INNER JOIN dbo.employees e ON tda.employee_id = e.id
                LEFT JOIN dbo.fix_schedules_details fsd
                    ON e.work_schedule_id = fsd.fix_schedule_id AND fsd.day_id = @DayOfWeek AND e.is_shifting = 0
                INNER JOIN dbo.work_cancellations wc ON tda.date BETWEEN wc.date_from AND wc.date_to
                INNER JOIN WTIHRIS_PTTC.dbo.leave_headers lr
                    ON lr.employee_id = tda.employee_id
                    AND tda.date >= lr.date_from AND tda.date <= lr.date_to
                    AND lr.leave_type_id = 1
                    AND (lr.approved = 1 AND lr.approved_2 = 1 AND lr.approved_3 = 1)
                WHERE tda.date = @DateToProcess
                  AND tda.absent = 1.00
                  AND (tda.leave IS NULL OR tda.leave = 0)
                  AND (tda.is_wfh IS NULL OR tda.is_wfh = 0)
                  AND (tda.is_holiday IS NULL OR tda.is_holiday = 0)
                  AND (tda.is_ob IS NULL OR tda.is_ob = 0)
                  AND (e.is_shifting = 1 OR (fsd.is_restday IS NULL OR fsd.is_restday = 0))
            )
            UPDATE tda
            SET
                absent = 0.00,
                leave = 1,
                work_hours = CAST(0.0000 AS DECIMAL(8,4)),
                late = 0.00,
                undertime = 0.00,
                updated_at = GETDATE()
            FROM dbo.time_data_adj tda
            INNER JOIN LeaveOnCancelledWork lcw ON tda.id = lcw.id

            -- ========== STEP 2: PROCESS ALL RECORDS (exclude is_assumed = 1) ==========
            DECLARE process_cursor CURSOR LOCAL FAST_FORWARD FOR
                SELECT DISTINCT tda.employee_id, tda.work_schedule_id, ISNULL(tda.is_wfh, 0), ISNULL(tda.is_shifting, 0)
                FROM dbo.time_data_adj tda
                WHERE tda.date = @DateToProcess
                  AND (tda.applied_offset IS NULL OR tda.applied_offset = 0)
                  AND (tda.leave IS NULL OR tda.leave = 0)
                  AND (tda.is_holiday IS NULL OR tda.is_holiday = 0)
                  AND (tda.is_ob IS NULL OR tda.is_ob = 0)
                  AND (tda.is_assumed IS NULL OR tda.is_assumed = 0)
                  AND (
                      tda.work_hours IS NULL
                      OR tda.late IS NULL
                      OR tda.undertime IS NULL
                      OR tda.is_edited = 1
                      OR tda.is_wfh = 1
                      OR tda.is_restday = 1
                  )
                  AND (@AdjustmentId IS NULL OR tda.id = @AdjustmentId)
                ORDER BY tda.employee_id

            OPEN process_cursor
            FETCH NEXT FROM process_cursor INTO @EmployeeId, @WorkScheduleId, @IsWfh, @IsShifting

            WHILE @@FETCH_STATUS = 0
            BEGIN
                SET @CalculatedLate = 0.00
                SET @CalculatedUndertime = 0.00
                SET @CalculatedAbsent = 0.00

                IF @IsShifting = 1
                BEGIN
                    SELECT
                        @ScheduledAmIn = am_in,
                        @ScheduledPmOut = pm_out,
                        @ScheduledWorkHours = CAST(work_hours AS DECIMAL(8,4)),
                        @FlexiHours = ISNULL(flexi_hours, 0),
                        @GracePeriod = ISNULL(grace_period, 0),
                        @ShiftScheduleId = shift_schedule_id,
                        @IsRestDay = 0
                    FROM dbo.shift_schedules_details
                    WHERE shift_date = @DateToProcess
                END
                ELSE
                BEGIN
                    SELECT
                        @ScheduledAmIn = am_in,
                        @ScheduledPmOut = pm_out,
                        @ScheduledWorkHours = CAST(work_hours AS DECIMAL(8,4)),
                        @FlexiHours = ISNULL(flexi_hours, 0),
                        @GracePeriod = ISNULL(grace_period, 0),
                        @IsRestDay = ISNULL(is_restday, 0)
                    FROM dbo.fix_schedules_details
                    WHERE fix_schedule_id = @WorkScheduleId AND day_id = @DayOfWeek
                END

                IF @IsRestDay = 1
                BEGIN
                    IF EXISTS (SELECT 1 FROM dbo.overtime_applications oa WHERE oa.employee_id = @EmployeeId AND oa.date = @DateToProcess AND (oa.approved = 1 AND oa.approved_2 = 1 AND oa.approved_3 = 1))
                    BEGIN
                        SELECT TOP 1 @ApprovedOtHours = oa.total_hours, @OtId = oa.id, @OtDateTimeTo = CAST(oa.date_time_to AS TIME)
                        FROM dbo.overtime_applications oa
                        WHERE oa.employee_id = @EmployeeId AND oa.date = @DateToProcess AND (oa.approved = 1 AND oa.approved_2 = 1 AND oa.approved_3 = 1)
                        SET @ActualOtHours = @ApprovedOtHours
                        SELECT TOP 1 @ActualAmIn = am_in, @ActualPmOut = pm_out FROM dbo.time_data_adj WHERE employee_id = @EmployeeId AND date = @DateToProcess
                        IF @ActualPmOut IS NOT NULL AND @OtDateTimeTo IS NOT NULL AND @ActualPmOut < @OtDateTimeTo
                        BEGIN
                            SET @OtShortMinutes = DATEDIFF(MINUTE, @ActualPmOut, @OtDateTimeTo)
                            SET @OtShortHours = dbo.fn_MinutesToDayFraction(@OtShortMinutes)
                            SET @ActualOtHours = @ApprovedOtHours - @OtShortHours
                            IF @ActualOtHours < 0 SET @ActualOtHours = 0
                        END
                        ELSE IF @ActualPmOut IS NULL SET @ActualOtHours = 0
                        UPDATE dbo.time_data_adj SET ot_hours = @ActualOtHours, ot_id = @OtId, is_ot = 1, is_restday = 1, updated_at = GETDATE()
                        WHERE employee_id = @EmployeeId AND date = @DateToProcess
                    END
                    FETCH NEXT FROM process_cursor INTO @EmployeeId, @WorkScheduleId, @IsWfh, @IsShifting
                    CONTINUE
                END

                IF @ScheduledAmIn IS NULL
                BEGIN
                    FETCH NEXT FROM process_cursor INTO @EmployeeId, @WorkScheduleId, @IsWfh, @IsShifting
                    CONTINUE
                END

                SELECT TOP 1 @ActualAmIn = am_in, @ActualPmOut = pm_out, @IsWfh = ISNULL(is_wfh, 0)
                FROM dbo.time_data_adj
                WHERE employee_id = @EmployeeId AND date = @DateToProcess
                ORDER BY CASE WHEN is_edited = 1 THEN 1 ELSE 0 END DESC, updated_at DESC, id DESC

                IF EXISTS (SELECT 1 FROM dbo.overtime_applications oa WHERE oa.employee_id = @EmployeeId AND oa.date = @DateToProcess AND (oa.approved = 1 AND oa.approved_2 = 1 AND oa.approved_3 = 1))
                    UPDATE dbo.time_data_adj SET is_ot = 1 WHERE employee_id = @EmployeeId AND date = @DateToProcess

                IF @IsRestDay = 1
                BEGIN
                    IF EXISTS (SELECT 1 FROM dbo.overtime_applications oa WHERE oa.employee_id = @EmployeeId AND oa.date = @DateToProcess AND (oa.approved = 1 AND oa.approved_2 = 1 AND oa.approved_3 = 1))
                    BEGIN
                        SELECT TOP 1 @ApprovedOtHours = oa.total_hours, @OtId = oa.id, @OtDateTimeTo = CAST(oa.date_time_to AS TIME)
                        FROM dbo.overtime_applications oa
                        WHERE oa.employee_id = @EmployeeId AND oa.date = @DateToProcess AND (oa.approved = 1 AND oa.approved_2 = 1 AND oa.approved_3 = 1)
                        SET @ActualOtHours = @ApprovedOtHours
                        SELECT TOP 1 @ActualAmIn = am_in, @ActualPmOut = pm_out FROM dbo.time_data_adj WHERE employee_id = @EmployeeId AND date = @DateToProcess
                        IF @ActualPmOut IS NOT NULL AND @OtDateTimeTo IS NOT NULL AND @ActualPmOut < @OtDateTimeTo
                        BEGIN
                            SET @OtShortMinutes = DATEDIFF(MINUTE, @ActualPmOut, @OtDateTimeTo)
                            SET @OtShortHours = dbo.fn_MinutesToDayFraction(@OtShortMinutes)
                            SET @ActualOtHours = @ApprovedOtHours - @OtShortHours
                            IF @ActualOtHours < 0 SET @ActualOtHours = 0
                        END
                        ELSE IF @ActualPmOut IS NULL SET @ActualOtHours = 0
                        UPDATE dbo.time_data_adj SET ot_hours = @ActualOtHours, ot_id = @OtId, is_ot = 1, is_restday = 1, updated_at = GETDATE()
                        WHERE employee_id = @EmployeeId AND date = @DateToProcess
                    END
                    FETCH NEXT FROM process_cursor INTO @EmployeeId, @WorkScheduleId, @IsWfh, @IsShifting
                    CONTINUE
                END
                ELSE
                BEGIN
                    IF @ActualAmIn IS NULL OR @ActualPmOut IS NULL
                    BEGIN
                        SET @ActualWorkHours = CAST(0.0000 AS DECIMAL(8,4))
                        SET @CalculatedAbsent = 1.00
                    END
                    ELSE
                    BEGIN
                        IF @FlexiHours > 0
                        BEGIN
                            DECLARE @FlexiMinutes INT = CAST(@FlexiHours * 60 AS INT)
                            DECLARE @GracePeriodMinutes INT = CAST(@GracePeriod AS INT)
                            DECLARE @ActualLateMinutes INT = 0
                            DECLARE @AdjustedExpectedPmOut TIME
                            SET @FlexiLatestTimeIn = DATEADD(MINUTE, @GracePeriodMinutes, DATEADD(MINUTE, @FlexiMinutes, @ScheduledAmIn))
                            SET @FlexiLatestTimeOut = DATEADD(MINUTE, @FlexiMinutes, @ScheduledPmOut)
                            IF @ActualAmIn > @ScheduledAmIn BEGIN SET @ActualLateMinutes = DATEDIFF(MINUTE, @ScheduledAmIn, @ActualAmIn); IF @ActualLateMinutes < 0 SET @ActualLateMinutes = 0 END
                            SET @AdjustedExpectedPmOut = DATEADD(MINUTE, @ActualLateMinutes, @ScheduledPmOut)
                            IF @AdjustedExpectedPmOut > @FlexiLatestTimeOut SET @AdjustedExpectedPmOut = @FlexiLatestTimeOut
                            SET @CountedTimeIn = @ActualAmIn
                            SET @CountedTimeOut = @ActualPmOut
                            IF @ActualPmOut > @AdjustedExpectedPmOut SET @CountedTimeOut = @AdjustedExpectedPmOut
                            SET @RequiredWorkMinutes = CAST(@ScheduledWorkHours * 60 AS INT)
                            SET @ActualWorkMinutes = DATEDIFF(MINUTE, @CountedTimeIn, @CountedTimeOut)
                            IF @ActualWorkMinutes >= 60 SET @ActualWorkMinutes = @ActualWorkMinutes - 60 ELSE SET @ActualWorkMinutes = 0
                            IF @ActualWorkMinutes > @RequiredWorkMinutes SET @ActualWorkMinutes = @RequiredWorkMinutes
                            IF @ActualWorkMinutes < 0 SET @ActualWorkMinutes = 0
                            SET @ActualWorkHours = dbo.fn_MinutesToDayFraction(@ActualWorkMinutes)
                            SET @RawDeficitMinutes = @RequiredWorkMinutes - @ActualWorkMinutes
                            IF @RawDeficitMinutes < 0 SET @RawDeficitMinutes = 0
                            SET @LateMinutes = 0
                            IF @CountedTimeIn > @FlexiLatestTimeIn BEGIN SET @LateMinutes = DATEDIFF(MINUTE, @FlexiLatestTimeIn, @CountedTimeIn); IF @LateMinutes < 0 SET @LateMinutes = 0 END
                            SET @CalculatedLate = dbo.fn_MinutesToDayFraction(@LateMinutes)
                            SET @UndertimeMinutes = 0
                            IF @ActualPmOut < @AdjustedExpectedPmOut BEGIN SET @UndertimeMinutes = DATEDIFF(MINUTE, @ActualPmOut, @AdjustedExpectedPmOut); IF @UndertimeMinutes < 0 SET @UndertimeMinutes = 0 END
                            SET @CalculatedUndertime = dbo.fn_MinutesToDayFraction(@UndertimeMinutes)
                        END
                        ELSE
                        BEGIN
                            SET @RequiredWorkMinutes = CAST(@ScheduledWorkHours * 60 AS INT)
                            DECLARE @WorkCalcPmOut TIME
                            SET @WorkCalcPmOut = @ActualPmOut
                            IF @ActualPmOut > @ScheduledPmOut SET @WorkCalcPmOut = @ScheduledPmOut
                            SET @ActualWorkMinutes = DATEDIFF(MINUTE, @ActualAmIn, @WorkCalcPmOut)
                            IF @ActualWorkMinutes >= 60 SET @ActualWorkMinutes = @ActualWorkMinutes - 60 ELSE SET @ActualWorkMinutes = 0
                            IF @ActualWorkMinutes > @RequiredWorkMinutes SET @ActualWorkMinutes = @RequiredWorkMinutes
                            IF @ActualWorkMinutes < 0 SET @ActualWorkMinutes = 0
                            SET @ActualWorkHours = dbo.fn_MinutesToDayFraction(@ActualWorkMinutes)
                            SET @RawDeficitMinutes = @RequiredWorkMinutes - @ActualWorkMinutes
                            IF @RawDeficitMinutes < 0 SET @RawDeficitMinutes = 0
                            DECLARE @AmInDeadline TIME
                            SET @LateMinutes = 0
                            IF @ActualAmIn IS NOT NULL AND @ScheduledAmIn IS NOT NULL
                            BEGIN
                                SET @AmInDeadline = DATEADD(MINUTE, @GracePeriod, @ScheduledAmIn)
                                IF @ActualAmIn > @AmInDeadline BEGIN SET @LateMinutes = DATEDIFF(MINUTE, @AmInDeadline, @ActualAmIn); IF @LateMinutes < 0 SET @LateMinutes = 0 END
                            END
                            SET @CalculatedLate = dbo.fn_MinutesToDayFraction(@LateMinutes)
                            SET @UndertimeMinutes = @RawDeficitMinutes - @LateMinutes
                            IF @UndertimeMinutes < 0 SET @UndertimeMinutes = 0
                            SET @CalculatedUndertime = dbo.fn_MinutesToDayFraction(@UndertimeMinutes)
                        END

                        IF @ScheduledPmOut IS NOT NULL
                        BEGIN
                            SET @ApprovedOtHours = 0; SET @OtId = NULL; SET @OtDateTimeTo = NULL
                            SELECT TOP 1 @ApprovedOtHours = oa.total_hours, @OtId = oa.id, @OtDateTimeTo = CAST(oa.date_time_to AS TIME)
                            FROM dbo.overtime_applications oa
                            WHERE oa.employee_id = @EmployeeId AND oa.date = @DateToProcess AND (oa.approved = 1 OR oa.approved_2 = 1 OR oa.approved_3 = 1)
                            IF @ApprovedOtHours > 0
                            BEGIN
                                SET @ActualOtHours = @ApprovedOtHours
                                IF @ActualPmOut IS NOT NULL AND @ActualPmOut > @ScheduledPmOut AND @ActualPmOut < @OtDateTimeTo
                                BEGIN
                                    SET @OtShortMinutes = DATEDIFF(MINUTE, @ActualPmOut, @OtDateTimeTo)
                                    SET @OtShortHours = dbo.fn_MinutesToDayFraction(@OtShortMinutes)
                                    SET @ActualOtHours = @ApprovedOtHours - @OtShortHours
                                    IF @ActualOtHours < 0 SET @ActualOtHours = 0
                                END
                                ELSE SET @ActualOtHours = 0
                                UPDATE dbo.time_data_adj SET ot_hours = @ActualOtHours, ot_id = @OtId, updated_at = GETDATE()
                                WHERE employee_id = @EmployeeId AND date = @DateToProcess
                            END
                        END
                    END
                END

                IF @IsShifting = 1
                    UPDATE dbo.time_data_adj SET work_schedule_id = @ShiftScheduleId, is_shifting = 1, work_hours = @ActualWorkHours, late = @CalculatedLate, undertime = @CalculatedUndertime, absent = @CalculatedAbsent, is_restday = @IsRestDay, updated_at = GETDATE()
                    WHERE employee_id = @EmployeeId AND date = @DateToProcess
                ELSE
                    UPDATE dbo.time_data_adj SET work_hours = @ActualWorkHours, late = @CalculatedLate, undertime = @CalculatedUndertime, absent = @CalculatedAbsent, is_restday = @IsRestDay, updated_at = GETDATE()
                    WHERE employee_id = @EmployeeId AND date = @DateToProcess

                UPDATE dbo.time_data_adj SET updated_at = GETDATE()
                WHERE employee_id = @EmployeeId AND date = @DateToProcess AND is_edited = 1

                SET @ProcessedCount = @ProcessedCount + 1
                FETCH NEXT FROM process_cursor INTO @EmployeeId, @WorkScheduleId, @IsWfh, @IsShifting
            END

            CLOSE process_cursor
            DEALLOCATE process_cursor

            -- ========== STEP 3: ASSIGN PAYROLL PERIOD ==========
            UPDATE dbo.time_data_adj
            SET target_payroll_period_id = @SelectedPeriodId, updated_at = GETDATE()
            WHERE date = @DateToProcess AND (target_payroll_period_id IS NULL OR target_payroll_period_id != @SelectedPeriodId)
        END
        ";

        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS [dbo].[SP_ProcessTimeDataAdj]");
    }
};
