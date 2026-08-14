<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('mediable');
            $table->string('kind', 20);
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('display_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['mediable_type', 'kind']);
        });

        Schema::create('media_tag', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['media_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_tag');
        Schema::dropIfExists('media');
    }
};
