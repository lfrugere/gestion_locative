<?php

use App\Models\Building;
use App\Models\Property;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // L'ancien champ notes (texte libre, sans auteur ni date) devient la
        // première entrée du fil de notes de l'entité. Faute d'auteur connu à
        // l'époque, ces notes migrées sont attribuées au premier compte
        // utilisateur (l'administrateur, seul compte qui existe en pratique
        // à ce stade du projet).
        $authorId = DB::table('users')->orderBy('id')->value('id');

        $legacyTables = [
            'buildings' => Building::class,
            'properties' => Property::class,
        ];

        foreach ($legacyTables as $table => $modelClass) {
            DB::table($table)
                ->whereNotNull('notes')
                ->where('notes', '!=', '')
                ->orderBy('id')
                ->get(['id', 'notes', 'created_at'])
                ->each(function (object $row) use ($modelClass, $authorId): void {
                    DB::table('notes')->insert([
                        'notable_type' => $modelClass,
                        'notable_id' => $row->id,
                        'body' => $row->notes,
                        'created_by' => $authorId,
                        'updated_by' => null,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->created_at,
                    ]);
                });
        }

        Schema::table('buildings', function (Blueprint $table): void {
            $table->dropColumn('notes');
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn('notes');
        });
    }

    public function down(): void
    {
        // Ne restaure pas le contenu migré : la donnée vit désormais dans la
        // table notes, qui n'est pas supprimée par ce rollback.
        Schema::table('buildings', function (Blueprint $table): void {
            $table->text('notes')->nullable();
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->text('notes')->nullable();
        });
    }
};
