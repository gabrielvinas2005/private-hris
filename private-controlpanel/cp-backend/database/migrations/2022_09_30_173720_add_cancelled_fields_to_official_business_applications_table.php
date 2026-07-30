<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelledFieldsToOfficialBusinessApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('official_business_applications', function (Blueprint $table) {
            $table->boolean('is_cancel')->default(0)->nullable();
            $table->integer('canceled_by')->default(0)->nullable();
            $table->dateTime('canceled_date')->nullable();
            $table->string('canceled_remarks')->nullable();
            $table->string('approved_remarks')->nullable();
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
            $table->dropColumn('is_cancel');
            $table->dropColumn('canceled_by');
            $table->dropColumn('canceled_date');
            $table->dropColumn('canceled_remarks');
            $table->dropColumn('approved_remarks');
        });
    }
}
