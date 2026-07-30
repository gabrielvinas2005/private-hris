<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDepartmentToUniformClothingHeaderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uniform_clothing_header', function (Blueprint $table) {
            if (!Schema::hasColumn('uniform_clothing_header', 'department_id')) {
                $table->integer('department_id')->nullable()->after('branch_id');
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
        Schema::table('uniform_clothing_header', function (Blueprint $table) {
            if (Schema::hasColumn('uniform_clothing_header', 'department_id')) {
                $table->dropColumn('department_id');
            }
        });
    }
}

