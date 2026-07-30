<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManualAttendanceFieldsToTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data', function (Blueprint $table) {
            // Add ob_hours field if it doesn't exist
            if (!Schema::hasColumn('time_data', 'ob_hours')) {
                $table->decimal('ob_hours', 18, 3)->default(0)->nullable();
            }
            
            // Add dtr_request_id field for linking to DTR requests
            if (!Schema::hasColumn('time_data', 'dtr_request_id')) {
                $table->integer('dtr_request_id')->default(0)->nullable();
            }
            
            // Add manual_entry_source field to track how the record was created
            if (!Schema::hasColumn('time_data', 'manual_entry_source')) {
                $table->string('manual_entry_source')->nullable()->comment('biometric, manual, correction');
            }
            
            // Add entry_timestamp for when the record was manually entered
            if (!Schema::hasColumn('time_data', 'entry_timestamp')) {
                $table->timestamp('entry_timestamp')->nullable();
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
        Schema::table('time_data', function (Blueprint $table) {
            if (Schema::hasColumn('time_data', 'ob_hours')) {
                $table->dropColumn('ob_hours');
            }
            
            if (Schema::hasColumn('time_data', 'dtr_request_id')) {
                $table->dropColumn('dtr_request_id');
            }
            
            if (Schema::hasColumn('time_data', 'manual_entry_source')) {
                $table->dropColumn('manual_entry_source');
            }
            
            if (Schema::hasColumn('time_data', 'entry_timestamp')) {
                $table->dropColumn('entry_timestamp');
            }
        });
    }
}
