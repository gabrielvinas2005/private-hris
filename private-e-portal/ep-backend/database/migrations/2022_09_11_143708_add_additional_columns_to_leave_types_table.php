<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalColumnsToLeaveTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->integer('accrued_id')->default(0)->nullable();
            $table->decimal('accrual_amount')->nullable();
            $table->integer('accrual_frequency_id')->default(0)->nullable();
            $table->integer('leave_balance_policy_id')->default(0)->nullable();
            $table->integer('is_editable_id')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            //
        });
    }
}
