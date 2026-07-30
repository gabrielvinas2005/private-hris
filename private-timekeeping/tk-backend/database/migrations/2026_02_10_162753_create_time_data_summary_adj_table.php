<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_data_summary_adj', function (Blueprint $table) {
            $table->id('Time_Data_Summary_Adj_ID');
            $table->integer('Payroll_Period_ID')->nullable();
            $table->integer('Employee_ID')->nullable();
            $table->decimal('Hours_Worked', 18, 2)->default(0.00);
            $table->decimal('Daily', 18, 2)->default(0.00);
            $table->decimal('Late', 18, 2)->default(0.00);
            $table->decimal('Late_Amount', 18, 2)->default(0.00);
            $table->decimal('Undertime', 18, 2)->default(0.00);
            $table->decimal('Undertime_Amount', 18, 2)->default(0.00);
            $table->decimal('Absent', 18, 2)->default(0.00);
            $table->decimal('Absent_Amount', 18, 2)->default(0.00);
            $table->decimal('Total_Amount', 18, 2)->default(0.00);
            $table->decimal('Overtime', 18, 2)->default(0.00);
            $table->tinyInteger('Active')->default(1);
            $table->datetime('Date_Stamp')->nullable();
            $table->integer('Encoder_ID')->default(1);
            $table->tinyInteger('Approved')->default(0);
            $table->tinyInteger('Applied')->default(0);
            $table->integer('Reference_ID')->nullable();
            $table->decimal('Working_Hours', 18, 2)->default(0.00);
            $table->decimal('Work_Hours', 18, 2)->default(0.00);
            $table->tinyInteger('Is_Edited')->default(0);
            $table->tinyInteger('Is_Offset')->default(0);
            $table->decimal('Days_Covered', 18, 2)->default(0.00);
            $table->integer('Days_Present')->default(0);
            $table->decimal('Total_Deduction', 18, 2)->default(0.00);

            // Additional fields for adjustment tracking
            $table->integer('Source_Summary_ID')->nullable()->comment('Links to original time_data_summary record');
            $table->integer('Preceding_Payroll_Period_ID')->nullable()->comment('The preceding period this adjustment comes from');
            $table->string('Adjustment_Type', 50)->default('BIOMETRIC')->comment('BIOMETRIC, MANUAL, RECONCILIATION');
            $table->string('Status', 20)->default('PENDING')->comment('PENDING, APPLIED, REJECTED');
            $table->text('Reason')->nullable();
            $table->integer('Created_By')->default(1);
            $table->datetime('Created_At')->nullable();
            $table->datetime('Updated_At')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_data_summary_adj');
    }
};
