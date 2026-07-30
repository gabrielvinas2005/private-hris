<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloadable_forms', function (Blueprint $table) {
            $table->bigIncrements('FormId');
            $table->string('FormName');
            $table->longText('FormFile')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloadable_forms');
    }
};
