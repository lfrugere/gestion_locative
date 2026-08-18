<?php

use App\Models\PropertyRoom;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Même traitement que pour buildings/properties : le champ notes
        // libre de property_rooms devient la première entrée de son fil de
        // notes, attribuée au premier compte utilisateur faute d'auteur
        // connu à l'époque.
        $authorId = DB::table('users')->orderBy('id')->value('id');

        DB::table('property_rooms')
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->orderBy('id')
            ->get(['id', 'notes', 'created_at'])
            ->each(function (object $row) use ($authorId): void {
                DB::table('notes')->insert([
                    'notable_type' => PropertyRoom::class,
                    'notable_id' => $row->id,
                    'body' => $row->notes,
                    'created_by' => $authorId,
                    'updated_by' => null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->created_at,
                ]);
            });

        Schema::table('property_rooms', function (Blueprint $table): void {
            $table->dropColumn('notes');
        });
    }

    public function down(): void
    {
        // Ne restaure pas le contenu migré : la donnée vit désormais dans la
        // table notes, qui n'est pas supprimée par ce rollback.
        Schema::table('property_rooms', function (Blueprint $table): void {
            $table->text('notes')->nullable();
        });
    }
};
