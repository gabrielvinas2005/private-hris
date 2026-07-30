<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMonthsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('months', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('abbrv');
            $table->string('name');
        });
        
        // Insert the 12 months
        DB::table('months')->insert([
            ['id' => 1, 'abbrv' => 'Jan', 'name' => 'January'],
            ['id' => 2, 'abbrv' => 'Feb', 'name' => 'February'],
            ['id' => 3, 'abbrv' => 'Mar', 'name' => 'March'],
            ['id' => 4, 'abbrv' => 'Apr', 'name' => 'April'],
            ['id' => 5, 'abbrv' => 'May', 'name' => 'May'],
            ['id' => 6, 'abbrv' => 'Jun', 'name' => 'June'],
            ['id' => 7, 'abbrv' => 'Jul', 'name' => 'July'],
            ['id' => 8, 'abbrv' => 'Aug', 'name' => 'August'],
            ['id' => 9, 'abbrv' => 'Sep', 'name' => 'September'],
            ['id' => 10, 'abbrv' => 'Oct', 'name' => 'October'],
            ['id' => 11, 'abbrv' => 'Nov', 'name' => 'November'],
            ['id' => 12, 'abbrv' => 'Dec', 'name' => 'December'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('months');
    }
}
