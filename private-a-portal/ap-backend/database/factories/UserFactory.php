<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\User;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->unique()->firstName(),
        'email' => $faker->unique()->safeEmail(),
        'password' => encrypt('password'),
        'photo' => null,
        'professor_no' => null,
        'employee_no' => $faker->randomNumber(8),
        'is_admin' => $faker->boolean(),
        'locked' => false,
        'locked_date' => null,
        'with_hrm_access' => false,
        'with_hrt_access' => false,
        'with_hrp_access' => false,
        'with_cpm_access' => false,
        'has_change_password' => false,
        'is_applicant' => false,
        'access_all_branches' => false,
        'with_expiration' => false,
        'expiration_date' => null,
        'otp_code' => null,
        'with_ld_access' => false,
        'with_mig_access' => false,
        'is_encrypted' => false,
    ];
});
