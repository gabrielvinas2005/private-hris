<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalaryColumnsToCosContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cos_contract', function (Blueprint $table) {
            if (!Schema::hasColumn('cos_contract', 'salary')) {
                $table->decimal('salary', 12, 2)->nullable()->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cos_contract', function (Blueprint $table) {
            if (Schema::hasColumn('cos_contract', 'salary')) {
                $table->dropColumn('salary');
            }
        });
    }
}
