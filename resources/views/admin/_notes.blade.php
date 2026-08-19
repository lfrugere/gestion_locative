@php($canManageNotes = auth()->user()->can($managePermission))

<section class="card media-card">
    <div class="media-card-header">
        <div>
            <p class="panel-kicker">Notes</p>
            <h2>Fil de notes</h2>
        </div>
        @if ($canManageNotes)
            <button class="button secondary" type="button" data-note-dialog-open="note-create-dialog">Ajouter une note</button>
        @endif
    </div>

    @if ($notes->isEmpty())
        <p class="empty compact">Aucune note n’a encore été ajoutée.</p>
    @else
        <div class="notes-feed">
            @foreach ($notes as $note)
                @php($canModifyNote = $canManageNotes && (auth()->id() === $note->created_by || auth()->user()->hasRole('admin')))
                <article class="note-entry">
                    <div class="note-meta">
                        <span class="note-author">{{ $note->author?->name ?? 'Utilisateur supprimé' }}</span>
                        <span class="note-date">{{ $note->created_at->format('d/m/Y à H:i') }}</span>
                        @if ($note->wasEdited())
                            <span class="note-edited">· modifiée le {{ $note->updated_at->format('d/m/Y à H:i') }} par {{ $note->editor?->name ?? 'Utilisateur supprimé' }}</span>
                        @endif
                    </div>
                    <p class="note-body">{{ $note->body }}</p>
                    @if ($canModifyNote)
                        <div class="attachment-actions">
                            <button class="text-action" type="button" data-note-dialog-open="note-edit-dialog-{{ $note->id }}">Modifier</button>
                            <form method="POST" action="{{ route('notes.destroy', $note) }}" onsubmit="return confirm('Supprimer cette note ?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-action danger-text-action" type="submit">Supprimer</button>
                            </form>
                        </div>
                    @endif
                </article>

                @if ($canModifyNote)
                    <dialog id="note-edit-dialog-{{ $note->id }}" class="modal-dialog note-dialog" aria-labelledby="note-edit-title-{{ $note->id }}">
                        <form method="dialog" class="modal-close-form"><button type="submit" aria-label="Fermer">×</button></form>
                        <div class="modal-content">
                            <p class="panel-kicker">Note</p>
                            <h2 id="note-edit-title-{{ $note->id }}">Modifier la note</h2>
                            <form method="POST" action="{{ route('notes.update', $note) }}" class="modal-form">
                                @csrf
                                @method('PUT')
                                <div class="form-field"><label for="note_body_{{ $note->id }}">Contenu</label><textarea id="note_body_{{ $note->id }}" name="body" required>{{ $note->body }}</textarea></div>
                                <div class="form-actions"><button class="button" type="submit">Enregistrer</button><button class="button secondary" type="button" data-note-dialog-close>Annuler</button></div>
                            </form>
                        </div>
                    </dialog>
                @endif
            @endforeach
        </div>
    @endif
</section>

@if ($canManageNotes)
    <dialog id="note-create-dialog" class="modal-dialog note-dialog" aria-labelledby="note-create-title">
        <form method="dialog" class="modal-close-form"><button type="submit" aria-label="Fermer">×</button></form>
        <div class="modal-content">
            <p class="panel-kicker">Note</p>
            <h2 id="note-create-title">Ajouter une note</h2>
            <form method="POST" action="{{ $storeRoute }}" class="modal-form">
                @csrf
                <div class="form-field"><label for="note_body_new">Contenu</label><textarea id="note_body_new" name="body" required autofocus>{{ old('body') }}</textarea></div>
                <div class="form-actions"><button class="button" type="submit">Ajouter la note</button><button class="button secondary" type="button" data-note-dialog-close>Annuler</button></div>
            </form>
        </div>
    </dialog>
@endif

<script>
    (function () {
        const openDialog = (dialog) => {
            if (typeof dialog.showModal === 'function') { dialog.showModal(); } else { dialog.setAttribute('open', ''); }
        };
        const closeDialog = (dialog) => {
            if (typeof dialog.close === 'function') { dialog.close(); } else { dialog.removeAttribute('open'); }
        };

        document.querySelectorAll('[data-note-dialog-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => openDialog(document.getElementById(trigger.dataset.noteDialogOpen)));
        });
        document.querySelectorAll('[data-note-dialog-close]').forEach((trigger) => {
            trigger.addEventListener('click', () => closeDialog(trigger.closest('dialog')));
        });
        document.querySelectorAll('.note-dialog').forEach((dialog) => {
            dialog.addEventListener('click', (event) => { if (event.target === dialog) closeDialog(dialog); });
        });

        @if ($errors->has('body') && ! old('_method'))
            const createDialog = document.getElementById('note-create-dialog');
            if (createDialog) openDialog(createDialog);
        @endif
    })();
</script>
