<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsForwardedToApplicantsForAdministratorSelectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicants_for_administrator_selections', function (Blueprint $table) {
            $table->boolean('is_forwarded')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicants_for_administrator_selections', function (Blueprint $table) {
            $table->dropColumn('is_forwarded');
        });
    }
}
