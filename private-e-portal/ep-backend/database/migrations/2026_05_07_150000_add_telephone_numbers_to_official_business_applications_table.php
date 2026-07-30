<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTelephoneNumbersToOfficialBusinessApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('official_business_applications', function (Blueprint $table) {
            $table->string('telephone_numbers')->nullable()->after('client');
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
            $table->dropColumn('telephone_numbers');
        });
    }
}

