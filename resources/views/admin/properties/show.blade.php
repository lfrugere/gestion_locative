@extends('layouts.admin')

@section('title', $property->name)

@section('content')
    @php($mapAddress = $property->building?->address ?? $property->address)
    @php($primaryPhoto = $property->media->first(fn ($media) => $media->isPhoto() && $media->is_primary))

    <section class="detail-hero">
        <div class="detail-hero-copy">
            <a class="back-link" href="{{ route('properties.index') }}">← Tous les biens</a>
            <div class="eyebrow">{{ $property->typeLabel() }} · {{ $property->reference }}</div>
            <h1>{{ $property->name }}</h1>
            <p class="detail-lead">{{ $property->building?->name ?? $mapAddress?->city ?? 'Adresse à compléter' }}</p>
        </div>
        @if ($primaryPhoto)
            <figure class="detail-hero-photo"><img src="{{ route('media.download', $primaryPhoto) }}" alt="{{ $primaryPhoto->display_name }}"></figure>
        @endif
        <div class="detail-hero-actions">
            @if ($property->is_shared_accommodation)
                <span class="status-pill status-active">Colocation</span>
            @endif
            <span class="status-pill {{ $property->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</span>
            @can('manage properties')
                <a class="button secondary" href="{{ route('properties.edit', $property) }}">Modifier</a>
            @endcan
        </div>
    </section>

    <div class="detail-grid">
        <div class="detail-main">
            <section class="detail-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Vue d’ensemble</span><h2>Caractéristiques du bien</h2></div><span class="panel-icon">⌂</span></div>
                <div class="metric-grid property-metric-grid">
                    <div class="metric"><span>Type</span><strong>{{ $property->typeLabel() }}</strong></div>
                    <div class="metric"><span>Statut</span><strong>{{ $property->status === 'active' ? 'Actif' : 'Inactif' }}</strong></div>
                    <div class="metric"><span>Étage</span><strong>{{ $property->floor ?: '—' }}</strong></div>
                    <div class="metric"><span>Surface</span><strong>{{ $property->surface_m2 ? $property->surface_m2.' m²' : '—' }}</strong></div>
                    <div class="metric"><span>Usage</span><strong>{{ $property->is_shared_accommodation ? 'Colocation' : 'Classique' }}</strong></div>
                </div>
            </section>

            <section class="detail-panel address-panel">
                <div class="panel-heading"><div><span class="panel-kicker">Rattachement</span><h2>{{ $property->building ? 'Immeuble' : 'Adresse' }}</h2></div><span class="panel-icon">⌖</span></div>
                @if ($property->building)
                    <a class="related-entity" href="{{ route('buildings.show', $property->building) }}"><span class="entity-mark">I</span><span><strong>{{ $property->building->name }}</strong><small>{{ $property->building->reference }} · {{ $mapAddress->line1 }}, {{ $mapAddress->city }}</small></span><span class="row-arrow">→</span></a>
                @elseif ($property->address)
                    <p class="address-value">{{ $property->address->line1 }}@if($property->address->line2)<br>{{ $property->address->line2 }}@endif<br>{{ $property->address->postal_code }} {{ $property->address->city }}<br>France</p>
                @endif
            </section>

            @can('manage properties')
                <section class="detail-panel">
                    <div class="panel-heading"><div><span class="panel-kicker">Rattachement</span><h2>Compte bancaire</h2></div><span class="panel-icon">🏦</span></div>
                    @if ($property->bankAccount)
                        <a class="related-entity" href="{{ route('bank-accounts.show', $property->bankAccount) }}"><span class="entity-mark">B</span><span><strong>{{ $property->bankAccount->label }}</strong></span><span class="row-arrow">→</span></a>
                    @else
                        <p class="empty compact">Aucun compte bancaire associé à ce bien.</p>
                    @endif
                </section>
            @endcan

            @can('manage invoices')
                <section class="detail-panel associated-panel">
                    <div class="panel-heading"><div><span class="panel-kicker">Charges</span><h2>Factures</h2></div><span class="count-badge">{{ $property->invoices->count() }}</span></div>

                    @if ($property->invoices->isEmpty())
                        <p class="empty compact">Aucune facture n’a encore été ajoutée.</p>
                    @else
                        <div class="card table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Date</th><th>Fournisseur</th><th>N°</th><th>Libellé</th><th style="text-align: right;">Montant</th><th>Écriture bancaire</th><th>Pièces jointes</th><th>Actions</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($property->invoices as $invoice)
                                        <tr>
                                            <td>{{ $invoice->date->format('d/m/Y') }}</td>
                                            <td>{{ $invoice->supplier ?: '—' }}</td>
                                            <td>{{ $invoice->number ?: '—' }}</td>
                                            <td>{{ $invoice->label }}</td>
                                            <td style="text-align: right;">{{ number_format($invoice->amount, 2, ',', ' ') }} €</td>
                                            <td>
                                                @if ($invoice->bankTransaction)
                                                    <a href="{{ route('bank-accounts.show', $property->bank_account_id) }}">Ajoutée au compte</a>
                                                @else
                                                    <span class="hint">Aucun compte associé au bien</span>
                                                @endif
                                            </td>
                                            <td>
                                                @foreach ($invoice->media as $file)
                                                    <div style="display:flex; align-items:center; gap:4px;">
                                                        <a href="{{ route('media.download', $file) }}">{{ $file->display_name }}</a>
                                                        <form method="POST" action="{{ route('media.destroy', $file) }}" onsubmit="return confirm('Supprimer cette pièce jointe ?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="icon-action danger-action" type="submit" aria-label="Supprimer la pièce jointe" data-tooltip="Supprimer" title="Supprimer"><span aria-hidden="true">×</span></button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                                <form method="POST" action="{{ route('properties.invoices.media.store', [$property, $invoice]) }}" enctype="multipart/form-data" style="margin-top:4px;">
                                                    @csrf
                                                    <input type="file" name="file" required style="max-width: 160px;">
                                                    <button class="button secondary" type="submit">Ajouter</button>
                                                </form>
                                            </td>
                                            <td class="actions">
                                                <form method="POST" action="{{ route('properties.invoices.destroy', [$property, $invoice]) }}" onsubmit="return confirm('Supprimer cette facture ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="icon-action danger-action" type="submit" aria-label="Supprimer la facture" data-tooltip="Supprimer" title="Supprimer"><span aria-hidden="true">×</span></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <form class="card" method="POST" action="{{ route('properties.invoices.store', $property) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-grid">
                            <div class="form-field"><label for="inv_supplier">Fournisseur</label><input id="inv_supplier" name="supplier" placeholder="Optionnel"></div>
                            <div class="form-field"><label for="inv_number">N° de facture</label><input id="inv_number" name="number" placeholder="Optionnel"></div>
                            <div class="form-field"><label for="inv_label">Libellé</label><input id="inv_label" name="label" required></div>
                            <div class="form-field"><label for="inv_amount">Montant</label><input id="inv_amount" type="number" step="0.01" min="0.01" name="amount" placeholder="100.00" required></div>
                            <div class="form-field"><label for="inv_date">Date</label><input id="inv_date" type="date" name="date" value="{{ now()->format('Y-m-d') }}" required></div>
                            <div class="form-field"><label for="inv_attachments">Pièces jointes</label><input id="inv_attachments" type="file" name="attachments[]" multiple></div>
                        </div>
                        @if ($property->bankAccount)
                            <p class="hint">Cette facture ajoutera automatiquement une écriture de dépense sur le compte « {{ $property->bankAccount->label }} ».</p>
                        @else
                            <p class="hint">Aucun compte bancaire n’est associé à ce bien : la facture ne sera pas reportée sur un compte.</p>
                        @endif
                        <div class="form-actions"><button class="button" type="submit">Ajouter la facture</button></div>
                    </form>
                </section>
            @endcan

            @can('manage properties')
                <section class="detail-panel">
                    <div class="panel-heading"><div><span class="panel-kicker">Attribution</span><h2>Mise en gestion</h2></div><span class="panel-icon">👤</span></div>
                    @if ($managers->isEmpty())
                        <p class="empty compact">Aucun compte gestionnaire n’existe pour le moment.</p>
                    @else
                        <form method="POST" action="{{ route('properties.managers.update', $property) }}" class="modal-form">
                            @csrf @method('PUT')
                            @foreach ($managers as $manager)
                                <label class="hint" style="display: flex; align-items: center; gap: 8px; margin: 6px 0;">
                                    <input type="checkbox" name="managers[]" value="{{ $manager->id }}" @checked($property->managers->contains($manager))>
                                    {{ $manager->name }} ({{ $manager->email }})
                                </label>
                            @endforeach
                            <div class="form-actions"><button class="button" type="submit">Enregistrer les gestionnaires</button></div>
                        </form>
                    @endif
                </section>
            @endcan

            @if ($property->canHaveRooms())
                <section class="detail-panel">
                    <div class="panel-heading">
                        <div><span class="panel-kicker">Colocation</span><h2>Pièces</h2></div>
                        @can('manage properties')
                            <a class="button secondary" href="{{ route('property-rooms.create', $property) }}">Ajouter une pièce</a>
                        @endcan
                    </div>
                    @forelse ($property->rooms as $room)
                        @php($roomPhoto = $room->media->first(fn ($media) => $media->isPhoto() && $media->is_primary))
                        <a class="associated-row room-row" href="{{ route('property-rooms.show', [$property, $room]) }}">
                            @if ($roomPhoto)
                                <img class="list-thumb" src="{{ route('media.download', $roomPhoto) }}" alt="">
                            @else
                                <span class="entity-mark">P</span>
                            @endif
                            <span><strong>{{ $room->name }}</strong>@if($room->surface_m2)<small>{{ $room->surface_m2 }} m²</small>@endif</span>
                            <span class="status-pill {{ $room->status === 'active' ? 'status-active' : 'status-muted' }}">{{ $room->status === 'active' ? 'Active' : 'Inactive' }}</span>
                            <span class="row-arrow">→</span>
                        </a>
                    @empty
                        <p class="empty compact">Aucune pièce n’a encore été ajoutée.</p>
                    @endforelse
                </section>
            @endif

            @include('admin._media', ['media' => $property->media, 'mediable' => $property, 'managePermission' => 'manage properties', 'uploadRoute' => route('properties.media.store', $property)])

            @include('admin._notes', ['notes' => $property->notes, 'managePermission' => 'manage notes', 'storeRoute' => route('properties.notes.store', $property)])

            @can('manage properties')
                <form class="danger-zone" method="POST" action="{{ route('properties.destroy', $property) }}" onsubmit="return confirm('Supprimer ce bien ?')">
                    @csrf @method('DELETE')
                    <div><strong>Supprimer le bien</strong><p>Les pièces jointes et les photos associées seront également supprimées.</p></div>
                    <button class="button danger" type="submit">Supprimer</button>
                </form>
            @endcan
        </div>

        <aside class="detail-aside">
            @include('admin._map', ['address' => $mapAddress, 'title' => 'Carte'])
        </aside>
    </div>
@endsection
