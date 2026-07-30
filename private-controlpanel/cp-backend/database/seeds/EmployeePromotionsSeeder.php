<?php

use App\EmployeePromotion;
use Illuminate\Database\Seeder;

class EmployeePromotionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(EmployeePromotion::class, 50)->create();
    }
}
