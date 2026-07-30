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
            ['name' => 'Request for Pickup'],
            ['created_at' => DB::raw('GETDATE()')]
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('approver_type')) {
            return;
        }

        DB::table('approver_type')->where('name', 'Request for Pickup')->delete();
    }
};

