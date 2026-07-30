<?php

use App\Learning;
use Illuminate\Database\Seeder;

class LearningsSeeder extends Seeder
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
                'code' => 'Sk1',
                'name' => 'IT',
                'active' => true,
            ],
            [
                'code' => 'Sk2',
                'name' => 'Law',
                'active' => true,
            ],
            [
                'code' => 'Sk3',
                'name' => 'None Required',
                'active' => true,
            ],
            [
                'code' => 'Sk4',
                'name' => 'Commerce',
                'active' => true,
            ],
            [
                'code' => 'Sk5',
                'name' => 'Admin',
                'active' => true,
            ],
            [
                'code' => 'Sk6',
                'name' => 'Supervisor',
                'active' => true,
            ],
            [
                'code' => 'Sk7',
                'name' => 'Management & Supervision',
                'active' => true,
            ],
            [
                'code' => 'Sk8',
                'name' => 'Draftsman',
                'active' => true,
            ],
            [
                'code' => 'Sk9',
                'name' => 'Security',
                'active' => true,
            ],
            [
                'code' => 'Sk99',
                'name' => 'Others',
                'active' => true,
            ],
        ];

        Learning::truncate();

        foreach ($data as $value) {
            Learning::create($value);
        }
    }
}
