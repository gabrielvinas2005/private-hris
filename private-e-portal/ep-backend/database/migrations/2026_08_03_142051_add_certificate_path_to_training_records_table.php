<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('training_records', function (Blueprint $table) {
            $table->string('certificate_path')->nullable()->after('has_certificate');
        });
    }

    public function down()
    {
        Schema::table('training_records', function (Blueprint $table) {
            $table->dropColumn('certificate_path');
        });
    }
};