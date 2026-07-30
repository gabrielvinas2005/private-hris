<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approver_type')) {
            return;
        }

        DB::table('approver_type')->updateOrInsert(
            ['name' => 'Daily Time Record'],
            ['created_at' => DB::raw('GETDATE()')]
        );

        DB::table('approver_type')->updateOrInsert(
            ['name' => 'Accomplishment Report'],
            ['created_at' => DB::raw('GETDATE()')]
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('approver_type')) {
            return;
        }

        DB::table('approver_type')->whereIn('name', [
            'Daily Time Record',
            'Accomplishment Report',
        ])->delete();
    }
};
