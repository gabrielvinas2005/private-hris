<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAcceptanceLetterHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('acceptance_letter_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->string('applicant_no')->nullable();
            $table->string('applicant_name')->nullable();
            $table->string('email')->nullable();
            $table->string('position_applied')->nullable();
            $table->unsignedBigInteger('position_applied_id')->nullable();
            $table->string('status')->default('sent'); // sent, failed
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable(); // employee_id who sent it
            $table->dateTime('sent_at')->default(DB::raw('GETDATE()'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('acceptance_letter_history');
    }
}
