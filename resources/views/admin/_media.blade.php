<div class="card media-card">
    <h2>Photos et pièces jointes</h2>
    @php($photos = $media->where('kind', \App\Models\Media::KIND_PHOTO))
    @php($documents = $media->where('kind', \App\Models\Media::KIND_DOCUMENT))
    @if ($photos->isNotEmpty())
        <h3>Photos</h3>
        <div class="photo-grid">
            @foreach ($photos as $photo)
                <div class="photo-item @if($photo->is_primary) primary @endif">
                    <img src="{{ route('admin.media.download', $photo) }}" alt="{{ $photo->display_name }}">
                    <span>{{ $photo->is_primary ? 'Principale' : $photo->display_name }}</span>
                    @can($managePermission)
                        <div class="actions"><form method="POST" action="{{ route('admin.media.primary', $photo) }}">@csrf<button class="link-button" type="submit">Définir par défaut</button></form><form method="POST" action="{{ route('admin.media.destroy', $photo) }}" onsubmit="return confirm('Supprimer cette photo ?')">@csrf @method('DELETE')<button class="link-button" type="submit">Supprimer</button></form></div>
                    @endcan
                </div>
            @endforeach
        </div>
    @endif
    @if ($documents->isNotEmpty())
        <h3>Pièces jointes</h3>
        @foreach ($documents as $document)
            <div class="attachment-row">
                <a href="{{ route('admin.media.download', $document) }}">{{ $document->display_name }}</a>
                <span class="muted">{{ number_format($document->size / 1024, 0, ',', ' ') }} Ko</span>
                @if ($document->tags->isNotEmpty())<span class="tags">{{ $document->tags->pluck('name')->join(', ') }}</span>@endif
                @can($managePermission)
                    <form class="attachment-edit" method="POST" action="{{ route('admin.media.update', $document) }}">@csrf @method('PUT')<input name="display_name" value="{{ $document->display_name }}" aria-label="Nom de la pièce jointe"><input name="tags" value="{{ $document->tags->pluck('name')->join(', ') }}" placeholder="Tags séparés par des virgules" aria-label="Tags"><button class="button secondary" type="submit">Enregistrer</button></form>
                    <form method="POST" action="{{ route('admin.media.destroy', $document) }}" onsubmit="return confirm('Supprimer cette pièce jointe ?')">@csrf @method('DELETE')<button class="link-button" type="submit">Supprimer</button></form>
                @endcan
            </div>
        @endforeach
    @endif
    @can($managePermission)
        <form class="upload-form" method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data">
            @csrf
            <h3>Ajouter un fichier</h3>
            <div class="form-grid"><div class="form-field"><label for="kind">Type</label><select id="kind" name="kind"><option value="photo">Photo</option><option value="document">Pièce jointe</option></select></div><div class="form-field"><label for="file">Fichier</label><input id="file" type="file" name="file" required></div><div class="form-field"><label for="display_name">Nom affiché</label><input id="display_name" name="display_name" placeholder="Nom du fichier par défaut"></div><div class="form-field"><label for="tags">Tags</label><input id="tags" name="tags" placeholder="ex. bail, diagnostic, travaux"></div></div>
            <p class="hint">Photos : JPG, PNG ou WebP. Documents : PDF, images, DOCX ou XLSX. Taille maximale : 10 Mo.</p>
            <button class="button" type="submit">Ajouter</button>
        </form>
    @endcan
</div>
