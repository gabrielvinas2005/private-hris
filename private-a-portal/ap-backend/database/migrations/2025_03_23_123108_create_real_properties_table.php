<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateRealPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saln_real_properties', function (Blueprint $table) {
            $table->bigIncrements('id'); // Auto-incrementing primary key
            $table->unsignedBigInteger('user_id'); // If linked to a user
            $table->string('description')->nullable();
            $table->string('kind')->nullable();
            $table->string('exact_location')->nullable();
            $table->decimal('assessed_value', 15, 2)->nullable();
            $table->decimal('current_fair_market_value', 15, 2)->nullable();
            $table->year('acquisition_year')->nullable();
            $table->string('acquisition_mode')->nullable();
            $table->decimal('acquisition_cost', 15, 2)->nullable();
            $table->timestamps();

            // Foreign key constraint if linking to a users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saln_real_properties');
    }
};
