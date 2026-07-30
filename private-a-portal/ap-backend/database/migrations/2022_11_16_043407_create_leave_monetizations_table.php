<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveMonetizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_monetizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id');
            $table->decimal('vl_credit', 18, 3)->nullable();
            $table->decimal('sl_credit', 18, 3)->nullable();
            $table->decimal('total_days', 18, 3)->nullable();
            $table->decimal('cf_rate', 18, 3)->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->integer('approve_1_id')->nullable();
            $table->boolean('approve_1')->nullable();
            $table->dateTime('approve_date_1')->nullable();
            $table->text('approve_1_remarks')->nullable();
            $table->integer('disapprove_1_id')->nullable();
            $table->boolean('disapprove_1')->nullable();
            $table->dateTime('disapprove_date_1')->nullable();
            $table->text('disapprove_1_remarks')->nullable();
            $table->boolean('is_processed')->nullable();
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
        Schema::dropIfExists('leave_monetizations');
    }
}
