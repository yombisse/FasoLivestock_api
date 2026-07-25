@extends('admin.layouts.ferme')

@section('title', 'Gestion des lots')
@section('page-title', 'Gestion des lots')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/shared/table-list.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Lots</li>
@endsection

@section('content')
<div x-data="lotsIndex()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-grid me-2 text-success"></i>Gestion des lots</h2>
            <p>Gérez les lots d'animaux de vos fermes</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.lots.trashed', ['farm' => $farmId]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-archive"></i>
                Archivés
            </a>
            <a href="{{ route('admin.lots.create', ['farm' => $farmId]) }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i>
                Nouveau lot
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.lots.index', ['farm' => $farmId]) }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       placeholder="Rechercher par nom, description..."
                       value="{{ request('search') }}"
                       x-ref="searchInput">
            </div>

            {{-- Bouton recherche --}}
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i>
                Rechercher
            </button>

            {{-- Reset --}}
            @if(request()->hasAny(['search']))
            <a href="{{ route('admin.lots.index', ['farm' => $farmId]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} lot(s)
            </span>

        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────── --}}
    <div class="table-list-card">
        <div class="table-responsive">
            <table class="table-list">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Ferme</th>
                        <th>Nb animaux</th>
                        <th>Description</th>
                        <th>Créé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lots as $lot)
                    <tr>
                        {{-- Nom --}}
                        <td>
                            <div class="table-avatar">
                                <div class="table-avatar-icon">
                                    <i class="bi bi-grid"></i>
                                </div>
                                <div class="table-avatar-info">
                                    <div class="table-avatar-name">{{ $lot['nom_lot'] ?? '—' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Ferme --}}
                        <td>{{ $lot['farm']['name'] ?? '—' }}</td>

                        {{-- Nb animaux --}}
                        <td>
                            <span class="table-badge table-badge-info">
                                {{ $lot['animals_count'] ?? 0 }}
                            </span>
                        </td>

                        {{-- Description --}}
                        <td>{{ \Illuminate\Support\Str::limit($lot['description'] ?? '—', 30) }}</td>

                        {{-- Date --}}
                        <td style="font-size:.78rem;color:var(--text-muted)">
                            {{ \Carbon\Carbon::parse($lot['created_at'])->format('d/m/Y') }}
                        </td>

                        {{-- Actions --}}
                        <td>
                            <div class="table-actions">
                                {{-- Voir --}}
                                <a href="{{ route('admin.lots.show', ['farm' => $farmId, 'lot' => $lot['id']]) }}"
                                   class="btn-icon"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Modifier --}}
                                <a href="{{ route('admin.lots.edit', ['farm' => $farmId, 'lot' => $lot['id']]) }}"
                                   class="btn-icon"
                                   title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Affecter animaux --}}
                                <a href="{{ route('admin.lots.assign', ['farm' => $farmId, 'id' => $lot['id']]) }}"
                                   class="btn-icon btn-outline-info"
                                   title="Affecter animaux">
                                    <i class="bi bi-box2-heart"></i>
                                </a>

                                {{-- Archiver --}}
                                <button type="button"
                                        class="btn-icon btn-outline-danger"
                                        title="Archiver"
                                        @click="deleteLot(
                                            @js($lot['id']),
                                            @js($lot['nom_lot'])
                                        )">
                                    <i class="bi bi-archive"></i>
                                </button>

                                {{-- Formulaire caché --}}
                                <form id="form-delete-{{ $lot['id'] }}"
                                      method="POST"
                                      action="{{ route('admin.lots.destroy', ['farm' => $farmId, 'lot' => $lot['id']]) }}"
                                      style="display:none">
                                    @csrf @method('DELETE')
                                </form>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="table-empty-state">
                                <div class="table-empty-state-icon">
                                    <i class="bi bi-grid"></i>
                                </div>
                                <p>Aucun lot trouvé</p>
                                @if(request()->hasAny(['search']))
                                    <a href="{{ route('admin.lots.index', ['farm' => $farmId]) }}"
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
        <div class="table-pagination">
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
══════════════════════════════════════════════════════ --}}

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
    <script src="{{ asset('admin/js/lots/index.js') }}"></script>
@endpush
