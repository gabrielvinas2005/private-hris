<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\BloodType;
use App\Branch;
use App\Citizenship;
use App\CivilStatus;
use App\Department;
use App\Employee;
use App\EmploymentType;
use App\Gender;
use App\NamePrefix;
use App\Position;
use App\Religion;
use App\SalaryGrade;
use App\SalaryStep;
use Faker\Generator as Faker;

$factory->define(Employee::class, function (Faker $faker) {
    return [
        'photo' => null,
        'employee_no' => $faker->unique()->numerify('EMP###'),
        'access_no' => $faker->unique()->numerify('ACC###'),
        'name_prefix_id' => $faker->randomElement(NamePrefix::all('id')), // Example: 1 = Mr., 2 = Ms., 3 = Dr.
        'first_name' => $faker->firstName,
        'middle_name' => $faker->lastName,
        'last_name' => $faker->lastName,
        'name_suffix_id' => $faker->randomElement([0, 1, 2, 3]), // Example: 1 = Jr., 2 = Sr.
        'birth_place' => $faker->city,
        'birthdate' => $faker->date,
        'age' => $faker->numberBetween(18, 65),
        'gender_id' => $faker->randomElement(Gender::all('id')), // Example: 1 = Male, 2 = Female
        'height' => $faker->numberBetween(150, 200), // in cm
        'weight' => $faker->numberBetween(50, 100), // in kg
        'email' => $faker->unique()->safeEmail,
        'mobile_no' => $faker->phoneNumber,
        'telephone_no' => $faker->phoneNumber,
        'citizenship_id' => $faker->randomElement(Citizenship::all('id')), // Example IDs
        'civil_status_id' => $faker->randomElement(CivilStatus::all('id')), // Example: 1 = Single, 2 = Married
        'religion_id' => $faker->randomElement(Religion::all('id')), // Example IDs
        'is_dual_citizent' => false,
        'by_birth' => false,
        'by_naturalization' => false,
        'indicate_country' => null,
        'ra_postal_id' => 0,
        'ra_house_no' => $faker->buildingNumber,
        'ra_barangay' => null,
        'ra_street' => $faker->streetAddress,
        'ra_village' => $faker->secondaryAddress,
        'pa_postal_id' => 0,
        'pa_house_no' => $faker->buildingNumber,
        'pa_barangay' => null,
        'pa_street' => $faker->streetAddress,
        'pa_village' => $faker->secondaryAddress,
        'father_name_prefix_id' => $faker->randomElement([1, 2, 3]),
        'father_first_name' => $faker->firstNameMale,
        'father_middle_name' => $faker->lastName,
        'father_last_name' => $faker->lastName,
        'father_name_suffix_id' => $faker->randomElement([0, 1, 2]),
        'mother_name_prefix_id' => $faker->randomElement([1, 2, 3]),
        'mother_first_name' => $faker->firstNameFemale,
        'mother_middle_name' => $faker->lastName,
        'mother_last_name' => $faker->lastName,
        'mother_name_suffix_id' => $faker->randomElement([0, 1, 2]),
        'spouse_name_prefix_id' => $faker->randomElement([0, 1, 2, 3]),
        'spouse_first_name' => $faker->firstName,
        'spouse_middle_name' => $faker->lastName,
        'spouse_last_name' => $faker->lastName,
        'spouse_name_suffix_id' => $faker->randomElement([0, 1, 2]),
        'spouse_occupation' => $faker->jobTitle,
        'spouse_employer' => $faker->company,
        'spouse_business_address' => $faker->address,
        'company_id' => 1,
        'branch_id' => $faker->randomElement(Branch::all('id')), // Example IDs
        'department_id' => $faker->randomElement(Department::all('id')), // Example IDs
        'work_schedule_id' => 0,
        'employment_type_id' => $faker->randomElement(EmploymentType::all('id')), // Example IDs
        'position_id' => $faker->randomElement(Position::all('id')), // Example IDs
        'plantilla_id' => 0,
        'is_shifting' => false,
        'is_plantilla' => false,
        'is_employee' => true,
        'is_teaching' => false,
        'date_hired' => $faker->date,
        'tin_no' => $faker->numerify('#########'),
        'gsis_no' => $faker->numerify('#########'),
        'sss_no' => $faker->numerify('#########'),
        'pagibig_no' => $faker->numerify('#########'),
        'philhealth_no' => $faker->numerify('#########'),
        'salary' => $faker->numberBetween(20000, 100000),
        'tax_amount' => $faker->randomFloat(2, 1000, 5000),
        'gsis_amount' => $faker->randomFloat(2, 500, 1000),
        'sss_amount' => $faker->randomFloat(2, 500, 1000),
        'pagibig_amount' => $faker->randomFloat(2, 500, 1000),
        'philhealth_amount' => $faker->randomFloat(2, 500, 1000),
        'payroll_interval_id' => 0,
        'active' => true,
        'blood_type_id' => $faker->randomElement(BloodType::all('id')),
        'ra_region' => null,
        'ra_province' => null,
        'ra_city' => null,
        'pa_region' => null,
        'pa_province' => null,
        'pa_city' => null,
        'salary_grade_id' => $faker->randomElement(SalaryGrade::all('id')),
        'salary_step_id' => $faker->randomElement(SalaryStep::all('id')),
        'ra_region_name' => null,
        'ra_province_name' => null,
        'ra_city_name' => null,
        'pa_region_name' => null,
        'pa_province_name' => null,
        'pa_city_name' => null,
    ];
});
