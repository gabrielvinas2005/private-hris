<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSentToEmployeeToCosContractNotifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('cos_contract_notifications') && !Schema::hasColumn('cos_contract_notifications', 'sent_to_employee')) {
            Schema::table('cos_contract_notifications', function (Blueprint $table) {
                $table->boolean('sent_to_employee')->default(0)->after('sent_to_hr');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('cos_contract_notifications') && Schema::hasColumn('cos_contract_notifications', 'sent_to_employee')) {
            Schema::table('cos_contract_notifications', function (Blueprint $table) {
                $table->dropColumn('sent_to_employee');
            });
        }
    }
}
