<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeOrganizationsTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_organizations_temps', function (Blueprint $table) {
            $table->bigIncrements('organization_id');
            $table->integer('request_id')->default(0);
            $table->integer('employee_id')->default(0);
            $table->string('organization')->nullable();
            $table->date('org_from')->nullable();
            $table->date('org_to')->nullable();
            $table->decimal('org_hours')->default(0)->nullable();
            $table->string('org_position')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_organizations_temps');
    }
}
