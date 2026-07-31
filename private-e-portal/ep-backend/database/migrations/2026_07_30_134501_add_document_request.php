<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
 
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
 
            $table->string('document_type'); // e.g. coe, health_clearance, workspace_clearance
            $table->string('purpose', 500);
            $table->text('notes')->nullable();
 
            $table->string('status')->default('Pending');
            // Pending -> Processing -> Ready for Pickup -> Released
            // or Denied at any point. Adjust to match your actual status set.
 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_requests');
    }
};