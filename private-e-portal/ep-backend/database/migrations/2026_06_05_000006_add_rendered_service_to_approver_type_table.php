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

        $existsByName = DB::table('approver_type')->where('name', 'Rendered Service')->exists();
        if ($existsByName) {
            return;
        }

        $existsById = DB::table('approver_type')->where('id', 10)->exists();
        if ($existsById) {
            DB::table('approver_type')->where('id', 10)->update([
                'name' => 'Rendered Service',
            ]);
            return;
        }

        DB::unprepared('SET IDENTITY_INSERT [approver_type] ON');
        DB::table('approver_type')->insert([
            'id' => 10,
            'name' => 'Rendered Service',
            'created_at' => DB::raw('GETDATE()'),
        ]);
        DB::unprepared('SET IDENTITY_INSERT [approver_type] OFF');
    }

    public function down(): void
    {
        if (!Schema::hasTable('approver_type')) {
            return;
        }

        DB::table('approver_type')->where('id', 10)->where('name', 'Rendered Service')->delete();
    }
};
