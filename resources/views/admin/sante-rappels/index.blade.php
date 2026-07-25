@extends('admin.layouts.ferme')

@section('title', 'Rappels sanitaires')
@section('page-title', 'Rappels sanitaires')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/sante-rappels/index.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Rappels sanitaires</li>
@endsection

@section('content')
<div x-data="santeRappelsIndex()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-bell me-2 text-warning"></i>Rappels sanitaires</h2>
            <p>Gérez les rappels de vaccination et de traitement</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sante-rappels.a-venir', ['farm' => $farmId]) }}"
               class="btn btn-outline-info btn-sm">
                <i class="bi bi-calendar-check"></i>
                À venir
            </a>
            <a href="{{ route('admin.sante-rappels.en-retard', ['farm' => $farmId]) }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-exclamation-triangle"></i>
                En retard
            </a>
            <a href="{{ route('admin.sante-rappels.create', ['farm' => $farmId]) }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i>
                Nouveau rappel
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.sante-rappels.index', ['farm' => $farmId]) }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       placeholder="Rechercher..."
                       value="{{ request('search') }}"
                       class="form-control">
            </div>

            {{-- Type --}}
            <div class="filter-select">
                <select name="type" class="form-select">
                    <option value="">Tous les types</option>
                    <option value="VACCINATION" {{ request('type') === 'VACCINATION' ? 'selected' : '' }}>Vaccination</option>
                    <option value="TRAITEMENT" {{ request('type') === 'TRAITEMENT' ? 'selected' : '' }}>Traitement</option>
                    <option value="CONTROLE" {{ request('type') === 'CONTROLE' ? 'selected' : '' }}>Contrôle</option>
                </select>
            </div>

            {{-- Statut --}}
            <div class="filter-select">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="EN_ATTENTE" {{ request('statut') === 'EN_ATTENTE' ? 'selected' : '' }}>En attente</option>
                    <option value="EFFECTUE" {{ request('statut') === 'EFFECTUE' ? 'selected' : '' }}>Effectué</option>
                    <option value="EN_RETARD" {{ request('statut') === 'EN_RETARD' ? 'selected' : '' }}>En retard</option>
                </select>
            </div>

            {{-- Date début --}}
            <div class="filter-date">
                <input type="date"
                       name="date_debut"
                       value="{{ request('date_debut') }}"
                       class="form-control"
                       placeholder="Du">
            </div>

            {{-- Date fin --}}
            <div class="filter-date">
                <input type="date"
                       name="date_fin"
                       value="{{ request('date_fin') }}"
                       class="form-control"
                       placeholder="Au">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-funnel"></i> Filtrer
            </button>

            <a href="{{ route('admin.sante-rappels.index', ['farm' => $farmId]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i> Réinitialiser
            </a>

            <div class="filter-count">
                @if($rappels instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $rappels->total() }} rappel(s)
                @else
                    {{ count($rappels) }} rappel(s)
                @endif
            </div>
        </div>
    </form>

    {{-- ── Tableau des rappels ───────────────────────────── --}}
    <div class="rappels-card">
        @if($rappels && count($rappels) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Animal</th>
                            <th>Type</th>
                            <th>Date prévue</th>
                            <th>Statut</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rappels as $rappel)
                        @php
                            $statut = $rappel['statut'] ?? 'EN_ATTENTE';
                            $rowClass = match($statut) {
                                'EN_RETARD' => 'table-danger',
                                'EFFECTUE' => 'table-success',
                                default => ''
                            };
                            $badgeClass = match($statut) {
                                'EN_ATTENTE' => 'bg-warning text-dark',
                                'EFFECTUE' => 'bg-success',
                                'EN_RETARD' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            $isUrgent = false;
                            if(!empty($rappel['date_prevue'])) {
                                $daysUntil = \Carbon\Carbon::parse($rappel['date_prevue'])->diffInDays(now(), false);
                                $isUrgent = $daysUntil >= 0 && $daysUntil <= 7;
                            }
                        @endphp
                        <tr class="{{ $rowClass }} {{ $isUrgent && $statut === 'EN_ATTENTE' ? 'table-warning' : '' }}">
                            <td>
                                @if(isset($rappel['animal']))
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar">
                                            {{ substr($rappel['animal']['nom'] ?? 'A', 0, 1) }}
                                        </div>
                                        <div class="user-info">
                                            <div class="user-name">{{ $rappel['animal']['nom'] ?? '—' }}</div>
                                            <small class="text-muted">{{ $rappel['animal']['code'] ?? '' }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $rappel['type'] ?? '—' }}</span>
                            </td>
                            <td>
                                @if(!empty($rappel['date_prevue']))
                                    {{ \Carbon\Carbon::parse($rappel['date_prevue'])->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $statut }}</span>
                            </td>
                            <td>
                                <small>{{ $rappel['description'] ?? '—' }}</small>
                            </td>
                            <td>
                                <div class="actions-cell justify-content-end">
                                    <a href="{{ route('admin.sante-rappels.show', ['farm' => $farmId, 'sante_rappel' => $rappel['id']]) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.sante-rappels.edit', ['farm' => $farmId, 'sante_rappel' => $rappel['id']]) }}"
                                       class="btn btn-sm btn-outline-secondary"
                                       title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if(in_array($statut, ['EN_ATTENTE', 'EN_RETARD']))
                                    <form id="form-realise-{{ $rappel['id'] }}"
                                          method="POST"
                                          action="{{ route('admin.sante-rappels.marquer-realise', ['farm' => $farmId, 'id' => $rappel['id']]) }}"
                                          style="display: none;">
                                        @csrf
                                    </form>
                                    <button type="button"
                                            @click="marquerRealise('{{ $rappel['id'] }}', '{{ $rappel['animal']['nom'] ?? 'ce rappel' }}')"
                                            class="btn btn-sm btn-outline-success"
                                            title="Marquer réalisé">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    @endif
                                    <form id="form-delete-{{ $rappel['id'] }}"
                                          method="POST"
                                          action="{{ route('admin.sante-rappels.destroy', ['farm' => $farmId, 'sante_rappel' => $rappel['id']]) }}"
                                          style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    <button type="button"
                                            @click="deleteRappel('{{ $rappel['id'] }}', '{{ $rappel['animal']['nom'] ?? 'ce rappel' }}')"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── Pagination ───────────────────────────────────── --}}
            @if($rappels instanceof \Illuminate\Pagination\LengthAwarePaginator && $rappels->hasPages())
                <div class="pagination-bar">
                    <div class="pagination-info">
                        Affichage {{ $rappels->firstItem() }} à {{ $rappels->lastItem() }}
                        sur {{ $rappels->total() }}
                    </div>
                    <div class="pagination-wrapper">
                        {{ $rappels->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-bell"></i>
                </div>
                <p>Aucun rappel trouvé.</p>
            </div>
        @endif
    </div>

    {{-- ─── Modal de confirmation ───────────────────────────── --}}
    <div x-cloak
         x-show="confirmModal.show"
         class="modal-backdrop"
         style="display: none;">
        <div class="modal-content">
            <div class="confirm-modal-box">
                <div class="confirm-modal-icon icon-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="confirm-modal-title" x-text="confirmModal.title"></h3>
                <p class="confirm-modal-message" x-html="confirmModal.message"></p>
                <div class="confirm-modal-actions">
                    <button type="button"
                            @click="confirmModal.show = false"
                            class="btn-confirm-cancel">
                        Annuler
                    </button>
                    <button type="button"
                            @click="confirmAction()"
                            class="btn-confirm-ok ok-success">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Modal de suppression ───────────────────────────── --}}
    <div x-cloak
         x-show="deleteModal.show"
         class="modal-backdrop"
         style="display: none;">
        <div class="modal-content">
            <div class="confirm-modal-box">
                <div class="confirm-modal-icon icon-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h3 class="confirm-modal-title" x-text="deleteModal.title"></h3>
                <p class="confirm-modal-message" x-html="deleteModal.message"></p>
                <div class="confirm-modal-actions">
                    <button type="button"
                            @click="deleteModal.show = false"
                            class="btn-confirm-cancel">
                        Annuler
                    </button>
                    <button type="button"
                            @click="confirmDelete()"
                            class="btn-confirm-ok ok-danger">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/sante-rappels/index.js') }}"></script>
@endpush
