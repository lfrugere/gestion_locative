<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->string('type', 30)->nullable()->after('kind');
            $table->index(['mediable_type', 'type']);
        });

        DB::table('media')->whereNull('type')->update(['type' => 'other']);
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table): void {
            $table->dropIndex('media_mediable_type_type_index');
            $table->dropColumn('type');
        });
    }
};
