<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddObTypesToOfficialBusinessApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('official_business_applications', function (Blueprint $table) {
            $table->string('ob_type')->nullable();
            $table->string('funds')->nullable();
            $table->string('recommending_approval')->nullable();
            $table->string('recommending_position')->nullable();
            $table->string('approver')->nullable();
            $table->string('approver_position')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('official_business_applications', function (Blueprint $table) {
            //
        });
    }
}
