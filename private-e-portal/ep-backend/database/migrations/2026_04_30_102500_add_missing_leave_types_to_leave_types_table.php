<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMissingLeaveTypesToLeaveTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $leaveTypesToInsert = [
            [
                'name' => 'Maternity Leave',
                'aliases' => ['Maternity Leave'],
            ],
            [
                'name' => 'Paternity Leave',
                'aliases' => ['Paternity Leave'],
            ],
            [
                'name' => '10-Day VAWC Leave',
                'aliases' => ['10-Day VAWC Leave'],
            ],
            [
                'name' => 'Rehabilitation Privilege',
                'aliases' => ['Rehabilitation Privilege'],
            ],
            [
                'name' => 'Special Leave Benefits for Women',
                'aliases' => ['Special Leave Benefits for Women'],
            ],
            [
                'name' => 'Special Emergency (Calamity) Leave',
                'aliases' => ['Emergency Leave'],
            ],
            [
                'name' => 'Adoption Leave',
                'aliases' => ['Adoption Leave'],
            ],
        ];

        foreach ($leaveTypesToInsert as $leaveType) {
            $existing = DB::table('leave_types')
                ->where('name', $leaveType['name'])
                ->orWhereIn('name', $leaveType['aliases'])
                ->exists();

            if ($existing) {
                continue;
            }

            DB::table('leave_types')->insert([
                'name' => $leaveType['name'],
                'active' => 1,
                'service_credit' => 0,
                'accrued_id' => 1,
                'accrual_amount' => 0.00,
                'accrual_frequency_id' => 0,
                'leave_balance_policy_id' => 1,
                'is_editable_id' => 1,
                'serial_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('leave_types')->whereIn('name', [
            'Maternity Leave',
            'Paternity Leave',
            '10-Day VAWC Leave',
            'Rehabilitation Privilege',
            'Special Leave Benefits for Women',
            'Special Emergency (Calamity) Leave',
            'Adoption Leave',
        ])->delete();
    }
}
