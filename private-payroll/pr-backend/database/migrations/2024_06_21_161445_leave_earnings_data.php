<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeaveEarningsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            TRUNCATE TABLE leave_earnings;

            SET IDENTITY_INSERT [dbo].[leave_earnings] ON 

            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (1, CAST(1.00 AS Decimal(18, 2)), CAST(0.042 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (2, CAST(2.00 AS Decimal(18, 2)), CAST(0.083 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (3, CAST(3.00 AS Decimal(18, 2)), CAST(0.125 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (4, CAST(4.00 AS Decimal(18, 2)), CAST(0.167 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (5, CAST(5.00 AS Decimal(18, 2)), CAST(0.208 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (6, CAST(6.00 AS Decimal(18, 2)), CAST(0.250 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (7, CAST(7.00 AS Decimal(18, 2)), CAST(0.292 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (8, CAST(8.00 AS Decimal(18, 2)), CAST(0.333 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (9, CAST(9.00 AS Decimal(18, 2)), CAST(0.375 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (10, CAST(10.00 AS Decimal(18, 2)), CAST(0.417 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (11, CAST(11.00 AS Decimal(18, 2)), CAST(0.458 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (12, CAST(12.00 AS Decimal(18, 2)), CAST(0.500 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (13, CAST(13.00 AS Decimal(18, 2)), CAST(0.542 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (14, CAST(14.00 AS Decimal(18, 2)), CAST(0.583 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (15, CAST(15.00 AS Decimal(18, 2)), CAST(0.625 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (16, CAST(16.00 AS Decimal(18, 2)), CAST(0.667 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (17, CAST(17.00 AS Decimal(18, 2)), CAST(0.708 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (18, CAST(18.00 AS Decimal(18, 2)), CAST(0.750 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (19, CAST(19.00 AS Decimal(18, 2)), CAST(0.792 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (20, CAST(20.00 AS Decimal(18, 2)), CAST(0.833 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (21, CAST(21.00 AS Decimal(18, 2)), CAST(0.875 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (22, CAST(22.00 AS Decimal(18, 2)), CAST(0.917 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (23, CAST(23.00 AS Decimal(18, 2)), CAST(0.958 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (24, CAST(24.00 AS Decimal(18, 2)), CAST(1.000 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (25, CAST(25.00 AS Decimal(18, 2)), CAST(1.042 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (26, CAST(26.00 AS Decimal(18, 2)), CAST(1.083 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (27, CAST(27.00 AS Decimal(18, 2)), CAST(1.125 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (28, CAST(28.00 AS Decimal(18, 2)), CAST(1.167 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (29, CAST(29.00 AS Decimal(18, 2)), CAST(1.208 AS Decimal(18, 3)), NULL, NULL)
            INSERT [dbo].[leave_earnings] ([id], [days_present], [leave_earned], [created_at], [updated_at]) VALUES (30, CAST(30.00 AS Decimal(18, 2)), CAST(1.250 AS Decimal(18, 3)), NULL, NULL)
            SET IDENTITY_INSERT [dbo].[leave_earnings] OFF
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
