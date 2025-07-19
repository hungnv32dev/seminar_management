<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->string('company')->nullable();
            $table->string('position')->nullable();
            $table->string('ticket_code')->unique();
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_checked_in')->default(false);
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('workshop_id');
            $table->index('ticket_type_id');
            $table->index('ticket_code');
            $table->index('email');
            $table->index(['workshop_id', 'email']);
            $table->index('is_paid');
            $table->index('is_checked_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
