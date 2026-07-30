<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToLoanApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->boolean('reconstructed')->default(0)->nullable();
            $table->boolean('active')->default(0)->nullable();
            $table->date('reconstructed_date')->nullable();
            $table->integer('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn('reconstructed');
            $table->dropColumn('active');
            $table->dropColumn('reconstructed_date');
            $table->dropColumn('user_id');
        });
    }
}
