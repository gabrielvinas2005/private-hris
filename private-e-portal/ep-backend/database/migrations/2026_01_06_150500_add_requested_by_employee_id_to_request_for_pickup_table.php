<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddRequestedByEmployeeIdToRequestForPickupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_for_pickup', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_by_employee_id')->nullable()->after('requested_by');
            $table->foreign('requested_by_employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('set null');
        });

        // Backfill existing records using the employee_id from the parent OB when available
        DB::statement('
            UPDATE r
            SET r.requested_by_employee_id = ob.employee_id
            FROM request_for_pickup r
            INNER JOIN official_business_applications ob ON r.ob_id = ob.id
            WHERE r.requested_by_employee_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_for_pickup', function (Blueprint $table) {
            $table->dropForeign(['requested_by_employee_id']);
            $table->dropColumn('requested_by_employee_id');
        });
    }
}

