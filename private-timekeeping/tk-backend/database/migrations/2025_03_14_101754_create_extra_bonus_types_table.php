<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateExtraBonusTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extra_bonus_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $extra_bonuses = [
            [
                'code' => 'CNA',
                'name' => 'Collective Negotiation Agreement',
            ],
            [
                'code' => 'SRI',
                'name' => 'Service Recognation Incentive',
            ],
            [
                'code' => 'PEI',
                'name' => 'Productivity Enhancement Incentive',
            ],
        ];

        foreach ($extra_bonuses as $bonus) {
            DB::table('extra_bonus_types')->insert($bonus);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('extra_bonus_types');
    }
}
