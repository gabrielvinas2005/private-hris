<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        if (env('APP_ENV', '') == 'production') {
            $this->call([
                PHAddressSeeder::class,
                InitialDataSeeder::class,
                DocumentTypeSeeder::class,
                CivilStatusSeeder::class,
                EmploymentTypeSeeder::class,
                EmployeeSeeder::class,
            ]);
        } else {
            $this->call([
                PHAddressSeeder::class,
                InitialDataSeeder::class,
                DocumentTypeSeeder::class,
                CivilStatusSeeder::class,
                EmploymentTypeSeeder::class,
                EmployeeSeeder::class,
            ]);
        }
    }
}
