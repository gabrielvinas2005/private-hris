<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateExamTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exam_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->dateTime('created_at')->default(DB::raw('GETDATE()'));
        });

        // Seed initial data
        DB::table('exam_types')->insert([
            ['code' => 'pre-examination', 'name' => 'Pre-Examination'],
            ['code' => 'technical', 'name' => 'Technical Examination'],
            ['code' => 'psychological', 'name' => 'Psychological Examination'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exam_types');
    }
}
