<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancelAttachmentToOvertimeApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->text('attachment_name')->nullable();
            $table->text('path')->nullable();
            $table->string('extension')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overtime_applications', function (Blueprint $table) {
            $table->dropColumn('attachment_name');
            $table->dropColumn('path');
            $table->dropColumn('extension');
        });
    }
}
