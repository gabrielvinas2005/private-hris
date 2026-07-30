<?php

use App\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Employee::truncate(); // Clear the employees table before seeding
        // Use the factory to create 500 employees
        factory(Employee::class, 500)->create();
    }
}
