<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateClearanceCertTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clearance_cert', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint identity(1,1) primary key
            $table->unsignedBigInteger('employee_id')->nullable(); //
            $table->date('Date_of_filing')->default(DB::raw('getdate()'));
            $table->date('Date_of_effectivity')->default(DB::raw('getdate()'));

            $table->unsignedBigInteger('purpose_id')->nullable(); //
            $table->string('Other_purpose', 250)->nullable();

            $table->boolean('is_cleared')->default(false);
            $table->boolean('with_pending_administrative')->default(false);
            $table->boolean('with_ongoing_investigation')->default(false);

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
        Schema::dropIfExists('clearance_cert');
    }
}
