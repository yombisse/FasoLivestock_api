@extends('admin.layouts.app')

@section('title', 'Mouvements')
@section('page-title', 'Mouvements')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/mouvements/index.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Mouvements</li>
@endsection

@section('content')
<div x-data="mouvementsIndex()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-arrow-left-right me-2 text-primary"></i>Mouvements</h2>
            <p>Historique des mouvements du cheptel</p>
        </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.mouvements.statistiques') }}"
                   class="btn btn-outline-info btn-sm">
                    <i class="bi bi-bar-chart"></i>
                    Statistiques
                </a>
            </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.mouvements.index') }}">
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
                    <option value="ACHAT" {{ request('type') === 'ACHAT' ? 'selected' : '' }}>Achat</option>
                    <option value="VENTE" {{ request('type') === 'VENTE' ? 'selected' : '' }}>Vente</option>
                    <option value="DECES" {{ request('type') === 'DECES' ? 'selected' : '' }}>Décès</option>
                    <option value="PERTE" {{ request('type') === 'PERTE' ? 'selected' : '' }}>Perte</option>
                    <option value="TRANSFERT" {{ request('type') === 'TRANSFERT' ? 'selected' : '' }}>Transfert</option>
                    <option value="ABATTAGE" {{ request('type') === 'ABATTAGE' ? 'selected' : '' }}>Abattage</option>
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

            <a href="{{ route('admin.mouvements.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i> Réinitialiser
            </a>

            <div class="filter-count">
                @if($mouvements instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{ $mouvements->total() }} mouvement(s)
                @else
                    {{ count($mouvements) }} mouvement(s)
                @endif
            </div>
        </div>
    </form>

    {{-- ── Tableau des mouvements ───────────────────────────── --}}
    <div class="mouvements-card">
        @if($mouvements && count($mouvements) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Animal</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Ferme source</th>
                            <th>Ferme destination</th>
                            <th>Coût</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mouvements as $mouvement)
                        <tr>
                            <td>
                                @if(isset($mouvement['animal']))
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar">
                                            {{ substr($mouvement['animal']['nom'] ?? 'A', 0, 1) }}
                                        </div>
                                        <div class="user-info">
                                            <div class="user-name">{{ $mouvement['animal']['nom'] ?? '—' }}</div>
                                            <small class="text-muted">{{ $mouvement['animal']['code'] ?? '' }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $type = $mouvement['type_evenement']['nom_type'] ?? '—';
                                    $badgeClass = match(strtoupper($type)) {
                                        'ACHAT' => 'bg-success',
                                        'VENTE' => 'bg-primary',
                                        'DECES' => 'bg-danger',
                                        'PERTE' => 'bg-warning text-dark',
                                        'TRANSFERT' => 'bg-info text-dark',
                                        'ABATTAGE' => 'bg-secondary',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $type }}</span>
                            </td>
                            <td>
                                @if(!empty($mouvement['date_evenement']))
                                    {{ \Carbon\Carbon::parse($mouvement['date_evenement'])->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(isset($mouvement['animal']['farm']))
                                    {{ $mouvement['animal']['farm']['name'] ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(isset($mouvement['farm_destination']))
                                    {{ $mouvement['farm_destination']['name'] ?? '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(isset($mouvement['cout']))
                                    {{ number_format($mouvement['cout'], 0, ',', ' ') }} FCFA
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <div class="actions-cell justify-content-end">
                                    <a href="{{ route('admin.mouvements.show', $mouvement['id']) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(isset($mouvement['animal']))
                                    <a href="{{ route('admin.mouvements.animal-history', $mouvement['animal']['id']) }}"
                                       class="btn btn-sm btn-outline-info"
                                       title="Historique animal">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                    @endif

                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── Pagination ───────────────────────────────────── --}}
            @if($mouvements instanceof \Illuminate\Pagination\LengthAwarePaginator && $mouvements->hasPages())
                <div class="pagination-bar">
                    <div class="pagination-info">
                        Affichage {{ $mouvements->firstItem() }} à {{ $mouvements->lastItem() }}
                        sur {{ $mouvements->total() }}
                    </div>
                    <div class="pagination-wrapper">
                        {{ $mouvements->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <p>Aucun mouvement trouvé.</p>
            </div>
        @endif
    </div>

    {{-- ── Modal de confirmation ───────────────────────────── --}}
    <div x-cloak
         x-show="confirmModal.show"
         class="modal-backdrop"
         style="display: none;">
        <div class="modal-content">
            <div class="confirm-modal-box">
                <div class="confirm-modal-icon icon-danger">
                    <i class="bi bi-exclamation-triangle"></i>
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
    <script src="{{ asset('admin/js/mouvements/index.js') }}"></script>
@endpush
