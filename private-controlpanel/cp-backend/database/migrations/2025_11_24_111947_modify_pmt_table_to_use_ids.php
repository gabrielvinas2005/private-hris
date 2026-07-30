<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyPmtTableToUseIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop existing table if it exists
        Schema::dropIfExists('pmt');
        
        // Recreate table with new structure
        Schema::create('pmt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('department_id');
            $table->boolean('is_ipcr')->default(false);
            $table->boolean('is_opcr')->default(false);
            $table->boolean('is_dpcr')->default(false);
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate employee-department combinations
            $table->unique(['employee_id', 'department_id']);
        });
        
        // Add foreign key constraints separately (SQL Server compatibility)
        DB::statement('ALTER TABLE pmt ADD CONSTRAINT fk_pmt_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE pmt ADD CONSTRAINT fk_pmt_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pmt');
        
        // Recreate old structure
        Schema::create('pmt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('Employee_no', 50);
            $table->string('Department_no', 50);
            $table->boolean('is_ipcr')->default(false);
            $table->boolean('is_opcr')->default(false);
            $table->boolean('is_dpcr')->default(false);
            $table->timestamps();
        });
    }
}
