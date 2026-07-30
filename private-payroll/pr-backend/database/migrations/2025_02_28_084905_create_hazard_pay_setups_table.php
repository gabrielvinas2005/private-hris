<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHazardPaySetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hazard_pay_setups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('salary_from',10,2)->nullable();
            $table->decimal('salary_to',10,2)->nullable();
            $table->decimal('percentage',4,2)->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hazard_pay_setups', function (Blueprint $table) {
            $table->dropColumn([
                'id',
                'salary_from',
                'salary_to',
                'percentage'
            ]);
        });
    }

}