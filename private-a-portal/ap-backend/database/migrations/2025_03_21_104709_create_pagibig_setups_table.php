<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePagibigSetupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagibig_setups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('year')->unique('UK_pagibig_setups_01');
            $table->decimal('amount', 18, 2)->default(0);
            $table->timestamps();
        });

        DB::unprepared("
            DECLARE @PK_NAME_TO_ALTER AS VARCHAR(200) = ''
            DECLARE @PK_NAME_TO_ADD AS VARCHAR(200) = 'PK_pagibig_setups'
            DECLARE @TABLE_NAME AS VARCHAR(200) = 'pagibig_setups'
            DECLARE @OLD_INDEX AS NVARCHAR(MAX) = ''
            DECLARE @SQL_DROP AS VARCHAR(MAX)
            DECLARE @SQL_CREATE AS VARCHAR(MAX)

            SET @PK_NAME_TO_ALTER = (
                                        SELECT 
                                            RTRIM(CONSTRAINT_NAME) AS CONSTRAINT_NAME
                                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                        WHERE OBJECTPROPERTY(OBJECT_ID(CONSTRAINT_SCHEMA + '.' + QUOTENAME(CONSTRAINT_NAME)), 'IsPrimaryKey') = 1
                                            AND TABLE_NAME = @TABLE_NAME
                                    )

            SET @OLD_INDEX = (
                                select distinct
                                    d.name as constraint_name
                                from sys.all_columns c
                                    inner join sys.tables t on t.object_id = c.object_id
                                    inner join sys.schemas s on s.schema_id = t.schema_id
                                    inner join sys.default_constraints d on c.default_object_id = d.object_id
                                    inner join sys.columns e on e.column_id = c.column_id and c.object_id = e.object_id
                                    inner join sys.types f on e.system_type_id = f.system_type_id
                                where f.name <> 'sysname'
                                and t.name = 'pagibig_setups'
                                )

            -- DROP OBJECT NAME
            SET @SQL_DROP = 'ALTER TABLE dbo.' + @TABLE_NAME + ' DROP CONSTRAINT ' + @PK_NAME_TO_ALTER
            EXEC(@SQL_DROP)

            -- CREATE OBJECT
            SET @SQL_CREATE = 'ALTER TABLE dbo.' + @TABLE_NAME + ' ADD CONSTRAINT ' + @PK_NAME_TO_ADD + ' PRIMARY KEY CLUSTERED (id)'
            EXEC(@SQL_CREATE)

            SET @SQL_DROP = 'ALTER TABLE pagibig_setups DROP CONSTRAINT IF EXISTS ' + @OLD_INDEX
            EXEC(@SQL_DROP)
            SET @SQL_DROP = ''

            SET @SQL_CREATE = 'ALTER TABLE pagibig_setups ADD CONSTRAINT DF_pagibig_setups_amount DEFAULT 0 FOR amount'
            EXEC(@SQL_CREATE)
            SET @SQL_CREATE = ''
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagibig_setups');
    }
}
