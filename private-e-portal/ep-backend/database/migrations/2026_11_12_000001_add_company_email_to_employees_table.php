<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyEmailToEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('employees') && !Schema::hasColumn('employees', 'company_email')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('company_email')->nullable()->after('email');
            });
        }

        if (Schema::hasTable('employees_temps') && !Schema::hasColumn('employees_temps', 'company_email')) {
            Schema::table('employees_temps', function (Blueprint $table) {
                $table->string('company_email')->nullable()->after('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'company_email')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('company_email');
            });
        }

        if (Schema::hasTable('employees_temps') && Schema::hasColumn('employees_temps', 'company_email')) {
            Schema::table('employees_temps', function (Blueprint $table) {
                $table->dropColumn('company_email');
            });
        }
    }
}
