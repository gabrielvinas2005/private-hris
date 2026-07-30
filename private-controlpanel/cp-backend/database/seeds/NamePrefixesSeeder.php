<?php

use App\NamePrefix;
use Illuminate\Database\Seeder;

class NamePrefixesSeeder extends Seeder
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
                'code' => 'EXT1',
                'name' => 'MR.',
                'active' => true,
            ],
            [
                'code' => 'EXT2',
                'name' => 'MRS.',
                'active' => true,
            ],
            [
                'code' => 'EXT3',
                'name' => 'MS.',
                'active' => true,
            ],
        ];

        NamePrefix::truncate();

        foreach ($data as $value) {
            NamePrefix::create($value);
        }
    }
}
