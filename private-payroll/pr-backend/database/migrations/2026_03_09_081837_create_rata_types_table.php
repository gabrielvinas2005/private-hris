<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateRataTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::statement("
                CREATE TABLE rata_types (
                    id bigint IDENTITY(1,1) PRIMARY KEY,
                    name nvarchar(255) NULL,
                    code nvarchar(255) NULL,
                    is_active bit NULL,
                    sort_order int NULL,
                    created_at datetime NULL,
                    updated_at datetime NULL
                )
            ");
            DB::statement("CREATE UNIQUE INDEX UX_rata_types_code ON rata_types(code) WHERE code IS NOT NULL");
        } else {
            Schema::create('rata_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->nullable();
                $table->string('code')->nullable()->unique();
                $table->boolean('is_active')->default(true)->nullable();
                $table->integer('sort_order')->nullable();
                $table->timestamps();
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
        Schema::dropIfExists('rata_types');
    }
}
