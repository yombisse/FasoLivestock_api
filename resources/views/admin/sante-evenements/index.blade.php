@extends('admin.layouts.ferme')

@section('title', 'Événements sanitaires')
@section('page-title', 'Événements sanitaires')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/shared/table-list.css') }}">
<style>
    .sante-table {
        font-size: 0.875rem;
    }
    .sante-table th {
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.75rem;
        background-color: #f8f9fa;
    }
    .sante-table td {
        padding: 0.75rem;
        vertical-align: middle;
    }
    .sante-table .animal-info {
        font-size: 0.875rem;
    }
    .sante-table .animal-number {
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
    }
    .sante-table .animal-espece {
        font-size: 0.8rem;
        color: #666;
    }
    .sante-table .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.75rem;
    }
    .sante-table .description {
        font-size: 0.85rem;
        color: #555;
        max-width: 250px;
    }
    .sante-table .date {
        font-weight: 500;
        color: #333;
        font-size: 0.85rem;
    }
    .sante-table .cost {
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
    }
    .sante-table .actions {
        font-size: 0.875rem;
    }
    .sante-table .btn-icon {
        padding: 0.35rem;
        font-size: 0.9rem;
    }
    .page-header h2 {
        font-size: 1.5rem;
    }
    .page-header p {
        font-size: 0.9rem;
    }
    .filters-bar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .filters-bar input,
    .filters-bar select {
        font-size: 0.875rem;
        padding: 0.5rem;
    }
    .filters-bar button {
        font-size: 0.875rem;
        padding: 0.5rem 0.875rem;
    }
    .filter-count {
        font-size: 0.875rem;
    }
</style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Événements sanitaires</li>
@endsection

@section('content')
<div x-data="santeEvenementsIndex()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-heart-pulse me-2 text-danger"></i>Événements sanitaires</h2>
            <p>Suivez les vaccinations, traitements et consultations</p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center justify-content-between w-100">
            <a href="{{ route('admin.sante-evenements.statistiques', ['farm' => $farmId]) }}"
               class="btn btn-outline-info btn-sm">
                <i class="bi bi-graph-up"></i>
                Statistiques
            </a>
            <ul class="nav nav-pills mb-0 gap-2">
                <li class="nav-item">
                    <a class="nav-link bg-primary text-white" href="{{ route('admin.sante-evenements.create', ['farm' => $farmId]) }}?type=VACCINATION">
                        <i class="bi bi-syringe me-1"></i>
                        Vacciner
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link bg-warning text-dark" href="{{ route('admin.sante-evenements.create', ['farm' => $farmId]) }}?type=TRAITEMENT">
                        <i class="bi bi-capsule me-1"></i>
                        Traiter
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link bg-danger text-white" href="{{ route('admin.sante-evenements.create', ['farm' => $farmId]) }}?type=MALADIE">
                        <i class="bi bi-thermometer-high me-1"></i>
                        Maladie
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link bg-info text-white" href="{{ route('admin.sante-evenements.create', ['farm' => $farmId]) }}?type=CONTROLE">
                        <i class="bi bi-clipboard2-pulse me-1"></i>
                        Contrôler
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.sante-evenements.index', ['farm' => $farmId]) }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Rechercher..."
                       value="{{ request('search') }}"
                       x-ref="searchInput">
            </div>

            {{-- Type d'événement --}}
            <select name="type" class="form-select form-select-sm">
                <option value="">Tous les types</option>
                <option value="VACCINATION" {{ request('type') === 'VACCINATION' ? 'selected' : '' }}>Vaccination</option>
                <option value="TRAITEMENT" {{ request('type') === 'TRAITEMENT' ? 'selected' : '' }}>Traitement</option>
                <option value="MALADIE" {{ request('type') === 'MALADIE' ? 'selected' : '' }}>Maladie</option>
                <option value="CONSULTATION" {{ request('type') === 'CONSULTATION' ? 'selected' : '' }}>Consultation</option>
            </select>

            {{-- Période --}}
            <input type="date"
                   name="date_debut"
                   class="form-control form-control-sm"
                   value="{{ request('date_debut') }}"
                   placeholder="Date début">

            <input type="date"
                   name="date_fin"
                   class="form-control form-control-sm"
                   value="{{ request('date_fin') }}"
                   placeholder="Date fin">

            {{-- Bouton recherche --}}
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i>
                Filtrer
            </button>

            {{-- Reset --}}
            @if(request()->hasAny(['search', 'type', 'date_debut', 'date_fin']))
            <a href="{{ route('admin.sante-evenements.index', ['farm' => $farmId]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} événement(s)
            </span>

        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────── --}}
    <div class="users-card">
        <div class="table-responsive">
            <table class="table sante-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Animal</th>
                        <th>Coût</th>
                        <th>Statut actuel</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evenements as $evenement)
                    <tr>
                        {{-- Date --}}
                        <td class="date">
                            {{ \Carbon\Carbon::parse($evenement['date_evenement'])->format('d/m/Y') }}
                        </td>

                        {{-- Type --}}
                        <td>
                            <span class="badge bg-info">
                                {{ $evenement['type']['nom_type'] ?? '—' }}
                            </span>
                        </td>

                        {{-- Animal --}}
                        <td>
                            <div class="animal-info">
                                <div class="animal-number">{{ $evenement['animal']['numero_identification'] ?? '—' }}</div>
                                <div class="animal-espece">{{ $evenement['animal']['espece']['nom_espece'] ?? '—' }}</div>
                            </div>
                        </td>

                        {{-- Coût --}}
                        <td class="cost">
                            @if(isset($evenement['cout']) && $evenement['cout'] > 0)
                                {{ number_format($evenement['cout'], 0, ',', ' ') }} FCFA
                            @else
                                —
                            @endif
                        </td>

                        {{-- Statut actuel --}}
                        <td>
                            <span class="badge bg-success">{{ $evenement['statut_apres'] ?? '—' }}</span>
                        </td>

                        {{-- Description --}}
                        <td class="description">
                            {{ \Illuminate\Support\Str::limit($evenement['description'] ?? '—', 50) }}
                        </td>

                        {{-- Actions --}}
                        <td>
                            <div class="actions-cell justify-content-end">
                                {{-- Voir --}}
                                <a href="{{ route('admin.sante-evenements.show', ['farm' => $farmId, 'sante_evenement' => $evenement['id']]) }}"
                                   class="btn btn-icon btn-outline-primary"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Modifier --}}
                                <a href="{{ route('admin.sante-evenements.edit', ['farm' => $farmId, 'sante_evenement' => $evenement['id']]) }}"
                                   class="btn btn-icon btn-outline-warning"
                                   title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Archiver --}}
                                <button type="button"
                                        class="btn btn-icon btn-outline-danger"
                                        title="Archiver"
                                        @click="deleteEvenement(
                                            @js($evenement['id']),
                                            @js($evenement['type']['nom_type'] ?? 'Événement')
                                        )">
                                    <i class="bi bi-archive"></i>
                                </button>

                                {{-- Formulaire caché --}}
                                <form id="form-delete-{{ $evenement['id'] }}"
                                      method="POST"
                                      action="{{ route('admin.sante-evenements.destroy', ['farm' => $farmId, 'sante_evenement' => $evenement['id']]) }}"
                                      style="display:none">
                                    @csrf @method('DELETE')
                                </form>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-heart-pulse"></i>
                                </div>
                                <p>Aucun événement sanitaire trouvé</p>
                                @if(request()->hasAny(['search', 'type', 'date_debut', 'date_fin']))
                                    <a href="{{ route('admin.sante-evenements.index', ['farm' => $farmId]) }}"
                                       class="btn btn-outline-primary btn-sm mt-2">
                                        Réinitialiser les filtres
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ───────────────────────────────── --}}
        @if(isset($meta['last_page']) && $meta['last_page'] > 1)
        <div class="pagination-bar">
            <span>
                Page {{ $meta['current_page'] }} sur {{ $meta['last_page'] }}
                — {{ $meta['total'] }} résultat(s)
            </span>
            <nav>
                <ul class="pagination">
                    {{-- Précédent --}}
                    <li class="page-item {{ $meta['current_page'] <= 1 ? 'disabled' : '' }}">
                        <a class="page-link"
                           href="{{ request()->fullUrlWithQuery(['page' => $meta['current_page'] - 1]) }}">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    {{-- Pages --}}
                    @for($i = 1; $i <= $meta['last_page']; $i++)
                        @if($i == 1 || $i == $meta['last_page'] || abs($i - $meta['current_page']) <= 1)
                        <li class="page-item {{ $i == $meta['current_page'] ? 'active' : '' }}">
                            <a class="page-link"
                               href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">
                                {{ $i }}
                            </a>
                        </li>
                        @elseif(abs($i - $meta['current_page']) == 2)
                        <li class="page-item disabled">
                            <span class="page-link">…</span>
                        </li>
                        @endif
                    @endfor

                    {{-- Suivant --}}
                    <li class="page-item {{ $meta['current_page'] >= $meta['last_page'] ? 'disabled' : '' }}">
                        <a class="page-link"
                           href="{{ request()->fullUrlWithQuery(['page' => $meta['current_page'] + 1]) }}">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif

    </div>

   {{-- ══════════════════════════════════════════════════════
     MODAL CONFIRMATION (Alpine-only)
═══════════════════════════════════════════════════════ --}}

    {{-- Backdrop --}}
    <div x-cloak
        x-show="confirmModal.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="confirmModal.show = false"
        style="position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)">
    </div>

    {{-- Modal --}}
    <div x-cloak
        x-show="confirmModal.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="position:fixed;inset:0;z-index:1055;overflow-y:auto;pointer-events:none">

        <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:1rem;pointer-events:none">

            <div style="width:100%;max-width:420px;pointer-events:auto">
                <div class="confirm-modal-box">

                    {{-- Icône centrale --}}
                    <div class="confirm-modal-icon icon-danger">
                        <i class="bi fs-4 bi-trash3"></i>
                    </div>

                    {{-- Titre --}}
                    <h6 class="confirm-modal-title" x-text="confirmModal.title"></h6>

                    {{-- Message --}}
                    <p class="confirm-modal-message" x-html="confirmModal.message"></p>

                    {{-- Actions --}}
                    <div class="confirm-modal-actions">
                        <button type="button"
                                class="btn-confirm-cancel"
                                @click="confirmModal.show = false">
                            Annuler
                        </button>
                        <button type="button"
                                class="btn-confirm-ok ok-danger"
                                @click="confirmAction()">
                            Confirmer
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/sante-evenements/index.js') }}"></script>
@endpush
