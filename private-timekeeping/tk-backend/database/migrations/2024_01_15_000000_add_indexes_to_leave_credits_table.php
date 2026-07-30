<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToLeaveCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_credits', function (Blueprint $table) {
            // Add index on employee_id for faster joins and lookups
            $table->index('employee_id', 'idx_leave_credits_employee_id');
            
            // Add index on leave_type_id for faster filtering
            $table->index('leave_type_id', 'idx_leave_credits_leave_type_id');
            
            // Add composite unique index on (employee_id, leave_type_id) for faster updateOrInsert operations
            // This also ensures uniqueness and improves query performance
            $table->unique(['employee_id', 'leave_type_id'], 'idx_leave_credits_employee_leave_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_credits', function (Blueprint $table) {
            $table->dropUnique('idx_leave_credits_employee_leave_type_unique');
            $table->dropIndex('idx_leave_credits_leave_type_id');
            $table->dropIndex('idx_leave_credits_employee_id');
        });
    }
}

