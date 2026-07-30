<?php

use App\EmploymentType;
use Illuminate\Database\Seeder;

class EmploymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Permanent',
                'code' => '1',
                'with_end_contract' => false,
                'active' => true,
            ],
            [
                'name' => 'Casual',
                'code' => '2',
                'with_end_contract' => false,
                'active' => true,
            ],
            [
                'name' => 'Job Order',
                'code' => '3',
                'with_end_contract' => false,
                'active' => true,
            ],
            [
                'name' => 'Contractual',
                'code' => '4',
                'with_end_contract' => true,
                'active' => true,
            ],
        ];

        EmploymentType::truncate();

        foreach ($data as $value) {
            EmploymentType::create($value);
        }
    }
}
