<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWfhFieldsToTimeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_data', function (Blueprint $table) {
            // Add WFH specific fields
            if (!Schema::hasColumn('time_data', 'is_wfh')) {
                $table->boolean('is_wfh')->default(0)->after('is_ot')->comment('Indicates if this is a work from home attendance record');
            }
            
            if (!Schema::hasColumn('time_data', 'wfh_reason')) {
                $table->string('wfh_reason')->nullable()->after('is_wfh')->comment('Reason for working from home');
            }
            
            if (!Schema::hasColumn('time_data', 'wfh_location')) {
                $table->string('wfh_location')->nullable()->after('wfh_reason')->comment('Location where employee is working from home');
            }
            
            if (!Schema::hasColumn('time_data', 'wfh_approved_by')) {
                $table->integer('wfh_approved_by')->nullable()->after('wfh_location')->comment('User ID who approved the WFH request');
            }
            
            if (!Schema::hasColumn('time_data', 'wfh_approved_at')) {
                $table->timestamp('wfh_approved_at')->nullable()->after('wfh_approved_by')->comment('When the WFH was approved');
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
            $table->dropColumn([
                'is_wfh',
                'wfh_reason', 
                'wfh_location',
                'wfh_approved_by',
                'wfh_approved_at'
            ]);
        });
    }
}
