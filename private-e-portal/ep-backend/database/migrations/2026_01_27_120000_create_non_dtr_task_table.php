<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNonDtrTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('non_dtr_task', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->text('task_1')->nullable(); // Main tasks/responsibilities
            $table->text('task_2')->nullable(); // Secondary tasks
            $table->text('task_3')->nullable(); // Additional tasks
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->boolean('for_payroll')->default(false); // Approved for payroll processing
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_id');
            $table->index('status');
            $table->index(['period_from', 'period_to']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('non_dtr_task');
    }
}
