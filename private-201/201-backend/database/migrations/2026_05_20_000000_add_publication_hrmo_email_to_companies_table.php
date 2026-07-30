<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicationHrmoEmailToCompaniesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('companies') && !Schema::hasColumn('companies', 'publication_hrmo_email')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('publication_hrmo_email', 255)->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'publication_hrmo_email')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('publication_hrmo_email');
            });
        }
    }
}
