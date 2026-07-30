<?php

use App\CivilStatus;
use Illuminate\Database\Seeder;

class CivilStatusSeeder extends Seeder
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
                'code' => 'CS1',
                'name' => 'Married',
                'active' => true,
            ],
            [
                'code' => 'CS2',
                'name' => 'Single',
                'active' => true,
            ],
            [
                'code' => 'CS3',
                'name' => 'Widowed',
                'active' => true,
            ],
            [
                'code' => 'CS4',
                'name' => 'Separated',
                'active' => true,
            ],
            [
                'code' => 'CS5',
                'name' => 'Annulled',
                'active' => true,
            ],
            [
                'code' => 'CS6',
                'name' => 'Divorced',
                'active' => true,
            ],
            [
                'code' => 'CS7',
                'name' => 'Legally Separated',
                'active' => true,
            ],
        ];

        CivilStatus::truncate();

        foreach ($data as $value) {
            CivilStatus::create($value);
        }
    }
}
