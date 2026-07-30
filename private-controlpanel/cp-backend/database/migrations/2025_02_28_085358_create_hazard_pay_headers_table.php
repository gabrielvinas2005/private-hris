<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHazardPayHeadersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hazard_pay_headers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('month_id')->default(0)->nullable();
            $table->integer('year')->nullable();
            $table->integer('department_id')->default(0)->nullable();
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
        Schema::table('hazard_pay_headers', function (Blueprint $table) {
            $table->dropColumn([
                'id',
                'month_id',
                'year',
                'department_id'
            ]);
        });
    }
}