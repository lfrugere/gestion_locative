<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 30);
            $table->decimal('surface_m2', 8, 2)->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_rooms');
    }
};
