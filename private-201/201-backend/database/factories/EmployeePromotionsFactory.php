<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Employee;
use App\EmployeePromotion;
use App\Position;
use Faker\Generator as Faker;

$factory->define(EmployeePromotion::class, function (Faker $faker) {

    $employee_id = $faker->randomElement(Employee::all('id'));
    $employee = Employee::find($employee_id);

    return [
        'employee_id' => $employee_id,
        'position_id' => $faker->randomElement(Position::all('id')),
        'plantilla_id' => 0,
        'is_plantilla' => false,
        'is_teaching' => $faker->boolean(30),
        'nature_of_appointment_id' => 1,
        'employment_type_id' => 2,
        'department_id' => 1, //$employee[0]->department_id,
        'branch_id' => $employee[0]->branch_id,
        'payroll_interval_id' => $employee[0]->payroll_interval_id,
        'old_salary' => $employee[0]->salary,
        'old_tax_amount' => $employee[0]->tax_amount,
        'old_gsis_amount' => $employee[0]->gsis_amount,
        'old_sss_amount' => $employee[0]->sss_amount,
        'old_pagibig_amount' => $employee[0]->pagibig_amount,
        'old_philhealth_amount' => $employee[0]->philhealth_amount,
        'new_salary' => $employee[0]->salary + 1000,
        'new_tax_amount' => $employee[0]->tax_amount + 100,
        'new_gsis_amount' => $employee[0]->gsis_amount + 100,
        'new_sss_amount' => $employee[0]->sss_amount + 100,
        'new_pagibig_amount' => $employee[0]->pagibig_amount + 100,
        'new_philhealth_amount' => $employee[0]->philhealth_amount + 100,
        'date_position_appointed' => $faker->date(),
        'date_of_effectivity' => $faker->date()
    ];
});