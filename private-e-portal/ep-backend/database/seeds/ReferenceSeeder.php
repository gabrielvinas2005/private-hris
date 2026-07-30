<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::unprepared("
        SET IDENTITY_INSERT [dbo].[branches] ON 

        INSERT [dbo].[branches] ([id], [name], [created_at], [updated_at], [is_main_branch], [branch_head_id], [code]) VALUES (1, N'CAR', CAST(N'2025-03-11T10:04:22.380' AS DateTime), CAST(N'2025-03-11T10:04:22.380' AS DateTime), 0, 0, NULL)

        SET IDENTITY_INSERT [dbo].[branches] OFF

        SET IDENTITY_INSERT [dbo].[departments] ON 

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (1, N'MUNICIPAL ACCOUNTING OFFICE', N'MUNICIPAL ACCOUNTING OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (2, N'MUNICIPAL AGRICULTURE OFFICE', N'MUNICIPAL AGRICULTURE OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (3, N'MUNICIPAL ASSESSOR''S OFFICE', N'MUNICIPAL ASSESSOR''S OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (4, N'MUNICIPAL BUDGET OFFICE', N'MUNICIPAL BUDGET OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (5, N'MUNICIPAL CIVIL REGISTRAR''S OFFICE', N'MUNICIPAL CIVIL REGISTRAR''S OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (6, N'MUNICIPAL ENGINEERING OFFICE', N'MUNICIPAL ENGINEERING OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (7, N'MUNICIPAL ENGINEERING OFFIVE', N'MUNICIPAL ENGINEERING OFFIVE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (8, N'MUNICIPAL ENVIRONMENTAL  AND NATURAL RESOURCES OFFICE', N'MUNICIPAL ENVIRONMENTAL  AND NATURAL RESOURCES OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (9, N'MUNICIPAL ENVIRONMENTAL AND NATURAL RESOURCES OFFICE', N'MUNICIPAL ENVIRONMENTAL AND NATURAL RESOURCES OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (10, N'MUNICIPAL HEALRH SERVICES OFFICE', N'MUNICIPAL HEALRH SERVICES OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (11, N'MUNICIPAL HEALTH SERVICES OFFICE', N'MUNICIPAL HEALTH SERVICES OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (12, N'MUNICIPAL MAYOR''S OFFICE', N'MUNICIPAL MAYOR''S OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (13, N'MUNICIPAL PLANNING AND DEVELOOPMENT OFFICE', N'MUNICIPAL PLANNING AND DEVELOOPMENT OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (14, N'MUNICIPAL PLANNING AND DEVELOPMENT OFFICE', N'MUNICIPAL PLANNING AND DEVELOPMENT OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (15, N'MUNICIPAL PLANNING AND DEVELOPMENT OFFICER', N'MUNICIPAL PLANNING AND DEVELOPMENT OFFICER', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (16, N'MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE', N'MUNICIPAL SOCIAL WELFARE AND DEVELOPMENT OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (17, N'MUNICIPAL TREASURY OFFICE', N'MUNICIPAL TREASURY OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (18, N'OFFICE OF THE MUNICIPAL VICE MAYOR', N'OFFICE OF THE MUNICIPAL VICE MAYOR', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (19, N'OFFICE OF THE SECRETARY OF THE SANGGUNIAN', N'OFFICE OF THE SECRETARY OF THE SANGGUNIAN', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (20, N'SANGGUNIANG BAYAN', N'SANGGUNIANG BAYAN', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (21, N'SANGGUNIANG BAYAN OFFICE', N'SANGGUNIANG BAYAN OFFICE', NULL, 0, CAST(N'2025-03-11T10:06:21.317' AS DateTime), CAST(N'2025-03-11T10:06:21.317' AS DateTime), 1, 0, 1)

        SET IDENTITY_INSERT [dbo].[departments] OFF

        SET IDENTITY_INSERT [dbo].[blood_types] ON 

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (1, N'A', N'A', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (2, N'A+', N'A+', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (3, N'AB', N'AB', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (4, N'AB+', N'AB+', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (5, N'B', N'B', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (6, N'B+', N'B+', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (7, N'O', N'O', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (8, N'O Rh+', N'O Rh+', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (9, N'O+', N'O+', 1, CAST(N'2025-03-11T10:03:22.993' AS DateTime), CAST(N'2025-03-11T10:03:22.993' AS DateTime))

        SET IDENTITY_INSERT [dbo].[blood_types] OFF

        SET IDENTITY_INSERT [dbo].[citizenships] ON 

        INSERT [dbo].[citizenships] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (1, N'Filipino', 1, CAST(N'2025-03-11T10:05:08.423' AS DateTime), CAST(N'2025-03-11T10:05:08.423' AS DateTime), NULL)

        SET IDENTITY_INSERT [dbo].[citizenships] OFF

        SET IDENTITY_INSERT [dbo].[civil_status] ON 

        INSERT [dbo].[civil_status] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (1, N'Married', 1, CAST(N'2025-03-11T10:05:34.617' AS DateTime), CAST(N'2025-03-11T10:05:34.617' AS DateTime), NULL)

        INSERT [dbo].[civil_status] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (2, N'Separated', 1, CAST(N'2025-03-11T10:05:34.617' AS DateTime), CAST(N'2025-03-11T10:05:34.617' AS DateTime), NULL)

        INSERT [dbo].[civil_status] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (3, N'Single', 1, CAST(N'2025-03-11T10:05:34.617' AS DateTime), CAST(N'2025-03-11T10:05:34.617' AS DateTime), NULL)

        INSERT [dbo].[civil_status] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (4, N'Widowed', 1, CAST(N'2025-03-11T10:05:34.617' AS DateTime), CAST(N'2025-03-11T10:05:34.617' AS DateTime), NULL)

        INSERT [dbo].[civil_status] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (5, N'Widower', 1, CAST(N'2025-03-11T10:05:34.617' AS DateTime), CAST(N'2025-03-11T10:05:34.617' AS DateTime), NULL)

        SET IDENTITY_INSERT [dbo].[civil_status] OFF

        SET IDENTITY_INSERT [dbo].[employment_types] ON 

        INSERT [dbo].[employment_types] ([id], [name], [active], [created_at], [updated_at], [with_end_contract], [code]) VALUES (1, N'Casual', 1, CAST(N'2025-03-11T10:12:38.540' AS DateTime), CAST(N'2025-03-11T10:12:38.540' AS DateTime), 0, NULL)

        INSERT [dbo].[employment_types] ([id], [name], [active], [created_at], [updated_at], [with_end_contract], [code]) VALUES (2, N'Co-terminus', 1, CAST(N'2025-03-11T10:12:38.540' AS DateTime), CAST(N'2025-03-11T10:12:38.540' AS DateTime), 0, NULL)

        INSERT [dbo].[employment_types] ([id], [name], [active], [created_at], [updated_at], [with_end_contract], [code]) VALUES (3, N'Elective', 1, CAST(N'2025-03-11T10:12:38.540' AS DateTime), CAST(N'2025-03-11T10:12:38.540' AS DateTime), 0, NULL)

        INSERT [dbo].[employment_types] ([id], [name], [active], [created_at], [updated_at], [with_end_contract], [code]) VALUES (4, N'Permanent', 1, CAST(N'2025-03-11T10:12:38.540' AS DateTime), CAST(N'2025-03-11T10:12:38.540' AS DateTime), 0, NULL)

        INSERT [dbo].[employment_types] ([id], [name], [active], [created_at], [updated_at], [with_end_contract], [code]) VALUES (5, N'Temporary', 1, CAST(N'2025-03-11T10:12:38.540' AS DateTime), CAST(N'2025-03-11T10:12:38.540' AS DateTime), 0, NULL)

        SET IDENTITY_INSERT [dbo].[employment_types] OFF

        SET IDENTITY_INSERT [dbo].[genders] ON 

        INSERT [dbo].[genders] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Female', 1, CAST(N'2025-03-11T10:08:35.407' AS DateTime), CAST(N'2025-03-11T10:08:35.407' AS DateTime))

        INSERT [dbo].[genders] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Male', 1, CAST(N'2025-03-11T10:08:35.407' AS DateTime), CAST(N'2025-03-11T10:08:35.407' AS DateTime))

        SET IDENTITY_INSERT [dbo].[genders] OFF

        SET IDENTITY_INSERT [dbo].[name_prefixes] ON 

        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (1, N'Atty.', 1, CAST(N'2025-03-11T10:10:57.743' AS DateTime), CAST(N'2025-03-11T10:10:57.743' AS DateTime), NULL)

        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (2, N'Dr.', 1, CAST(N'2025-03-11T10:10:57.743' AS DateTime), CAST(N'2025-03-11T10:10:57.743' AS DateTime), NULL)

        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (3, N'Mr.', 1, CAST(N'2025-03-11T10:10:57.743' AS DateTime), CAST(N'2025-03-11T10:10:57.743' AS DateTime), NULL)

        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (4, N'Mrs.', 1, CAST(N'2025-03-11T10:10:57.743' AS DateTime), CAST(N'2025-03-11T10:10:57.743' AS DateTime), NULL)

        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (5, N'Ms.', 1, CAST(N'2025-03-11T10:10:57.743' AS DateTime), CAST(N'2025-03-11T10:10:57.743' AS DateTime), NULL)

        SET IDENTITY_INSERT [dbo].[name_prefixes] OFF

        SET IDENTITY_INSERT [dbo].[name_suffixes] ON 

        INSERT [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Jr.', 1, CAST(N'2025-03-11T10:16:29.930' AS DateTime), CAST(N'2025-03-11T10:16:29.930' AS DateTime))

        INSERT [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Sr.', 1, CAST(N'2025-03-11T10:16:29.930' AS DateTime), CAST(N'2025-03-11T10:16:29.930' AS DateTime))

        SET IDENTITY_INSERT [dbo].[name_suffixes] OFF

        SET IDENTITY_INSERT [dbo].[positions] ON 

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (1, N'ACCOUNTANT III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (2, N'ADMINISTRATIVE AIDE I (UTILITY WORKER I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (3, N'ADMINISTRATIVE AIDE II (MESSENGER)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (4, N'ADMINISTRATIVE AIDE III (CLERK )', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (5, N'ADMINISTRATIVE AIDE III (CLERK I )', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (6, N'ADMINISTRATIVE AIDE III (CLERK I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (7, N'ADMINISTRATIVE AIDE III (DRIVER I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (8, N'ADMINISTRATIVE AIDE III (LABORER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (9, N'ADMINISTRATIVE AIDE IV', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (10, N'ADMINISTRATIVE AIDE IV (ACCOUNTING CLERK I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (11, N'ADMINISTRATIVE AIDE IV (ACCOUNTING CLERK II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (12, N'ADMINISTRATIVE AIDE IV (BUDGETING AIDE)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (13, N'ADMINISTRATIVE AIDE IV (CLERK II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (14, N'ADMINISTRATIVE AIDE IV (DATA CONTROLLER I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (15, N'ADMINISTRATIVE AIDE IV (DRIVER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (16, N'ADMINISTRATIVE AIDE IV (EQUIPMENT AND COMMUNICATION TECHNICIAN', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (17, N'ADMINISTRATIVE AIDE IV (HUMAN RESOURCE MANAGEMENT AIDE) ', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (18, N'ADMINISTRATIVE AIDE IV (REPRODUCTION MACHINE OPERATOR I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (19, N'ADMINISTRATIVE AIDE VI ( DATA CONTROLLER I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (20, N'ADMINISTRATIVE AIDE VI (ACCOUNTING CLERK II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (21, N'ADMINISTRATIVE AIDE VI (CASH CLERK II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (22, N'ADMINISTRATIVE AIDE VI (CLERK III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (23, N'ADMINISTRATIVE ASSISTANT I (AUDIO-VISUAL EQUIPMENT OPERATOR III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (24, N'ADMINISTRATIVE ASSISTANT I (COMPUTER OPERATOR I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (25, N'ADMINISTRATIVE ASSISTANT I (REPRODUCTION MACHINE OPERATOR III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (26, N'ADMINISTRATIVE ASSISTANT II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (27, N'ADMINISTRATIVE ASSISTANT II (ACCOUNTING CLERK III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (28, N'ADMINISTRATIVE ASSISTANT II (BOOKKEEPER I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (29, N'ADMINISTRATIVE ASSISTANT II (CLERK IV)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (30, N'ADMINISTRATIVE ASSISTANT II (DATA CONTROLLER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (31, N'ADMINISTRATIVE ASSISTANT II (DISBURSING OFFICER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (32, N'ADMINISTRATIVE ASSISTANT II (HUMAN RESOURCE MANAGEMENT ASSISTANT)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (33, N'ADMINISTRATIVE ASSISTANT III (COMPUTER OPERATOR II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (34, N'ADMINISTRATIVE ASSISTANT III (SENIOR BOOKKEEPER)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (35, N'ADMINISTRATIVE ASSISTANT V (DATA CONTROLLER III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (36, N'ADMINISTRATIVE ASSITANT V (DATA CONTROLLER III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (37, N'ADMINISTRATIVE ASSSITANT II (DATA CONTROLLER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (38, N'ADMINISTRATIVE OFFICER I (RECORDS OFFICER I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (39, N'ADMINISTRATIVE OFFICER I (SUPPLY OFFCER I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (40, N'ADMINISTRATIVE OFFICER III (CASHIER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (41, N'ADMINISTRATIVE OFFICER III (RECORDS OFFICER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (42, N'ADMINISTRATIVE OFFICER III (SUPPLY OFFICER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (43, N'ADMINISTRATIVE OFFICER IV (BUDGET OFFICER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (44, N'ADMINISTRATIVE OFFICER IV (HUMAN RESOURCE MANAGEMENT OFFICER II)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (45, N'ADMINISTRATIVE OFFICER V (ADMINISTRATIVE OFFICER III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (46, N'ADMINISTRATIVE OFFICER V (BUDGET OFFICER III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (47, N'ADMINISTRATIVE OFFICER V (FISCAL EXAMINER III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (48, N'ADMINISTRATIVE OFFICER V (HUMAN RESOURCE MANAGEMENT III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (49, N'ADMINISTRATIVE OFFICER V (RECORDS OFFICER III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (50, N'ADMINISTRATTIVE ASSISTANT I (BOOKBINDER III)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (51, N'AGRICULTURAL TECHNOLOGIST ', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (52, N'AGRICULTURIST I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (53, N'AGRICULTURIST II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (54, N'AMINISTRATIVE AIDE III (DRIVER I)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (55, N'ARCHITECT I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (56, N'ASSESSMENT CLERK I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (57, N'ASSESSMENT CLERK II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (58, N'ASSESSMENT CLERK III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (59, N'ASSISTANT REGISTRATION OFFICER', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (60, N'ATTORNEY III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (61, N'BANDMASTER', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (62, N'BUDGETING ASSISTANT II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (63, N'BUILDING INSPECTOR', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (64, N'COMPUTER PROGRAMMER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (65, N'COMPUTER PROGRAMMER III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (66, N'CONSTRUCTION & MAINTENANCE FOREMAN', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (67, N'CONSTRUCTION & MAINTENANCE GENERAL FOREMAN', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (68, N'COOPERATIVE DEVELOPMENT SPECIALIST I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (69, N'DENTIST II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (70, N'DISABILITY AFFAIRS OFFICER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (71, N'DRAFTSMAN I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (72, N'DRAFTSMAN II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (73, N'DRAFTSMAN III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (74, N'DRIVER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (75, N'ELECTRICIAN I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (76, N'ENGINEER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (77, N'ENGINEER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (78, N'ENGINEER III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (79, N'ENGINEER III (AGRICULTURAL BIOSYSTEMS ENGINEER)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (80, N'ENGINEERING ASSISTANT', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (81, N'ENGINEERING III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (82, N'ENVIRONMENTAL MANAGEMENT SPECIALIST I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (83, N'ESECUTIVE ASSISTANT I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (84, N'EXECUTIVE ASSISTANT II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (85, N'FARM WORKER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (86, N'FARM WORKER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (87, N'FORESTER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (88, N'HEALTH PROGRAM OFFICER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (89, N'HEAVY EQUIPMENT OPERATOR I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (90, N'INFORMATION SYSTEM ANALYST I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (91, N'INTERNAL AUDITOR I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (92, N'LABOR AND EMPLOYMENT OFFICER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (93, N'LABOR FOREMAN', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (94, N'LIBRARIAN II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (95, N'LOCAL ASSESSMENT OPERATIONS OFFICER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (96, N'LOCAL DISASTER RISK REDUCTION AND MANAGEMENT ASSISTANT', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (97, N'LOCAL DISASTER RISK REDUCTION AND MANAGEMENT OFFICER III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (98, N'LOCAL DISASTER RISK REDUCTION MANAGEMENT ASSISTANT', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (99, N'LOCAL DISASTER RISK REDUCTION MANAGEMENT OFFICER', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (100, N'LOCAL LEGISLATIVE STAFF EMPLOYEE I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (101, N'LOCAL LEGISLATIVE STAFF EMPLOYEE II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (102, N'LOCAL LEGISLATIVE STAFF OFFICER III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (103, N'LOCAL REVENUE COLLECTION CLERK I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (104, N'LOCAL REVENUE COLLECTION OFFICER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (105, N'LOCAL REVENUE COLLECTION OFFICER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (106, N'LOCAL TREASURY OPERATIONS ASSISTANT', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (107, N'LOCAL TREASURY OPERATIONS OFFICER III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (108, N'MECHANIC I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (109, N'MECHANIC II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (110, N'MEDICAL EQUIPMENT TECHNICIAN I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (111, N'MEDICAL TECHNOLOGIST I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (112, N'MEDICAL TECHNOLOGIST II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (113, N'MIDWIFE I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (114, N'MIDWIFE II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (115, N'MIDWIFE III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (116, N'MUNICIPAL ACCOUNTANT', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (117, N'MUNICIPAL ASSESSOR', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (118, N'MUNICIPAL ENGINEER', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (119, N'MUNICIPAL VERNMENT ASSISTANT DEPARTMENT HEAD I (ASSISTANT MUNICIPAL ASSESSOR)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (120, N'MUNICIPAL VERNMENT ASSISTANT DEPARTMENT HEAD I (ASSISTANT MUNICIPAL TREASURER)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (121, N'MUNICIPAL VERNMENT DEPARTMENT HEAD I (MUNICIPAL ADMINISTRATOR)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (122, N'MUNICIPAL VERNMENT DEPARTMENT HEAD I (MUNICIPAL AGRICULTURIST)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (123, N'MUNICIPAL VERNMENT DEPARTMENT HEAD I (MUNICIPAL BUDGET OFFICER)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (124, N'MUNICIPAL VERNMENT DEPARTMENT HEAD I (MUNICIPAL HEALTH OFFICER)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (125, N'MUNICIPAL VERNMENT DEPARTMENT HEAD I (MUNICIPAL PLANNING AND DEVELOPMENT COORDINATOR)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (126, N'MUNICIPAL VERNMENT DEPARTMENT HEAD I (MUNICIPAL SOCIAL WELFARE & DEVELOPMENT OFFICER)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (127, N'MUNICIPAL VERNMENT DEPARTMENT HEAD I(MUNICIPAL CIVIL REGISTRAR)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (128, N'MUNICIPAL MAYOR', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (129, N'MUNICIPAL TREASURER', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (130, N'MUNICIPAL VICE MAYOR', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (131, N'MUSICIAN', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (132, N'NURSE I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (133, N'NURSE II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (134, N'NURSE III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (135, N'PLANNING ASSISTANT', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (136, N'PLANNING OFFICER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (137, N'PRIVATE SECRETARY II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (138, N'PROJECT DEVELOPMENT OFFICER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (139, N'PROJECT DEVELOPMENT OFFICER III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (140, N'REGISTRATION OFFICER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (141, N'REVENUE COLLECTION CLERK I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (142, N'REVENUE COLLECTION CLERK II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (143, N'RURAL HEALTH PHYSICIAN', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (144, N'SANGGUNIANG BAYAN MEMBER', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (145, N'SANGGUNIANG BAYAN MEMBER 1', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (146, N'SANGGUNIANG BAYAN MEMBER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (147, N'SANITATION INSPECTOR I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (148, N'SECRETARY TO THE SANGGUNIANG BAYAN', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (149, N'SENIOR ADMINISTRATIVE ASSISTANT I (DATA CONTROLLER IV)', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (150, N'SENIOR ENVIRONMENTAL MANAGEMENT SPECIALIST I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (151, N'SOCIAL WELFARE AIDE', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (152, N'SOCIAL WELFARE ASSISTANT', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (153, N'SOCIAL WELFARE OFFICER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (154, N'SOCIAL WELFARE OFFICER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (155, N'SOCIAL WELFARE OFFICER III', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (156, N'STATISTICIAN AIDE', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (157, N'STATISTICIAN I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (158, N'TAX MAPPER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (159, N'TAX MAPPER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (160, N'TAX MAPPING AIDE', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (161, N'TOURISM OPERATIONS OFFICER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (162, N'VETERINARIAN II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (163, N'YOUTH DEVELOPMENT OFFICER I', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (164, N'ZONING OFFICER II', 1, CAST(N'2025-03-11T10:23:16.130' AS DateTime), CAST(N'2025-03-11T10:23:16.130' AS DateTime), NULL, 0, NULL)

        SET IDENTITY_INSERT [dbo].[positions] OFF

        SET IDENTITY_INSERT [dbo].[religions] ON 

        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Roman Catholic', 1, NULL, NULL)

        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Muslim', 1, NULL, NULL)

        SET IDENTITY_INSERT [dbo].[religions] OFF
        ");
    }
}
