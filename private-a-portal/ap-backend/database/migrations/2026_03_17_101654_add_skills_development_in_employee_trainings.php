<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSkillsDevelopmentInEmployeeTrainings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_trainings', function (Blueprint $table) {
            // Long text, nullable, placed near existing columns
            $table->text('skills_development')->nullable()->after('sponsored_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_trainings', function (Blueprint $table) {
            $table->dropColumn('skills_development');
        });
    }
}
