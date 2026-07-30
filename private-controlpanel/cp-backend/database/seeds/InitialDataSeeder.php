<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialDataSeeder extends Seeder
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
        
        INSERT [dbo].[branches] ([id], [name], [created_at], [updated_at], [is_main_branch], [branch_head_id], [code]) VALUES (1, N'Main Branch', CAST(N'2024-11-29T12:40:14.710' AS DateTime), CAST(N'2024-11-29T12:40:14.710' AS DateTime), 1, 0, NULL)
        
        INSERT [dbo].[branches] ([id], [name], [created_at], [updated_at], [is_main_branch], [branch_head_id], [code]) VALUES (2, N'Ibajay Branch', CAST(N'2024-11-29T12:40:14.710' AS DateTime), CAST(N'2024-11-29T12:40:14.710' AS DateTime), 0, 0, NULL)
        
        INSERT [dbo].[branches] ([id], [name], [created_at], [updated_at], [is_main_branch], [branch_head_id], [code]) VALUES (3, N'Kalibo Branch', CAST(N'2024-11-29T12:40:14.710' AS DateTime), CAST(N'2024-11-29T12:40:14.710' AS DateTime), 0, 0, NULL)
        
        INSERT [dbo].[branches] ([id], [name], [created_at], [updated_at], [is_main_branch], [branch_head_id], [code]) VALUES (4, N'Makato Branch', CAST(N'2024-11-29T12:40:14.710' AS DateTime), CAST(N'2024-11-29T12:40:14.710' AS DateTime), 0, 0, NULL)
        
        INSERT [dbo].[branches] ([id], [name], [created_at], [updated_at], [is_main_branch], [branch_head_id], [code]) VALUES (5, N'New Washington Branch', CAST(N'2024-11-29T12:40:14.710' AS DateTime), CAST(N'2024-11-29T12:40:14.710' AS DateTime), 0, 0, NULL)
        
        SET IDENTITY_INSERT [dbo].[branches] OFF

        SET IDENTITY_INSERT [dbo].[departments] ON 
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (2, N'ACCOUNTING OFFICE', N'ACCOUNTING OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (3, N'ADMINISTRATIVE AND SUPPORT SERVICES OFFICE', N'ADMINISTRATIVE AND SUPPORT SERVICES OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (4, N'AKLAN STAE UNIVERSITY - CAHM DEPARTMENT', N'AKLAN STAE UNIVERSITY - CAHM DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (5, N'AKLAN STATE UNIVERSITY -  CAHM DEPARTMENT', N'AKLAN STATE UNIVERSITY -  CAHM DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (6, N'AKLAN STATE UNIVERSITY -  CRIM DEPARTMENT', N'AKLAN STATE UNIVERSITY -  CRIM DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (7, N'AKLAN STATE UNIVERSITY -  CRIMINOLOGY DEPARTMENT', N'AKLAN STATE UNIVERSITY -  CRIMINOLOGY DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (8, N'AKLAN STATE UNIVERSITY -  TEACHER EDUCATION DEPARTMENT', N'AKLAN STATE UNIVERSITY -  TEACHER EDUCATION DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (9, N'AKLAN STATE UNIVERSITY - ACCOUNTING OFFICE', N'AKLAN STATE UNIVERSITY - ACCOUNTING OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (10, N'AKLAN STATE UNIVERSITY - ADMINISTRATION', N'AKLAN STATE UNIVERSITY - ADMINISTRATION', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (11, N'AKLAN STATE UNIVERSITY - ADMINISTRATIVE SERVICES', N'AKLAN STATE UNIVERSITY - ADMINISTRATIVE SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (12, N'AKLAN STATE UNIVERSITY - AUXILIARY SERVICES', N'AKLAN STATE UNIVERSITY - AUXILIARY SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (13, N'AKLAN STATE UNIVERSITY - BUDGET OFFICE', N'AKLAN STATE UNIVERSITY - BUDGET OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (14, N'AKLAN STATE UNIVERSITY - CAHM DEPARTMENT', N'AKLAN STATE UNIVERSITY - CAHM DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (15, N'AKLAN STATE UNIVERSITY - CAMPUS DIRECTOR''S OFFICE', N'AKLAN STATE UNIVERSITY - CAMPUS DIRECTOR''S OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (16, N'AKLAN STATE UNIVERSITY - CASHIER''S OFFICE', N'AKLAN STATE UNIVERSITY - CASHIER''S OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (17, N'AKLAN STATE UNIVERSITY - CRIM DEPARTMENT', N'AKLAN STATE UNIVERSITY - CRIM DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (18, N'AKLAN STATE UNIVERSITY - CRIMINOLOGY DEPARTMENT', N'AKLAN STATE UNIVERSITY - CRIMINOLOGY DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (19, N'AKLAN STATE UNIVERSITY - DUMLOG FISHPOND', N'AKLAN STATE UNIVERSITY - DUMLOG FISHPOND', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (20, N'AKLAN STATE UNIVERSITY - FISHPOND', N'AKLAN STATE UNIVERSITY - FISHPOND', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (21, N'AKLAN STATE UNIVERSITY - FMS DEPARTMENT', N'AKLAN STATE UNIVERSITY - FMS DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (22, N'AKLAN STATE UNIVERSITY - GUIDANCE OFFICE', N'AKLAN STATE UNIVERSITY - GUIDANCE OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (23, N'AKLAN STATE UNIVERSITY - HR OFFICE', N'AKLAN STATE UNIVERSITY - HR OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (24, N'AKLAN STATE UNIVERSITY - IGP/CAHM', N'AKLAN STATE UNIVERSITY - IGP/CAHM', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (25, N'AKLAN STATE UNIVERSITY - LIBRARY OFFICE', N'AKLAN STATE UNIVERSITY - LIBRARY OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (26, N'AKLAN STATE UNIVERSITY - MEDICAL CLINIC', N'AKLAN STATE UNIVERSITY - MEDICAL CLINIC', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (27, N'AKLAN STATE UNIVERSITY - MIS/ICT', N'AKLAN STATE UNIVERSITY - MIS/ICT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (28, N'AKLAN STATE UNIVERSITY - OFFICE OF STUDENT AFFAIRS', N'AKLAN STATE UNIVERSITY - OFFICE OF STUDENT AFFAIRS', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (29, N'AKLAN STATE UNIVERSITY - REGISTRAR''S OFFICE', N'AKLAN STATE UNIVERSITY - REGISTRAR''S OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (30, N'AKLAN STATE UNIVERSITY - SECURITY SERVICES', N'AKLAN STATE UNIVERSITY - SECURITY SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (31, N'AKLAN STATE UNIVERSITY - SUPPLY OFFICE', N'AKLAN STATE UNIVERSITY - SUPPLY OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (32, N'AKLAN STATE UNIVERSITY - TEACHER EDUCATION DEPARTMENT', N'AKLAN STATE UNIVERSITY - TEACHER EDUCATION DEPARTMENT', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (33, N'AKLAN STATE UNIVERSITY -CASHIER OFFICE', N'AKLAN STATE UNIVERSITY -CASHIER OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (34, N'AUXILIARY SERVICES', N'AUXILIARY SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (35, N'BEED PROGRAM', N'BEED PROGRAM', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (36, N'BSED PROGRAM', N'BSED PROGRAM', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (37, N'BUDGET OFFICE', N'BUDGET OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (38, N'CAMPUS DIRECTOR''S OFFICE', N'CAMPUS DIRECTOR''S OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (39, N'CASHIER''S OFFICE', N'CASHIER''S OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (40, N'COLLEGE OF COMPUTER STUDIES', N'COLLEGE OF COMPUTER STUDIES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (41, N'COLLEGE OF ENGINEERING AND ARCHITECTURE', N'COLLEGE OF ENGINEERING AND ARCHITECTURE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (42, N'COLLEGE OF INDUSTRIAL EDUCATION', N'COLLEGE OF INDUSTRIAL EDUCATION', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (43, N'COLLEGE OF INDUSTRIAL TECHNOLOGY', N'COLLEGE OF INDUSTRIAL TECHNOLOGY', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (44, N'COMPUTER SCIENCE PROGRAM', N'COMPUTER SCIENCE PROGRAM', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (45, N'ENVIRONMENTAL SCIENCE PROGRAM', N'ENVIRONMENTAL SCIENCE PROGRAM', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (46, N'FINANCE SERVICES', N'FINANCE SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (47, N'GENERAL SERVICES', N'GENERAL SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (48, N'GUIDANCE OFFICE', N'GUIDANCE OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (49, N'HEALTH SERVICES', N'HEALTH SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (50, N'HOSPITALITY MANAGEMENT PROGRAM', N'HOSPITALITY MANAGEMENT PROGRAM', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (51, N'HOTEL MANAGEMENT PROGRAM', N'HOTEL MANAGEMENT PROGRAM', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (52, N'HRMO', N'HRMO', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (53, N'LIBRARY AND INFORMATION SERVICES', N'LIBRARY AND INFORMATION SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (54, N'LIBRARY SERVICES', N'LIBRARY SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (55, N'OFFICE OF THE CAMPUS DIRECTOR', N'OFFICE OF THE CAMPUS DIRECTOR', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (56, N'PHYSICAL PLANT DEVELOPMENT SERVICES', N'PHYSICAL PLANT DEVELOPMENT SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (57, N'REGISTRAR''S OFFICE', N'REGISTRAR''S OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (58, N'RESEARCH AND EXTENSION SERVICES', N'RESEARCH AND EXTENSION SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (59, N'SAFETY AND SECURITY SERVICES', N'SAFETY AND SECURITY SERVICES', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (60, N'SUPPLY OFFICE', N'SUPPLY OFFICE', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        INSERT [dbo].[departments] ([id], [code], [name], [functionality], [is_academic], [created_at], [updated_at], [active], [employee_id], [branch_id]) VALUES (61, N'TOURISM PROGRAM', N'TOURISM PROGRAM', NULL, 0, CAST(N'2024-11-29T12:47:55.190' AS DateTime), CAST(N'2024-11-29T12:47:55.190' AS DateTime), 1, 0, 1)
        
        SET IDENTITY_INSERT [dbo].[departments] OFF

        TRUNCATE TABLE blood_types
        
        SET IDENTITY_INSERT [dbo].[blood_types] ON 
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (1, N'A', N'A', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (2, N'A RH+', N'A RH+', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (3, N'A+', N'A+', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (4, N'AB', N'AB', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (5, N'AB+', N'AB+', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (6, N'B', N'B', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (7, N'B RH+', N'B RH+', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (8, N'B+', N'B+', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (9, N'O', N'O', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        INSERT [dbo].[blood_types] ([id], [code], [name], [active], [created_at], [updated_at]) VALUES (10, N'O+', N'O+', 1, CAST(N'2024-11-29T13:38:52.927' AS DateTime), CAST(N'2024-11-29T13:38:52.927' AS DateTime))
        
        SET IDENTITY_INSERT [dbo].[blood_types] OFF

        TRUNCATE TABLE citizenships
        
        SET IDENTITY_INSERT [dbo].[citizenships] ON 
        
        INSERT [dbo].[citizenships] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (1, N'Filipino', 1, CAST(N'2024-11-29T12:18:20.217' AS DateTime), CAST(N'2024-11-29T12:18:20.217' AS DateTime), NULL)
        
        SET IDENTITY_INSERT [dbo].[citizenships] OFF

        TRUNCATE TABLE genders
        
        SET IDENTITY_INSERT [dbo].[genders] ON 
        
        INSERT [dbo].[genders] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Female', 1, CAST(N'2024-11-29T12:13:11.103' AS DateTime), CAST(N'2024-11-29T12:13:11.103' AS DateTime))
        
        INSERT [dbo].[genders] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Male', 1, CAST(N'2024-11-29T12:13:11.103' AS DateTime), CAST(N'2024-11-29T12:13:11.103' AS DateTime))
        
        SET IDENTITY_INSERT [dbo].[genders] OFF

        TRUNCATE TABLE name_prefixes
        
        SET IDENTITY_INSERT [dbo].[name_prefixes] ON 
        
        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (1, N'Ar.', 1, CAST(N'2024-11-29T12:04:01.647' AS DateTime), CAST(N'2024-11-29T12:04:01.647' AS DateTime), NULL)
        
        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (2, N'Atty.', 1, CAST(N'2024-11-29T12:04:01.647' AS DateTime), CAST(N'2024-11-29T12:04:01.647' AS DateTime), NULL)
        
        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (3, N'Dr.', 1, CAST(N'2024-11-29T12:04:01.647' AS DateTime), CAST(N'2024-11-29T12:04:01.647' AS DateTime), NULL)
        
        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (4, N'Engr.', 1, CAST(N'2024-11-29T12:04:01.647' AS DateTime), CAST(N'2024-11-29T12:04:01.647' AS DateTime), NULL)
        
        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (5, N'Mr.', 1, CAST(N'2024-11-29T12:04:01.647' AS DateTime), CAST(N'2024-11-29T12:04:01.647' AS DateTime), NULL)
        
        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (6, N'Mrs.', 1, CAST(N'2024-11-29T12:04:01.647' AS DateTime), CAST(N'2024-11-29T12:04:01.647' AS DateTime), NULL)
        
        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (7, N'Ms.', 1, CAST(N'2024-11-29T12:04:01.647' AS DateTime), CAST(N'2024-11-29T12:04:01.647' AS DateTime), NULL)
        
        INSERT [dbo].[name_prefixes] ([id], [name], [active], [created_at], [updated_at], [code]) VALUES (8, N'Prof.', 1, CAST(N'2024-11-29T12:04:01.647' AS DateTime), CAST(N'2024-11-29T12:04:01.647' AS DateTime), NULL)
        
        SET IDENTITY_INSERT [dbo].[name_prefixes] OFF

        TRUNCATE TABLE name_suffixes
        
        SET IDENTITY_INSERT [dbo].[name_suffixes] ON 
        
        INSERT [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'II', 1, CAST(N'2024-11-29T12:09:46.027' AS DateTime), CAST(N'2024-11-29T12:09:46.027' AS DateTime))
        
        INSERT [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'III', 1, CAST(N'2024-11-29T12:09:46.027' AS DateTime), CAST(N'2024-11-29T12:09:46.027' AS DateTime))
        
        INSERT [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (3, N'Jr.', 1, CAST(N'2024-11-29T12:09:46.027' AS DateTime), CAST(N'2024-11-29T12:09:46.027' AS DateTime))
        
        INSERT [dbo].[name_suffixes] ([id], [name], [active], [created_at], [updated_at]) VALUES (4, N'Sr.', 1, CAST(N'2024-11-29T12:09:46.027' AS DateTime), CAST(N'2024-11-29T12:09:46.027' AS DateTime))
        
        SET IDENTITY_INSERT [dbo].[name_suffixes] OFF

        TRUNCATE TABLE positions
        
        SET IDENTITY_INSERT [dbo].[positions] ON 
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (1, N'ACCOUNTANT I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (2, N'ACCOUNTANT II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (3, N'ACCOUNTANT III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (4, N'ACCOUNTING CLERK', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (5, N'ACCOUNTING OFFICE', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (6, N'ADM. OFFICER V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (7, N'ADMIN AIDE IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (8, N'ADMIN AIDE VI', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (9, N'ADMIN. AIDE I (UW I)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (10, N'ADMIN. AIDE VI (MECHANIC II)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (11, N'ADMIN. ASSISTANT V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (12, N'ADMIN. OFFICER V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (13, N'ADMINISTRATIVE AIDE I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (14, N'ADMINISTRATIVE AIDE I (UTILITY)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (15, N'ADMINISTRATIVE AIDE III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (16, N'ADMINISTRATIVE AIDE III (CLERK I)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (17, N'ADMINISTRATIVE AIDE III (DRIVER I)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (18, N'ADMINISTRATIVE AIDE III (DRIVER)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (19, N'ADMINISTRATIVE AIDE III (UTILITY WORKER II)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (20, N'ADMINISTRATIVE AIDE IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (21, N'ADMINISTRATIVE AIDE IV (CLERK II)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (22, N'ADMINISTRATIVE AIDE VI', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (23, N'ADMINISTRATIVE ASSISTANT II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (24, N'ADMINISTRATIVE ASSISTANT II (BUDGETING ASSISTANT)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (25, N'ADMINISTRATIVE ASSISTANT II (HRMA)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (26, N'ADMINISTRATIVE ASSISTANT III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (27, N'ADMINISTRATIVE ASSISTANT III (SENIOR BOOKKEEPER)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (28, N'ADMINISTRATIVE ASSSISTANT V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (29, N'ADMINISTRATIVE OFFICER I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (30, N'ADMINISTRATIVE OFFICER I (RECORDS OFFICER I)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (31, N'ADMINISTRATIVE OFFICER I (SUPPLY OFFICER I)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (32, N'ADMINISTRATIVE OFFICER I (SUPPLY OFFICER)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (33, N'ADMINISTRATIVE OFFICER I (SUPPLY)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (34, N'ADMINISTRATIVE OFFICER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (35, N'ADMINISTRATIVE OFFICER II (HRMO I)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (36, N'ADMINISTRATIVE OFFICER III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (37, N'ADMINISTRATIVE OFFICER III (CASHIER II)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (38, N'ADMINISTRATIVE OFFICER III/CASHIER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (39, N'ADMINISTRATIVE OFFICER IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (40, N'ADMINISTRATIVE OFFICER IV (BUDGET OFFICER II)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (41, N'ADMINISTRATIVE OFFICER IV (HRMO II)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (42, N'ADMINISTRATIVE OFFICER V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (43, N'ADMINISTRTIVE AIDE III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (44, N'AGRIC''L TECH II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (45, N'AGRICTECH-JO', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (46, N'AGRICULTURAL TECHNICIAN II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (47, N'AQUACULTURIST I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (48, N'ASSISTAN PROFESSOR I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (49, N'ASSISTAN PROFESSOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (50, N'ASSISTANT  PROFESSOR III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (51, N'ASSISTANT PROFESSOR I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (52, N'ASSISTANT PROFESSOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (53, N'ASSISTANT PROFESSOR III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (54, N'ASSISTANT PROFESSOR IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (55, N'ASSO. PROF. III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (56, N'ASSO. PROF. V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (57, N'ASSOCIATE  PROFESSOR I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (58, N'ASSOCIATE  PROFESSOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (59, N'ASSOCIATE  PROFESSOR IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (60, N'ASSOCIATE  PROFESSOR V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (61, N'ASSOCIATE PROFESOR V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (62, N'ASSOCIATE PROFESSO II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (63, N'ASSOCIATE PROFESSOR I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (64, N'ASSOCIATE PROFESSOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (65, N'ASSOCIATE PROFESSOR III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (66, N'ASSOCIATE PROFESSOR IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (67, N'ASSOCIATE PROFESSOR V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (68, N'ASST.  PROF. IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (69, N'ASST. PROF. I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (70, N'ASST. PROF. II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (71, N'ASST. PROF. III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (72, N'ASST. PROF. IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (73, N'ASST. PROF. V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (74, N'ATTORNEY IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (75, N'BOARD SEC OFFICE', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (76, N'BUDGET OFFICE', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (77, N'BUDGET OFFICER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (78, N'BUDGET OFFICER III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (79, N'BUDGETING ASSISTANT', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (80, N'CARPENTER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (81, N'CASH CLERK I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (82, N'CASHIER III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (83, N'CASUAL', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (84, N'CASUAL TEACHER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (85, N'CHIEF ADMIN. OFFICER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (86, N'CHIEF ADMINISTRATIVE OFFICER (FINANCE)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (87, N'CKERK', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (88, N'CLERK', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (89, N'CLERK 1 (CASUAL)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (90, N'CLERK I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (91, N'CLERK I (CASUAL)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (92, N'CLERK I CASUAL', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (93, N'CLERK II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (94, N'CLERK III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (95, N'CLERK WITH PLANTILLA', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (96, N'CLERK-JO', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (97, N'CLINICAL INSTRUCTOR', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (98, N'COA-AUDITOR', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (99, N'COLL. LIB. I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (100, N'COLLEGE L. III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (101, N'COLLEGE LIBRARIAN I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (102, N'COLLEGE LIBRARIAN II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (103, N'COLLEGE LIBRARIAN III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (104, N'COOK I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (105, N'COS INSTRUCTOR', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (106, N'COS-INSTRUCTOR', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (107, N'CS', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (108, N'CS-INSTRUCTOR', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (109, N'DEMO I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (110, N'DENTIST II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (111, N'DORMITORY MANAGER I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (112, N'DRAFTSMAN', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (113, N'DRIVER (CASUAL)', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (114, N'DRIVER I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (115, N'DRIVER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (116, N'DRIVER/UTILITY', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (117, N'DUPLICATE MACHINE OPTR. I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (118, N'ELECTRICIAN II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (119, N'EMERGENCY', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (120, N'EMERGENCY CLERK', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (121, N'EMERGENCY EMPLOYEE', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (122, N'EMERGENCY LABORER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (123, N'EMERGENCY TEACHER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (124, N'EMERGENCY TEACHER I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (125, N'ENGINEER-JO', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (126, N'F. WORKER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (127, N'FARM WORKER I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (128, N'FARM WORKER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (129, N'FIELD ASST.', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (130, N'FINANCE OFFICE', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (131, N'FISHERMAN', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (132, N'GUIDANCE COUNSELLOR I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (133, N'GUIDANCE COUNSELOR', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (134, N'GUIDANCE COUNSELOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (135, N'GUIDANCE COUNSELOR III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (136, N'GUIDANCECOUNSELOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (137, N'H. EQUIPMENT OPERATOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (138, N'HRMO III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (139, N'INFO SYSTEMS ANAYST II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (140, N'INFO TECH OFFICER I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (141, N'INFO. SYSTEMS ANALYST I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (142, N'INFORMATION OFFICER III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (143, N'INSTRUCTION I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (144, N'INSTRUCTOR', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (145, N'INSTRUCTOR I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (146, N'INSTRUCTOR I-CS', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (147, N'INSTRUCTOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (148, N'INSTRUCTOR III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (149, N'INSTRUCTOR-CIT', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (150, N'INSTRUCTOR-CS', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (151, N'INSTRUCTORIII', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (152, N'INSTRUCTOR-SMS', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (153, N'INSTRUTOR-CS', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (154, N'INTERNAL AUDITOR IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (155, N'INTRUCTOR I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (156, N'LABORER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (157, N'LABORER-JO', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (158, N'MASTER FISHERMAN I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (159, N'MEDICAL OFFICER IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (160, N'NURSE', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (161, N'NURSE II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (162, N'NURSE III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (163, N'NURSE-CIT', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (164, N'PLANNING OFFICER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (165, N'PRESIDENT', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (166, N'PROFESSOR II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (167, N'PROFESSOR III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (168, N'PROFESSOR IV', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (169, N'PROFESSOR V', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (170, N'PROFESSOR VI', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (171, N'PROJECT DEVEV''T. OFFICER III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (172, N'RECORDS OFFICER III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (173, N'REGISTRAR III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (174, N'RESEARCH ASST-CONTRACT', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (175, N'S. GUARD II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (176, N'SAS', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (177, N'SCIE RESEARCH ASST', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (178, N'SCIE. RESEARCH SPECIALIST', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (179, N'SCIENCE RESEARCH ASST', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (180, N'SE. SCH. TEACHER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (181, N'SEC. SCHOOL TEACHER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (182, N'SECONDARY SCHOOL TEACHER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (183, N'SECURITY GUARD I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (184, N'SECURITY GUARD II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (185, N'SECURITY GUARDE II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (186, N'SENIOR BOOKKEEPER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (187, N'SUC PRESIDENT', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (188, N'SUC PRESIDENT III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (189, N'SUPERVISING ADMIN OFFICER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (190, N'SUPERVISING ADMIN. OFFICER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (191, N'SUPPLY OFFICER III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (192, N'TEACHER', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (193, N'TEACHER I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (194, N'TEACHER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (195, N'TEACHER III', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (196, N'TEC', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (197, N'U. WORKER I', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (198, N'U. WORKER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (199, N'UNIVERSITY PHYSICIAN', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (200, N'UTILITY', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        INSERT [dbo].[positions] ([id], [name], [active], [created_at], [updated_at], [description], [is_administrative_position], [code]) VALUES (201, N'UTILITY WORKER II', 1, CAST(N'2024-11-29T12:55:42.290' AS DateTime), CAST(N'2024-11-29T12:55:42.290' AS DateTime), NULL, 0, NULL)
        
        SET IDENTITY_INSERT [dbo].[positions] OFF

        TRUNCATE TABLE religions
        
        SET IDENTITY_INSERT [dbo].[religions] ON 
        
        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (1, N'Assembly of d', 1, CAST(N'2024-11-29T12:36:27.530' AS DateTime), CAST(N'2024-11-29T12:36:27.530' AS DateTime))
        
        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (2, N'Baptist', 1, CAST(N'2024-11-29T12:36:27.530' AS DateTime), CAST(N'2024-11-29T12:36:27.530' AS DateTime))
        
        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (3, N'Born Again Christian', 1, CAST(N'2024-11-29T12:36:27.530' AS DateTime), CAST(N'2024-11-29T12:36:27.530' AS DateTime))
        
        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (4, N'Iglesia ni Cristo', 1, CAST(N'2024-11-29T12:36:27.530' AS DateTime), CAST(N'2024-11-29T12:36:27.530' AS DateTime))
        
        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (5, N'Mormons', 1, CAST(N'2024-11-29T12:36:27.530' AS DateTime), CAST(N'2024-11-29T12:36:27.530' AS DateTime))
        
        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (6, N'Roman Catholic', 1, CAST(N'2024-11-29T12:36:27.530' AS DateTime), CAST(N'2024-11-29T12:36:27.530' AS DateTime))
        
        INSERT [dbo].[religions] ([id], [name], [active], [created_at], [updated_at]) VALUES (7, N'Seventh Day Adventist', 1, CAST(N'2024-11-29T12:36:27.530' AS DateTime), CAST(N'2024-11-29T12:36:27.530' AS DateTime))
        
        SET IDENTITY_INSERT [dbo].[religions] OFF
        
        ");
    }
}
