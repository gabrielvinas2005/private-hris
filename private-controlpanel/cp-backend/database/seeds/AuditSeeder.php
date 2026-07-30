<?php

use App\Audit;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Audit::class, 5000)->create();
    }
}
