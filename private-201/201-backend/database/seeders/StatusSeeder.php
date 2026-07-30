<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('status')->insert([
            'name' => 'Pending',
            'created_at' => now(),
        ]);
        DB::table('status')->insert([
            'name' => 'Submitted',
            'created_at' => now(),
        ]);
        DB::table('status')->insert([
            'name' => 'Evaluated',
            'created_at' => now(),
        ]);
        DB::table('status')->insert([
            'name' => 'Re-calibrated',
            'created_at' => now(),
        ]);
    }
}
