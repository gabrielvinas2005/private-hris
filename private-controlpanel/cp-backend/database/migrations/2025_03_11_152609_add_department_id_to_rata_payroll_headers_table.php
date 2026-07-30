<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentIdToRataPayrollHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rata_payroll_headers', function (Blueprint $table) {
            $table->integer('department_id')->default(0)->nullable();
            $table->integer('rata_type_id')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rata_payroll_headers', function (Blueprint $table) {
            $table->dropColumn(['department_id', 'rata_type_id']);
        });
    }
}
