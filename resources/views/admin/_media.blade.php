<section class="card media-card">
    <div class="media-card-header">
        <div>
            <p class="panel-kicker">Médias</p>
            <h2>Photos et pièces jointes</h2>
        </div>
        @can($managePermission)
            <div class="media-card-actions">
                <button class="button secondary" type="button" data-dialog-open="photo-upload-dialog">Ajouter une photo</button>
                <button class="button" type="button" data-dialog-open="document-upload-dialog">Ajouter une pièce jointe</button>
            </div>
        @endcan
    </div>

    @php($photos = $media->where('kind', \App\Models\Media::KIND_PHOTO))
    @php($documents = $media->where('kind', \App\Models\Media::KIND_DOCUMENT))

    @if ($photos->isNotEmpty())
        <h3 class="media-section-title">Photos</h3>
        <div class="photo-grid">
            @foreach ($photos as $photo)
                <article class="photo-item @if($photo->is_primary) primary @endif">
                    <img src="{{ route('admin.media.download', $photo) }}" alt="{{ $photo->display_name }}">
                    <div class="photo-item-content">
                        <span>{{ $photo->is_primary ? 'Photo principale' : $photo->display_name }}</span>
                        @can($managePermission)
                            <div class="actions photo-actions">
                                @unless($photo->is_primary)
                                    <form method="POST" action="{{ route('admin.media.primary', $photo) }}">
                                        @csrf
                                        <button class="text-action" type="submit">Définir par défaut</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('admin.media.destroy', $photo) }}" onsubmit="return confirm('Supprimer cette photo ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-action danger-text-action" type="submit">Supprimer</button>
                                </form>
                            </div>
                        @endcan
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if ($documents->isNotEmpty())
        <h3 class="media-section-title">Pièces jointes</h3>
        <div class="attachment-list">
            @foreach ($documents as $document)
                <article class="attachment-row">
                    <div class="attachment-info">
                        <a href="{{ route('admin.media.download', $document) }}">{{ $document->display_name }}</a>
                        <span>{{ number_format($document->size / 1024, 0, ',', ' ') }} Ko</span>
                    </div>
                    @if ($document->tags->isNotEmpty())
                        <span class="tags">{{ $document->tags->pluck('name')->join(', ') }}</span>
                    @endif
                    @can($managePermission)
                        <div class="attachment-actions">
                            <button class="text-action" type="button" data-dialog-open="document-edit-dialog-{{ $document->id }}">Modifier</button>
                            <form method="POST" action="{{ route('admin.media.destroy', $document) }}" onsubmit="return confirm('Supprimer cette pièce jointe ?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-action danger-text-action" type="submit">Supprimer</button>
                            </form>
                        </div>
                    @endcan
                </article>

                @can($managePermission)
                    <dialog id="document-edit-dialog-{{ $document->id }}" class="modal-dialog" aria-labelledby="document-edit-title-{{ $document->id }}">
                        <form method="dialog" class="modal-close-form"><button type="submit" aria-label="Fermer">×</button></form>
                        <div class="modal-content">
                            <p class="panel-kicker">Pièce jointe</p>
                            <h2 id="document-edit-title-{{ $document->id }}">Modifier les informations</h2>
                            <form method="POST" action="{{ route('admin.media.update', $document) }}" class="modal-form">
                                @csrf
                                @method('PUT')
                                <div class="form-field"><label for="display_name_{{ $document->id }}">Nom affiché</label><input id="display_name_{{ $document->id }}" name="display_name" value="{{ $document->display_name }}" required></div>
                                <div class="form-field"><label for="tags_{{ $document->id }}">Tags</label><input id="tags_{{ $document->id }}" name="tags" value="{{ $document->tags->pluck('name')->join(', ') }}" placeholder="ex. bail, diagnostic, travaux"></div>
                                <div class="form-actions"><button class="button" type="submit">Enregistrer</button><button class="button secondary" type="button" data-dialog-close>Annuler</button></div>
                            </form>
                        </div>
                    </dialog>
                @endcan
            @endforeach
        </div>
    @endif

    @if ($photos->isEmpty() && $documents->isEmpty())
        <p class="empty compact">Aucun média n’a encore été ajouté.</p>
    @endif
</section>

@can($managePermission)
    <dialog id="photo-upload-dialog" class="modal-dialog" aria-labelledby="photo-upload-title">
        <form method="dialog" class="modal-close-form"><button type="submit" aria-label="Fermer">×</button></form>
        <div class="modal-content">
            <p class="panel-kicker">Photos</p>
            <h2 id="photo-upload-title">Ajouter une photo</h2>
            <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data" class="modal-form">
                @csrf
                <input type="hidden" name="kind" value="photo">
                <div class="form-field"><label for="photo_file">Fichier</label><input id="photo_file" type="file" name="file" accept="image/jpeg,image/png,image/webp" required></div>
                <div class="form-field"><label for="photo_display_name">Nom affiché <span class="field-optional">(facultatif)</span></label><input id="photo_display_name" name="display_name" placeholder="Nom du fichier par défaut"></div>
                <p class="hint">JPG, PNG ou WebP, 20 Mo maximum. La première photo ajoutée devient la photo principale.</p>
                <div class="form-actions"><button class="button" type="submit">Ajouter la photo</button><button class="button secondary" type="button" data-dialog-close>Annuler</button></div>
            </form>
        </div>
    </dialog>

    <dialog id="document-upload-dialog" class="modal-dialog" aria-labelledby="document-upload-title">
        <form method="dialog" class="modal-close-form"><button type="submit" aria-label="Fermer">×</button></form>
        <div class="modal-content">
            <p class="panel-kicker">Pièce jointe</p>
            <h2 id="document-upload-title">Ajouter une pièce jointe</h2>
            <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data" class="modal-form">
                @csrf
                <input type="hidden" name="kind" value="document">
                <div class="form-field"><label for="document_file">Fichier</label><input id="document_file" type="file" name="file" accept="application/pdf,image/jpeg,image/png,image/webp,.doc,.docx,.xls,.xlsx" required></div>
                <div class="form-field"><label for="document_display_name">Nom affiché <span class="field-optional">(facultatif)</span></label><input id="document_display_name" name="display_name" placeholder="Nom du fichier par défaut"></div>
                <div class="form-field"><label for="document_tags">Tags <span class="field-optional">(facultatif)</span></label><input id="document_tags" name="tags" placeholder="ex. bail, diagnostic, travaux"></div>
                <p class="hint">PDF, image, DOCX ou XLSX, 20 Mo maximum.</p>
                <div class="form-actions"><button class="button" type="submit">Ajouter la pièce jointe</button><button class="button secondary" type="button" data-dialog-close>Annuler</button></div>
            </form>
        </div>
    </dialog>

    <script>
        const openDialog = (dialog) => {
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', '');
            }
        };
        const closeDialog = (dialog) => {
            if (typeof dialog.close === 'function') {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
            }
        };
        document.querySelectorAll('[data-dialog-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => openDialog(document.getElementById(trigger.dataset.dialogOpen)));
        });
        document.querySelectorAll('[data-dialog-close]').forEach((trigger) => {
            trigger.addEventListener('click', () => closeDialog(trigger.closest('dialog')));
        });
        document.querySelectorAll('.modal-dialog').forEach((dialog) => {
            dialog.addEventListener('click', (event) => { if (event.target === dialog) closeDialog(dialog); });
        });
        @if ($errors->has('file') && old('kind') === \App\Models\Media::KIND_PHOTO)
            openDialog(document.getElementById('photo-upload-dialog'));
        @elseif ($errors->has('file') && old('kind') === \App\Models\Media::KIND_DOCUMENT)
            openDialog(document.getElementById('document-upload-dialog'));
        @endif
    </script>
@endcan
