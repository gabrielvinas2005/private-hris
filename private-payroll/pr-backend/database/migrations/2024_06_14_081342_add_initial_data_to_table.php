<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddInitialDataToTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $menu_exist = DB::table('menus')->get();

        if ($menu_exist->isEmpty()) {
            DB::statement("
                SET IDENTITY_INSERT [dbo].[salary_steps] ON
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Step 1', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Step 2', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (3, N'Step 3', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (4, N'Step 4', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (5, N'Step 5', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (6, N'Step 6', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (7, N'Step 7', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (8, N'Step 8', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_steps] ([id], [name], [active], [created_at], [updated_at]) VALUES (9, N'Step 9', 0, NULL, NULL)
                SET IDENTITY_INSERT [dbo].[salary_steps] OFF
                SET IDENTITY_INSERT [dbo].[salary_grades] ON
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Salary Grade 1', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Salary Grade 2', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (3, N'Salary Grade 3', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (4, N'Salary Grade 4', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (5, N'Salary Grade 5', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (6, N'Salary Grade 6', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (7, N'Salary Grade 7', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (8, N'Salary Grade 8', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (9, N'Salary Grade 9', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (10, N'Salary Grade 10', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (11, N'Salary Grade 11', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (12, N'Salary Grade 12', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (13, N'Salary Grade 13', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (14, N'Salary Grade 14', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (15, N'Salary Grade 15', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (16, N'Salary Grade 16', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (17, N'Salary Grade 17', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (18, N'Salary Grade 18', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (19, N'Salary Grade 19', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (20, N'Salary Grade 20', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (21, N'Salary Grade 21', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (22, N'Salary Grade 22', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (23, N'Salary Grade 23', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (24, N'Salary Grade 24', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (25, N'Salary Grade 25', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (26, N'Salary Grade 26', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (27, N'Salary Grade 27', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (28, N'Salary Grade 28', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (29, N'Salary Grade 29', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (30, N'Salary Grade 30', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (31, N'Salary Grade 31', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (32, N'Salary Grade 32', 1, NULL, NULL)
                INSERT INTO [dbo].[salary_grades] ([id], [name], [active], [created_at], [updated_at]) VALUES (33, N'Salary Grade 33', 1, NULL, NULL)
                SET IDENTITY_INSERT [dbo].[salary_grades] OFF
                SET IDENTITY_INSERT [dbo].[offboarding_natures] ON
                INSERT INTO [dbo].[offboarding_natures] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'RESIGNATION', 1, NULL, NULL)
                INSERT INTO [dbo].[offboarding_natures] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Retirement', 1, NULL, NULL)
                INSERT INTO [dbo].[offboarding_natures] ([id], [name], [active], [created_at], [updated_at]) VALUES (3, N'AWOL', 1, NULL, NULL)
                INSERT INTO [dbo].[offboarding_natures] ([id], [name], [active], [created_at], [updated_at]) VALUES (4, N'END OF CONTRACT', 1, NULL, NULL)
                INSERT INTO [dbo].[offboarding_natures] ([id], [name], [active], [created_at], [updated_at]) VALUES (5, N'DEATH', 1, NULL, NULL)
                INSERT INTO [dbo].[offboarding_natures] ([id], [name], [active], [created_at], [updated_at]) VALUES (6, N'TERMINATION', 1, NULL, NULL)
                INSERT INTO [dbo].[offboarding_natures] ([id], [name], [active], [created_at], [updated_at]) VALUES (7, N'TRANSFERRED OUT', 1, NULL, NULL)
                SET IDENTITY_INSERT [dbo].[offboarding_natures] OFF
                -- SET IDENTITY_INSERT [dbo].[name_suffixes] ON
                -- INSERT INTO [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Jr.', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Sr.', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (3, N'I', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (4, N'II', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (5, N'III', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (6, N'IV', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (7, N'V', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (8, N' ', 1, NULL, NULL)
                -- SET IDENTITY_INSERT [dbo].[name_suffixes] OFF
                -- SET IDENTITY_INSERT [dbo].[name_prefixes] ON
                -- INSERT INTO [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Mr.', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Ms.', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (3, N'Mrs.', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (4, N'Dr.', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (5, N'Atty.', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (6, N'Cpt.', 1, NULL, NULL)
                -- INSERT INTO [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (7, N'Engr.', 1, NULL, NULL)
                -- SET IDENTITY_INSERT [dbo].[name_prefixes] OFF
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (1, N'Jan', N'January')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (2, N'Feb', N'February')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (3, N'Mar', N'March')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (4, N'Apr', N'April')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (5, N'May', N'May')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (6, N'Jun', N'June')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (7, N'Jul', N'July')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (8, N'Aug', N'August')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (9, N'Sep', N'September')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (10, N'Oct', N'October')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (11, N'Nov', N'November')
                INSERT INTO [dbo].[months] ([id], [abbrv], [name]) VALUES (12, N'Dec', N'December')
                SET IDENTITY_INSERT [dbo].[menus] ON
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (1, N'Employee Records', N'Contains Employee profile informations.', 1, NULL, NULL, N'employee_record', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (2, N'Promotion', N'Create process for employee promotion and  movement.', 1, NULL, NULL, N'promotion', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (3, N'Step Increment', N'Create process  to increment regular employee''s salary step. ', 1, NULL, NULL, N'step_increment', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (4, N'Fixed Schedule', N'Create fixed schedules for employees.', 1, NULL, NULL, N'fixed_schedule', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (5, N'Shifting Schedule', N'Create shifting schedule for employess.', 1, NULL, NULL, N'shifting_schedule', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (6, N'Assign Schedule', N'Create process to assign employees for specific schedule. ', 1, NULL, NULL, N'assign_schedule', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (7, N'Payroll Periods', N'Create payroll periods process.', 1, NULL, NULL, N'payroll_period', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (8, N'Payitem Schedule', N'Assign income and deduction for every payroll.', 1, NULL, NULL, N'payitem_schedule', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (9, N'Payroll Process', N'Create employee payroll''s.', 1, NULL, NULL, N'payroll_process', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (10, N'User''s List', N'Display list of users and contain process to edit access rights.', 1, NULL, NULL, N'user_list', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (11, N'Position Setup', N'Create maintenance data for  employee positions.', 1, NULL, NULL, N'positions', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (12, N'Department Setup', N'Create Department maintenance data.', 1, NULL, NULL, N'departments', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (13, N'Plantilla Setup', N'Create Plantilla position.', 1, NULL, NULL, N'plantilla_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (14, N'Income Setup', N'Create income maintenance data.', 1, NULL, NULL, N'income_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (15, N'Deduction Setup', N'Create deduction maintenance data.', 1, NULL, NULL, N'deduction_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (16, N'User Activities', N'Display all users system activity.', 1, NULL, NULL, N'user_activities', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (17, N'Off-Boarding', N'Process employee off-boarding.', 1, NULL, NULL, N'off-boarding', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (18, N'Employment Types', N'Create type of emplyment data maintenance.', 1, NULL, NULL, N'employment_type', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (19, N'Civil Status', N'Create civil status maintenance data.', 1, NULL, NULL, N'civil_status', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (20, N'Citizenship Setup', N'Create citizenship maintenance data.', 1, NULL, NULL, N'citizenship_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (21, N'Gender Setup', N'Create gender maintenance data.', 1, NULL, NULL, N'gender_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (22, N'Religion Setup', N'Create religion maintenance data. ', 1, NULL, NULL, N'religion_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (23, N'Name Prefix Setup', N'Create name prefix maintenance data.', 1, NULL, NULL, N'name_prefix', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (24, N'Name Suffix Setup', N'Create name suffix maintenance data.', 1, NULL, NULL, N'name_suffix', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (25, N'Eligibility Setup', N'Create eligibility maintenance data.', 1, NULL, NULL, N'eligibility_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (26, N'Specializations Setup', N'Create Specializations maintenance data.', 1, NULL, NULL, N'learning_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (27, N'Salary Schedule Setup', N'Create salary schedule maintenance data.', 1, NULL, NULL, N'salary_schedule', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (28, N'Tax Table Setup', N'Maintain tax table data.', 1, NULL, NULL, N'tax_table', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (29, N'PhilHealth Table Setup', N'Maintain philhealth data.', 1, NULL, NULL, N'philhealth_table', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (30, N'GSIS Table Setup', N'Maintenance gsis table data.', 1, NULL, NULL, N'gsis_table', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (31, N'Salary Step Setup', N'Create salary step maintenance data.', 1, NULL, NULL, N'salary_step', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (32, N'Salary Grade Setup', N'Create salary grade maintenance data.', 1, NULL, NULL, N'salary_grade', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (33, N'Company Setup', N'Create company information.', 1, NULL, NULL, N'company_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (34, N'Branch Setup', N'Create branch information.', 1, NULL, NULL, N'branch_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (35, N'Blood Type Setup', N'Create blood type maintenance data.', 1, NULL, NULL, N'blood_type', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (36, N'Payroll Interval Setup', N'Create payroll interval maintenance data.', 1, NULL, NULL, N'payroll_interval_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (37, N'Promotion Types Setup', N'Create promotion maintenance data.', 1, NULL, NULL, N'promotion_types', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (38, N'Off-Boarding Types Setup', N'Create off-boarding type maintenance data.', 1, NULL, NULL, N'offboarding_types', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (39, N'Overtime Types Setup', N'Create overtime types maintenance data.', 1, NULL, NULL, N'overtime_types', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (40, N'Holiday Types Setup', N'Create holiday types maintenance data.', 1, NULL, NULL, N'holiday_types', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (41, N'Holidays Setup', N'Create holidays maintenance data.', 1, NULL, NULL, N'holidays_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (43, N'Leave Types Setup', N'Create leave types maintenance data.', 1, NULL, NULL, N'leave_types', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (44, N'Official Business Types Setup', N'Create official business types maintenance data.', 1, NULL, NULL, N'official_business_types', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (45, N'Holiday Tagging Setup', N'Create holiday tagging maintenance data.', 0, NULL, NULL, N'holiday_taggings', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (46, N'Leave Credits', N'Create leave credit balances.', 1, NULL, NULL, N'leave_credits', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (47, N'Night Differncial Setup', N'Create night diff setup', 1, NULL, NULL, N'night_diff', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (48, N'Income Type Setup', N'Create income type maintenance data.', 1, NULL, NULL, N'income_types', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (49, N'Deduction Type Setup', N'Create deduction type maintenance data.', 1, NULL, NULL, N'deduction_type', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (50, N'Process Attendance', N'Process attendance', 1, NULL, NULL, N'process_attendance', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (51, N'Daily Time Records', N'Process dtr', 1, NULL, NULL, N'daily_time_records', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (52, N'Leave Approval', N'Monitoring of leave applications', 1, NULL, NULL, N'leave_approvals', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (53, N'Official Business Approval', N'Monitoring of official business applications', 1, NULL, NULL, N'official_business_approvals', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (54, N'Overtime Approvals', N'Monitoring of overtime applications', 1, NULL, NULL, N'overtime_approvals', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (55, N'Loan Application', N'Application of loans', 1, NULL, NULL, N'loan_applications', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (56, N'Timekeeping Setup', N'Setup of timekeeping divisors', 1, NULL, NULL, N'time_keeping_setups', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (57, N'Salary Adjustment', N'Adjustment of Salary tranche', 1, NULL, NULL, N'salary_adjustments', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (58, N'Work Cancellation', N'Create work cancelled', 1, NULL, NULL, N'work_cancellations', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (59, N'Rating', N'Create adjectival rating', 1, NULL, NULL, N'rating', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (60, N'Applicant Records', N'Monitoring of applications', 1, NULL, NULL, N'applicants', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (61, N'Division Setup', N'Create division maintenance data', 1, NULL, NULL, N'divisions', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (62, N'Section Setup', N'Create section maintenance data', 1, NULL, NULL, N'sections', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (63, N'Semester for IPCR setup', N'Create semester information for IPCR', 1, NULL, NULL, N'semester_rating', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (64, N'IPCR', N'Managing employees'' ipcr', 1, NULL, NULL, N'ipcr', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (65, N'Review 201 Updates', N'Monitoring of updates for approval in 201', 1, NULL, NULL, N'review_201_updates', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (66, N'Export Employee List', N'Export list of employees', 1, NULL, NULL, N'export_employee_data', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (67, N'Income and Deductions', N'Managing income and deductions of employees', 1, NULL, NULL, N'payroll_income_deductions', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (68, N'Non-Plantilla Setup', N'Manage Non-Plantilla Positions', 1, NULL, NULL, N'non_plantilla_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (69, N'PACSVAL', N'Export PACSVAL File', 1, NULL, NULL, N'pacsval', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (70, N'Approver Setup', N'Assign Approvers', 1, NULL, NULL, N'approvers', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (71, N'Document Number Setup', N'Create Document Numbers', 1, NULL, NULL, N'document_number_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (72, N'Review DTR', N'Review DTR', 1, NULL, NULL, N'review_daily_time_records', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (73, N'Overtime Payroll Process', N'Process Overtime Payroll', 1, NULL, NULL, N'overtime_payroll_process', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (74, N'Vacant Position Posting', N'Posting of Vacant Positions', 1, NULL, NULL, N'vacant_position_posting', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (75, N'Examination Setup', N'Examination Setup', 1, NULL, NULL, N'examination_setup', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (76, N'Length of Service', N'generate length of service of employees', 1, NULL, NULL, N'length_of_service', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (77, N'Positions with RATA', N'Positions with RATA', 1, NULL, NULL, N'rata_positions', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (78, N'RATA Table Setup', N'RATA Table Setup', 1, NULL, NULL, N'rata_table', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (79, N'RATA Payroll', N'RATA Payroll', 1, NULL, NULL, N'rata_payroll', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (80, N'Loyalty Award', N'Loyalty Award', 1, NULL, NULL, N'loyalty_award', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (81, N'Loyalty Award Setup', N'Loyalty Award Setup', 1, NULL, NULL, N'loyalty_award_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (82, N'Uniform & Clothing Setup', N'Uniform & Clothing Setup', 1, NULL, NULL, N'uniform_clothing_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (83, N'Uniform & Clothing', N'Uniform & Clothing', 1, NULL, NULL, N'uniform_clothing', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (84, N'Overtime Tax Table', N'Overtime Tax Table', 1, NULL, NULL, N'overtime_tax_table', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (85, N'Pending Deduction', N'Pending Deduction', 1, NULL, NULL, N'pending_deduction_report', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (86, N'Tardiness Report', N'Tardiness Report', 1, NULL, NULL, N'tardiness_report', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (87, N'RATA Payroll Report', N'RATA Payroll Report', 1, NULL, NULL, N'rata_payroll_report', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (88, N'Mid Year Table Setup', N'Maintenance midyear table data.', 1, NULL, NULL, N'midyear_table', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (89, N'Year End Table Setup', N'Maintenance yearend table data.', 1, NULL, NULL, N'yearend_table', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (90, N'Cash Gift Table Setup', N'Maintenance cashgift table data.', 1, NULL, NULL, N'cashgift_table', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (91, N'Mid Year Bonus', N'Mid Year Bonus', 1, NULL, NULL, N'process_midyear', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (92, N'Year End Bonus', N'Year End Bonus', 1, NULL, NULL, N'process_yearend', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (93, N'Mid Year Bonus Report', N'Mid Year Bonus Report', 1, NULL, NULL, N'midyear_report', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (94, N'Year End Bonus Report', N'Year End Bonus Report', 1, NULL, NULL, N'yearend_report', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (95, N'Monetization Payroll', N'Monetization Payroll', 1, NULL, NULL, N'monetization_payroll', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (96, N'Monetization Payroll Report', N'Monetization Payroll Report', 1, NULL, NULL, N'monetization_payroll_report', 3, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (97, N'Monetization Setup', N'Monetization Setup', 1, NULL, NULL, N'monetization_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (98, N'COC Details', N'View COC details of employees', 1, NULL, NULL, N'coc_details', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (99, N'Leave Taken Monitoring ', N'Leave Taken Monitoring', 1, NULL, NULL, N'leave_taken_monitoring', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (100, N'Biometrics Data', N'Biometrics Data', 1, NULL, NULL, N'biometrics', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (101, N'Biometric Setup', N'Biometric Setup', 1, NULL, NULL, N'biometric_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (102, N'Competencies Setup', N'Create competency maintenance data', 1, NULL, NULL, N'competencies', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (103, N'Competency View', N'View employees competency details', 1, NULL, NULL, N'competency_view_hr', 5, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (104, N'EETE Rating Setup', N'EETE Rating Setup', 1, NULL, NULL, N'eete_rating_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (105, N'Leave Credit Card', N'Leave Credit Card', 1, NULL, NULL, N'leave_credit_card', 2, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (106, N'Exam Category Setup', N'Exam Category Setup', 1, NULL, NULL, N'exam_category_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (107, N'HRDD Review', N'HRDD Review', 1, NULL, NULL, N'hrdd_review_list', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (108, N'Administrator Selection', N'Administrator Selection', 1, NULL, NULL, N'administrator_selection', 1, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (109, N'Competency Training Management', N'Competency Training Management', 1, NULL, NULL, N'training_management', 5, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (110, N'Training Requisitioner Setup', N'Training Requisitioner Setup', 1, NULL, NULL, N'training_requisitioner', 5, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (111, N'Training Requisition', N'Training Requisition', 1, NULL, NULL, N'training_requisition', 5, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (112, N'Payroll Cutoffs', N'Payroll Cutoffs', 1, NULL, NULL, N'payroll_cutoffs', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (113, N'Migrate Branches', N'Migrate Branches', 1, NULL, NULL, N'migrate_branches', 6, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (114, N'Migrate Offices', N'Migrate Offices', 1, NULL, NULL, N'migrate_offices', 6, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (115, N'Migrate Divisions', N'Migrate Divisions', 1, NULL, NULL, N'migrate_divisions', 6, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (116, N'Migrate Sections', N'Migrate Sections', 1, NULL, NULL, N'migrate_sections', 6, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (117, N'Migrate Employment Types', N'Migrate Employment Types', 1, NULL, NULL, N'migrate_employment_types', 6, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (118, N'Document Type Setup', N'Document Type Setup', 1, NULL, NULL, N'document_type_setup', 4, 0)
                INSERT INTO [dbo].[menus] ([id], [menu], [description], [active], [created_at], [updated_at], [menu_key], [module_id], [status]) VALUES (119, N'Step Increment Approval', N'Step Increment Approval', 1, NULL, NULL, N'step_increment_approval', 1, 0)
                SET IDENTITY_INSERT [dbo].[menus] OFF
                SET IDENTITY_INSERT [dbo].[learnings] ON
                INSERT INTO [dbo].[learnings] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Managerial', 1, NULL, NULL)
                INSERT INTO [dbo].[learnings] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Training', 1, NULL, NULL)
                SET IDENTITY_INSERT [dbo].[learnings] OFF
                -- SET IDENTITY_INSERT [dbo].[genders] ON
                -- INSERT INTO [dbo].[genders] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Female', 1, NULL, NULL)
                -- INSERT INTO [dbo].[genders] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Male', 1, NULL, NULL)
                -- SET IDENTITY_INSERT [dbo].[genders] OFF
                SET IDENTITY_INSERT [dbo].[application_status] ON
                INSERT INTO [dbo].[application_status] ([id], [name], [created_at], [updated_at]) VALUES (1, N'Active', NULL, NULL)
                INSERT INTO [dbo].[application_status] ([id], [name], [created_at], [updated_at]) VALUES (2, N'Not Qualified', NULL, NULL)
                INSERT INTO [dbo].[application_status] ([id], [name], [created_at], [updated_at]) VALUES (3, N'Will not proceed', NULL, NULL)
                INSERT INTO [dbo].[application_status] ([id], [name], [created_at], [updated_at]) VALUES (4, N'Proceed to next step', NULL, NULL)
                INSERT INTO [dbo].[application_status] ([id], [name], [created_at], [updated_at]) VALUES (5, N'For Hiring', NULL, NULL)
                SET IDENTITY_INSERT [dbo].[application_status] OFF
                SET IDENTITY_INSERT [dbo].[exam_difficulty_levels] ON
                INSERT INTO [dbo].[exam_difficulty_levels] ([id], [difficulty_level], [active], [created_at], [updated_at]) VALUES (1, N'Easy', 1, NULL, NULL)
                INSERT INTO [dbo].[exam_difficulty_levels] ([id], [difficulty_level], [active], [created_at], [updated_at]) VALUES (2, N'Medium', 1, NULL, NULL)
                INSERT INTO [dbo].[exam_difficulty_levels] ([id], [difficulty_level], [active], [created_at], [updated_at]) VALUES (3, N'Difficult', 1, NULL, NULL)
                INSERT INTO [dbo].[exam_difficulty_levels] ([id], [difficulty_level], [active], [created_at], [updated_at]) VALUES (4, N'Complex', 1, NULL, NULL)
                SET IDENTITY_INSERT [dbo].[exam_difficulty_levels] OFF
                SET IDENTITY_INSERT [dbo].[leave_types] ON
                INSERT INTO [dbo].[leave_types] ([name], [id], [active], [created_at], [updated_at], [service_credit], [accrued_id], [accrual_amount], [accrual_frequency_id], [leave_balance_policy_id], [is_editable_id], [serial_number]) VALUES (N'Birthday Leaves', 18, 0, NULL, NULL, 0, 2, 1.00, 0, 1, 1, NULL)
                INSERT INTO [dbo].[leave_types] ([name], [id], [active], [created_at], [updated_at], [service_credit], [accrued_id], [accrual_amount], [accrual_frequency_id], [leave_balance_policy_id], [is_editable_id], [serial_number]) VALUES (N'CTO Leave', 4, 1, NULL, NULL, 1, 2, 0.00, 2, 1, 2, N'1')
                INSERT INTO [dbo].[leave_types] ([name], [id], [active], [created_at], [updated_at], [service_credit], [accrued_id], [accrual_amount], [accrual_frequency_id], [leave_balance_policy_id], [is_editable_id], [serial_number]) VALUES (N'Emergency Leave', 11, 1, NULL, NULL, 0, 1, 1.00, 1, 3, 2, N'9')
                INSERT INTO [dbo].[leave_types] ([name], [id], [active], [created_at], [updated_at], [service_credit], [accrued_id], [accrual_amount], [accrual_frequency_id], [leave_balance_policy_id], [is_editable_id], [serial_number]) VALUES (N'Maternity Leave', 7, 1, NULL, NULL, 0, 2, 105.00, 0, 3, 1, N'2')
                INSERT INTO [dbo].[leave_types] ([name], [id], [active], [created_at], [updated_at], [service_credit], [accrued_id], [accrual_amount], [accrual_frequency_id], [leave_balance_policy_id], [is_editable_id], [serial_number]) VALUES (N'Paternity Leave', 8, 1, NULL, NULL, 0, 2, 7.00, 0, 3, 2, N'3')
                INSERT INTO [dbo].[leave_types] ([name], [id], [active], [created_at], [updated_at], [service_credit], [accrued_id], [accrual_amount], [accrual_frequency_id], [leave_balance_policy_id], [is_editable_id], [serial_number]) VALUES (N'Sick Leave', 3, 1, NULL, NULL, 0, 1, 1.25, 1, 2, 1, N'4')
                INSERT INTO [dbo].[leave_types] ([name], [id], [active], [created_at], [updated_at], [service_credit], [accrued_id], [accrual_amount], [accrual_frequency_id], [leave_balance_policy_id], [is_editable_id], [serial_number]) VALUES (N'Vacation Leave', 16, 1, NULL, NULL, 0, 1, 3.00, 1, 2, 1, N'7')
                SET IDENTITY_INSERT [dbo].[leave_types] OFF
                SET IDENTITY_INSERT [dbo].[document_numbers] ON
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'Certificate of Employment', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'Certificate of Employment with Compensation', 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'DTR', 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'NOSA', 6, N'A-HRDD.FRM.2016.009', N'Revision 1 / 07-26-2022', N'A-HRDD.FRM.2016.009', N'Revision 1 / 07-26-2022', NULL, NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'NOSI', 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'OB Slip', 9, N'A-HRDD.FRM.2015.002', N'Revision 1 / 07-26-2022', N'A-HRDD.FRM.2015.002', N'Revision 1 / 07-26-2022', NULL, NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'Payroll Summary', 1, N'A-HRDD.FRM.2015.006', N'Revision 0/12-22-2015', N'A-HRDD.FRM.2015.005', N'Revision 0 / 12-22-2015', N'payroll_summary', NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'Payslip', 2, N'A-HRDD.FRM.2015.007', NULL, N'A-HRDD.FRM.2015.007', NULL, N'payslip', NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'Service Record', 8, N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', NULL, NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'Certificate of Last Day of Service', 10, N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', NULL, NULL, NULL)
                INSERT INTO [dbo].[document_numbers] ([name], [id], [rd_document_number], [rd_revision], [co_document_number], [co_revision], [key], [created_at], [updated_at]) VALUES (N'OJT Certificate', 11, N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', N'A-HRDD.FRM.2015.015', N'Revision 1 / 07-26-2022', NULL, NULL, NULL)
                SET IDENTITY_INSERT [dbo].[document_numbers] OFF
                SET IDENTITY_INSERT [dbo].[pds_questionaires] ON 
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (1, N'34.a', N'34. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office,Bureau or Department where you will be apppointed,a. within the third degree?', 0, 0, NULL, NULL, NULL, NULL, NULL, N'1(a)')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (2, N'34.b', N'34.b. within the fourth degree (for Local Government Unit - Career Employees)?', 0, 0, NULL, NULL, NULL, NULL, NULL, N'1(b)')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (3, N'35.a', N'35. a. Have you ever been found guilty of any administrative offense?', 0, 0, NULL, NULL, NULL, NULL, NULL, N'2(a)')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (4, N'35.b', N'35.b. Have you been criminally charged before any court?', 0, 0, NULL, NULL, NULL, NULL, NULL, N'2(b)')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (5, N'36', N'36. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?', 0, 0, NULL, NULL, NULL, NULL, NULL, N'3')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (6, N'37', N'37. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out', 0, 0, NULL, NULL, NULL, NULL, NULL, N'4')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (7, N'38.a', N'38. a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?
                ', 0, 0, NULL, NULL, NULL, NULL, NULL, N'5')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (8, N'38.b', N'38.b. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL)
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (9, N'39', N'39. Have you acquired the status of an immigrant or permanent resident of another country?', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL)
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (10, N'40.a', N'40. Pursuant to: (a) Indigenous People''s Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:', 0, 0, NULL, NULL, NULL, NULL, NULL, N'6(a)')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (11, N'40.b', N'40.b. Are you a person with disability?', 0, 0, NULL, NULL, NULL, NULL, NULL, N'6(b)')
                INSERT [dbo].[pds_questionaires] ([id], [code], [questions], [is_yes], [is_no], [yes_details], [date_filed], [case_status], [created_at], [updated_at], [que_id]) VALUES (12, N'40.c', N'40.c. Are you a solo parent?', 0, 0, NULL, NULL, NULL, NULL, NULL, N'6(c)')
                SET IDENTITY_INSERT [dbo].[pds_questionaires] OFF
                SET IDENTITY_INSERT [dbo].[schedule_days] ON
                INSERT INTO [dbo].[schedule_days] ([name], [id], [created_at], [updated_at]) VALUES (N'Friday', 5, NULL, NULL)
                INSERT INTO [dbo].[schedule_days] ([name], [id], [created_at], [updated_at]) VALUES (N'Monday', 1, NULL, NULL)
                INSERT INTO [dbo].[schedule_days] ([name], [id], [created_at], [updated_at]) VALUES (N'Saturday', 6, NULL, NULL)
                INSERT INTO [dbo].[schedule_days] ([name], [id], [created_at], [updated_at]) VALUES (N'Sunday', 7, NULL, NULL)
                INSERT INTO [dbo].[schedule_days] ([name], [id], [created_at], [updated_at]) VALUES (N'Thursday', 4, NULL, NULL)
                INSERT INTO [dbo].[schedule_days] ([name], [id], [created_at], [updated_at]) VALUES (N'Tuesday', 2, NULL, NULL)
                INSERT INTO [dbo].[schedule_days] ([name], [id], [created_at], [updated_at]) VALUES (N'Wednesday', 3, NULL, NULL)
                SET IDENTITY_INSERT [dbo].[schedule_days] OFF
                SET IDENTITY_INSERT [dbo].[deduction_priorities] ON
                INSERT INTO [dbo].[deduction_priorities] ([id], [deduction], [priority], [is_editable], [created_at], [updated_at]) VALUES (1, N'GSIS Contribution', 1, 0, NULL, NULL)
                INSERT INTO [dbo].[deduction_priorities] ([id], [deduction], [priority], [is_editable], [created_at], [updated_at]) VALUES (2, N'PhilHealth', 2, 0, NULL, NULL)
                INSERT INTO [dbo].[deduction_priorities] ([id], [deduction], [priority], [is_editable], [created_at], [updated_at]) VALUES (3, N'Pag-Ibig', 3, 0, NULL, NULL)
                INSERT INTO [dbo].[deduction_priorities] ([id], [deduction], [priority], [is_editable], [created_at], [updated_at]) VALUES (4, N'Tax', 4, 0, NULL, NULL)
                INSERT INTO [dbo].[deduction_priorities] ([id], [deduction], [priority], [is_editable], [created_at], [updated_at]) VALUES (5, N'Loans', 5, 0, NULL, NULL)
                INSERT INTO [dbo].[deduction_priorities] ([id], [deduction], [priority], [is_editable], [created_at], [updated_at]) VALUES (6, N'Tardiness', 8, 1, NULL, NULL)
                INSERT INTO [dbo].[deduction_priorities] ([id], [deduction], [priority], [is_editable], [created_at], [updated_at]) VALUES (7, N'Undertime', 7, 1, NULL, NULL)
                INSERT INTO [dbo].[deduction_priorities] ([id], [deduction], [priority], [is_editable], [created_at], [updated_at]) VALUES (8, N'Absent', 6, 1, NULL, NULL)
                SET IDENTITY_INSERT [dbo].[deduction_priorities] OFF

                SET IDENTITY_INSERT [dbo].[eligibilities] ON
                INSERT INTO [dbo].[eligibilities]([id],[name],[active],[created_at],[updated_at]) VALUES (1,N'Bar/Board Eligibility (RA 1080)',1,NULL,NULL)
                INSERT INTO [dbo].[eligibilities]([id],[name],[active],[created_at],[updated_at]) VALUES (2,N'Barangay Health Worker Eligibility (RA 7883)',1,NULL,NULL)
                INSERT INTO [dbo].[eligibilities]([id],[name],[active],[created_at],[updated_at]) VALUES (3,N'Barangay Nutrition Scholar Eligibility (PD 1569)',1,NULL,NULL)
                INSERT INTO [dbo].[eligibilities]([id],[name],[active],[created_at],[updated_at]) VALUES (4,N'Barangay Official Eligibility (RA 7160)',1,NULL,NULL)
                SET IDENTITY_INSERT [dbo].[eligibilities] OFF

                -- SET IDENTITY_INSERT [dbo].[employment_types] ON
                -- INSERT INTO [dbo].[employment_types] ([id],[name],[active],[created_at],[updated_at],[with_end_contract]) VALUES (1,N'Permanent',1,NULL,NULL,0)
                -- INSERT INTO [dbo].[employment_types] ([id],[name],[active],[created_at],[updated_at],[with_end_contract]) VALUES (2,N'Casual',1,NULL,NULL,0)
                -- INSERT INTO [dbo].[employment_types] ([id],[name],[active],[created_at],[updated_at],[with_end_contract]) VALUES (3,N'Contract of Service',1,NULL,NULL,1)
                -- SET IDENTITY_INSERT [dbo].[employment_types] OFF

                -- SET IDENTITY_INSERT [dbo].[civil_status] ON
                -- INSERT INTO [dbo].[civil_status]([id],[name] ,[active] ,[created_at] ,[updated_at]) VALUES (1,N'Single',1,NULL,NULL)
                -- INSERT INTO [dbo].[civil_status]([id],[name] ,[active] ,[created_at] ,[updated_at]) VALUES (2,N'Married',1,NULL,NULL)
                -- SET IDENTITY_INSERT [dbo].[civil_status] OFF

                --SET IDENTITY_INSERT [dbo].[citizenships] ON
                --INSERT INTO [dbo].[citizenships]([id],[name],[code],[active],[created_at],[updated_at]) VALUES (1,N'Filipino',N'Filipino',1,NULL,NULL)
                --INSERT INTO [dbo].[citizenships]([id],[name],[code],[active],[created_at],[updated_at]) VALUES (2,N'American',N'American',1,NULL,NULL)
                --SET IDENTITY_INSERT [dbo].[citizenships] OFF

                --SET IDENTITY_INSERT [dbo].[religions] ON
                --INSERT INTO [dbo].[religions]([id],[name] ,[active] ,[created_at] ,[updated_at]) VALUES (1,N'Roman Catholic',1,NULL,NULL)
                --INSERT INTO [dbo].[religions]([id],[name] ,[active] ,[created_at] ,[updated_at]) VALUES (2,N'Muslim',1,NULL,NULL)
                --SET IDENTITY_INSERT [dbo].[religions] OFF
                ");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("
            TRUNCATE TABLE menus
            TRUNCATE TABLE application_status
            TRUNCATE TABLE months
            TRUNCATE TABLE genders
            TRUNCATE TABLE learnings
            TRUNCATE TABLE name_prefixes
            TRUNCATE TABLE name_suffixes
            TRUNCATE TABLE offboarding_natures
            TRUNCATE TABLE salary_grades
            TRUNCATE TABLE salary_steps
        ");
    }
}
