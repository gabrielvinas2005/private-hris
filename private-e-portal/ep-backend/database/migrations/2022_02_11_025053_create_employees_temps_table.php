<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees_temps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('request_id');
            $table->integer('employee_id');
            $table->string('photo')->default('');
            $table->string('employee_no');
            $table->string('access_no')->nullable();
            $table->integer('name_prefix_id')->default(0);
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->integer('name_suffix_id')->default(0);
            $table->string('birth_place')->nullable();
            $table->date('birthdate');
            $table->integer('age')->default(0);
            $table->integer('gender_id')->default(0);
            $table->decimal('height')->default(0)->nullable();
            $table->decimal('weight')->default(0)->nullable();
            $table->string('blood_type')->nullable();
            $table->string('email')->unique();
            $table->string('mobile_no')->nullable();
            $table->string('telephone_no')->nullable();
            $table->integer('citizenship_id')->default(0);
            $table->integer('civil_status_id')->default(0);
            $table->integer('religion_id')->default(0);
            $table->boolean('is_dual_citizent')->default(0);
            $table->boolean('by_birth')->default(0);
            $table->boolean('by_naturalization')->default(0);
            $table->string('indicate_country')->nullable();
            $table->integer('ra_postal_id')->default(0);
            $table->string('ra_house_no')->nullable();
            $table->string('ra_barangay')->nullable();
            $table->string('ra_street')->nullable();
            $table->string('ra_village')->nullable();
            $table->integer('pa_postal_id')->default(0);
            $table->string('pa_house_no')->nullable();
            $table->string('pa_barangay')->nullable();
            $table->string('pa_street')->nullable();
            $table->string('pa_village')->nullable();
            $table->integer('father_name_prefix_id')->default(0)->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_middle_name')->nullable();
            $table->string('father_last_name')->nullable();
            $table->integer('father_name_suffix_id')->default(0)->nullable();
            $table->integer('mother_name_prefix_id')->default(0)->nullable();
            $table->string('mother_first_name')->nullable();
            $table->string('mother_middle_name')->nullable();
            $table->string('mother_last_name')->nullable();
            $table->integer('mother_name_suffix_id')->default(0)->nullable();
            $table->integer('spouse_name_prefix_id')->default(0)->nullable();
            $table->string('spouse_first_name')->nullable();
            $table->string('spouse_middle_name')->nullable();
            $table->string('spouse_last_name')->nullable();
            $table->integer('spouse_name_suffix_id')->default(0)->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->string('spouse_employer')->nullable();
            $table->string('spouse_business_address')->nullable();
            $table->integer('company_id')->default(0);
            $table->integer('branch_id')->default(0);
            $table->integer('department_id')->default(0);
            $table->integer('work_schedule_id')->default(0);
            $table->integer('employment_type_id')->default(0);
            $table->integer('position_id')->default(0)->nullable();
            $table->integer('plantilla_id')->default(0)->nullable();
            $table->boolean('is_shifting')->default(0)->nullable();
            $table->boolean('is_plantilla')->default(0)->nullable();
            $table->boolean('is_employee')->default(0)->nullable();
            $table->boolean('is_teaching')->default(0)->nullable();
            $table->date('date_hired')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('gsis_no')->nullable();
            $table->string('sss_no')->nullable();
            $table->string('pagibig_no')->nullable();
            $table->string('philhealth_no')->nullable();
            $table->decimal('salary')->default(0)->nullable();
            $table->decimal('tax_amount')->default(0)->nullable();
            $table->decimal('gsis_amount')->default(0)->nullable();
            $table->decimal('sss_amount')->default(0)->nullable();
            $table->decimal('pagibig_amount')->default(0)->nullable();
            $table->decimal('philhealth_amount')->default(0)->nullable();
            $table->integer('payroll_interval_id')->default(0)->nullable();
            $table->boolean('active')->default(0);
            $table->integer('blood_type_id')->default(0)->nullable();
            $table->string('ra_region')->nullable();
            $table->string('ra_province')->nullable();
            $table->string('ra_city')->nullable();
            $table->string('pa_region')->nullable();
            $table->string('pa_province')->nullable();
            $table->string('pa_city')->nullable();
            $table->string('ra_region_name')->nullable();
            $table->string('ra_province_name')->nullable();
            $table->string('ra_city_name')->nullable();
            $table->string('pa_region_name')->nullable();
            $table->string('pa_province_name')->nullable();
            $table->string('pa_city_name')->nullable();
            $table->integer('salary_grade_id')->default(0)->nullable();
            $table->integer('salary_step_id')->default(0)->nullable();
            $table->date('end_date')->nullable();
            $table->date('date_applied')->nullable();
            $table->integer('application_status_id')->nullable();
            $table->integer('position_applied_id')->nullable();
            $table->integer('division_id')->default(0)->nullable();
            $table->integer('section_id')->default(0)->nullable();
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
        Schema::dropIfExists('employees_temps');
    }
}
