<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->unique();
            $table->string('name');
            $table->string('type', 30);
            $table->foreignId('building_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('address_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('floor', 30)->nullable();
            $table->decimal('surface_m2', 8, 2)->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
