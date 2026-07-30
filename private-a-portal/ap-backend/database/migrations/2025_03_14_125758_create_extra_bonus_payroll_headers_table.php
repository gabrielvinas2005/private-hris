<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExtraBonusPayrollHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extra_bonus_payroll_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('extra_bonus_type_id')->default(0);
            $table->integer('department_id')->default(0);
            $table->integer('year_id')->default(0);
            $table->boolean('is_posted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('extra_bonus_payroll_headers');
    }
}
