<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniformClothingAllowanceSignatoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uniform_clothing_allowance_signatories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('branch_id');
            $table->string('report_name')->nullable();
            $table->string('signatory_1')->nullable();
            $table->string('signatory_position_1')->nullable();
            $table->string('description_1')->nullable();
            $table->string('signatory_2')->nullable();
            $table->string('signatory_position_2')->nullable();
            $table->string('description_2')->nullable();
            $table->string('signatory_3')->nullable();
            $table->string('signatory_position_3')->nullable();
            $table->string('description_3')->nullable();
            $table->string('signatory_4')->nullable();
            $table->string('signatory_position_4')->nullable();
            $table->string('description_4')->nullable();
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
        Schema::dropIfExists('uniform_clothing_allowance_signatories');
    }
}
