@extends('admin.layouts.app')

@section('title', 'Événements sanitaires')
@section('page-title', 'Événements sanitaires')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/sante-evenements/index.css') }}">
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
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sante-evenements.statistiques') }}"
               class="btn btn-outline-info btn-sm">
                <i class="bi bi-graph-up"></i>
                Statistiques
            </a>
            <a href="{{ route('admin.sante-evenements.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i>
                Nouvel événement
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.sante-evenements.index') }}">
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
            <a href="{{ route('admin.sante-evenements.index') }}"
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
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Animal</th>
                        <th>Coût</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evenements as $evenement)
                    <tr>
                        {{-- Date --}}
                        <td style="font-size:.85rem">
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
                            @if(isset($evenement['animal']))
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar">
                                        <i class="bi bi-box2-heart"></i>
                                    </div>
                                    <div>
                                        <div class="user-name">{{ $evenement['animal']['numero_identification'] ?? '—' }}</div>
                                        <div class="user-role" style="font-size:.75rem;color:var(--text-muted)">
                                            {{ $evenement['animal']['espece']['nom_espece'] ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                —
                            @endif
                        </td>

                        {{-- Coût --}}
                        <td style="font-weight:600">
                            @if(isset($evenement['cout']) && $evenement['cout'] > 0)
                                {{ number_format($evenement['cout'], 0, ',', ' ') }} FCFA
                            @else
                                —
                            @endif
                        </td>

                        {{-- Description --}}
                        <td>{{ \Illuminate\Support\Str::limit($evenement['description'] ?? '—', 30) }}</td>

                        {{-- Actions --}}
                        <td>
                            <div class="actions-cell justify-content-end">
                                {{-- Voir --}}
                                <a href="{{ route('admin.sante-evenements.show', $evenement['id']) }}"
                                   class="btn btn-icon btn-outline-primary"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Modifier --}}
                                <a href="{{ route('admin.sante-evenements.edit', $evenement['id']) }}"
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
                                      action="{{ route('admin.sante-evenements.destroy', $evenement['id']) }}"
                                      style="display:none">
                                    @csrf @method('DELETE')
                                </form>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-heart-pulse"></i>
                                </div>
                                <p>Aucun événement sanitaire trouvé</p>
                                @if(request()->hasAny(['search', 'type', 'date_debut', 'date_fin']))
                                    <a href="{{ route('admin.sante-evenements.index') }}"
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
