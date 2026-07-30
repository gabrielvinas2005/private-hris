<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use App\Audit;
use App\Menus;
use App\User;

$factory->define(Audit::class, function (Faker $faker) {
    return [
        'user_id' => $faker->randomElement([1, 2, 3, 4, 5, 6]),
        'module' => $faker->randomElement(['Control Panel', 'HR Module', 'Timekeeping', 'Payroll']),
        'menu' => $faker->randomElement(['Employee Records', 'Leave', 'Overtime']),
        'activity' => $faker->randomElement(['Approved', 'Add', 'Update', 'Delete']),
        'description' => $faker->text(),
    ];
});
