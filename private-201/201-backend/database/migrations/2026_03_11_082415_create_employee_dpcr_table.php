<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeDpcrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_dpcr', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('department');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('approved_by')->nullable();
            $table->unsignedBigInteger('approved_by_employee_id');
            $table->date('approved_date');
            $table->string('assessed_by');
            $table->unsignedBigInteger('assessed_by_employee_id');
            $table->date('assessed_date');
            $table->string('final_rater');
            $table->unsignedBigInteger('final_rater_employee_id');
            $table->date('final_rater_date');
            $table->unsignedBigInteger('planning_officer_id');
            $table->date('planning_officer_date');
            $table->date('created_at')->useCurrent();
            $table->date('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_dpcr');
    }
}
