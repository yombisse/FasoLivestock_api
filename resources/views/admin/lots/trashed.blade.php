@extends('admin.layouts.ferme')

@section('title', 'Lots archivés')
@section('page-title', 'Lots archivés')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/lots/index.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.lots.index') }}">Lots</a>
    </li>
    <li class="breadcrumb-item active">Archivés</li>
@endsection

@section('content')
<div x-data="lotsTrashed()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-archive me-2 text-warning"></i>Lots archivés</h2>
            <p>Les lots supprimés (archivés) peuvent être restaurés</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.lots.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Retour à la liste
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.lots.trashed') }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       class="form-control"
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
            <a href="{{ route('admin.lots.trashed') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} lot(s) archivé(s)
            </span>

        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────── --}}
    <div class="users-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Ferme</th>
                        <th>Nb animaux</th>
                        <th>Description</th>
                        <th>Archivé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lots as $lot)
                    <tr>
                        {{-- Nom --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar inactive">
                                    <i class="bi bi-grid"></i>
                                </div>
                                <div class="user-info">
                                    <div class="user-name">{{ $lot['nom_lot'] ?? '—' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Ferme --}}
                        <td>{{ $lot['farm']['name'] ?? '—' }}</td>

                        {{-- Nb animaux --}}
                        <td>
                            <span class="badge bg-secondary">
                                {{ $lot['animals_count'] ?? 0 }}
                            </span>
                        </td>

                        {{-- Description --}}
                        <td>{{ \Illuminate\Support\Str::limit($lot['description'] ?? '—', 30) }}</td>

                        {{-- Date archivage --}}
                        <td style="font-size:.78rem;color:var(--text-muted)">
                            {{ !empty($lot['deleted_at']) ? \Carbon\Carbon::parse($lot['deleted_at'])->format('d/m/Y H:i') : '—' }}
                        </td>

                        {{-- Actions --}}
                        <td>
                            <div class="actions-cell justify-content-end">

                                {{-- Restaurer --}}
                                <button type="button"
                                        class="btn btn-icon btn-outline-success"
                                        title="Restaurer"
                                        @click="restoreLot(
                                            @js($lot['id']),
                                            @js($lot['nom'])
                                        )">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>

                                {{-- Formulaire caché --}}
                                <form id="form-restore-{{ $lot['id'] }}"
                                      method="POST"
                                      action="{{ route('admin.lots.restore', $lot['id']) }}"
                                      style="display:none">
                                    @csrf
                                </form>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-archive"></i>
                                </div>
                                <p>Aucun lot archivé</p>
                                @if(request()->hasAny(['search']))
                                    <a href="{{ route('admin.lots.trashed') }}"
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
                    <div class="confirm-modal-icon icon-success">
                        <i class="bi fs-4 bi-arrow-counterclockwise"></i>
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
                                class="btn-confirm-ok ok-success"
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
    <script src="{{ asset('admin/js/lots/trashed.js') }}"></script>
@endpush
