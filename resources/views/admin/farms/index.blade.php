@extends('admin.layouts.app')

@section('title', 'Fermes')
@section('page-title', 'Fermes')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/farms/index.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Fermes</li>
@endsection

@section('content')
<div x-data="farmsIndex()" class="fade-in">

    {{-- ── En-tête ────────────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-house-door me-2 text-success"></i>Fermes</h2>
            <p>Gérez les fermes de la plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.farms.trashed') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-archive"></i> Archivées
            </a>
            <a href="{{ route('admin.farms.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Nouvelle ferme
            </a>
        </div>
    </div>

    {{-- ── Filtres ─────────────────────────────────────────── --}}
    <form id="filter-form" method="GET" action="{{ route('admin.farms.index') }}">
        <div class="filters-bar">

            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Rechercher par nom, localisation…"
                       value="{{ request('search') }}">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i> Rechercher
            </button>

            @if(request()->hasAny(['search']))
                <a href="{{ route('admin.farms.index') }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg"></i> Réinitialiser
                </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} ferme(s)
            </span>

        </div>
    </form>

    {{-- ── Grille fermes ───────────────────────────────────── --}}
    @forelse($farms as $farm)

        @if($loop->first)
        <div class="farms-grid">
        @endif

        <div class="farm-card">

            {{-- Header --}}
            <div class="farm-card-header">
                <div class="farm-icon">
                    <i class="bi bi-house-door"></i>
                </div>
                <div class="farm-header-info">
                    <div class="farm-name">{{ $farm['name'] }}</div>
                    <div class="farm-location">
                        <i class="bi bi-geo-alt"></i>
                        {{ $farm['location'] ?? '—' }}
                    </div>
                </div>
                <div class="farm-actions-top">
                    <a href="{{ route('admin.farms.show', $farm['id']) }}"
                       class="btn btn-icon btn-outline-secondary"
                       title="Voir">
                        <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('admin.farms.edit', $farm['id']) }}"
                       class="btn btn-icon btn-outline-primary"
                       title="Modifier">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button"
                            class="btn btn-icon btn-outline-danger"
                            title="Archiver"
                            @click="deleteFarm(
                                @js($farm['id']),
                                @js($farm['name'])
                            )">
                        <i class="bi bi-archive"></i>
                    </button>
                </div>
            </div>

            {{-- Description --}}
            @if(!empty($farm['description']))
            <div class="farm-description">
                {{ Str::limit($farm['description'], 80) }}
            </div>
            @endif

            {{-- Stats --}}
            <div class="farm-stats">
                <div class="farm-stat">
                    <div class="farm-stat-value">{{ $farm['animals_count'] ?? 0 }}</div>
                    <div class="farm-stat-label">Animaux</div>
                </div>
                <div class="farm-stat">
                    <div class="farm-stat-value">{{ $farm['users_count'] ?? 0 }}</div>
                    <div class="farm-stat-label">Membres</div>
                </div>
            </div>

            {{-- Footer owner --}}
            <div class="farm-card-footer">
                <div class="farm-owner">
                    <div class="farm-owner-avatar">
                        {{ strtoupper(substr($farm['owner']['name'] ?? '?', 0, 1)) }}
                    </div>
                    <div class="farm-owner-info">
                        <div class="farm-owner-label">Propriétaire</div>
                        <div class="farm-owner-name">{{ $farm['owner']['name'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="farm-date">
                    {{ \Carbon\Carbon::parse($farm['created_at'])->format('d/m/Y') }}
                </div>
            </div>

            {{-- Formulaire caché archivage --}}
            <form id="form-delete-{{ $farm['id'] }}"
                  method="POST"
                  action="{{ route('admin.farms.destroy', $farm['id']) }}"
                  style="display:none">
                @csrf @method('DELETE')
            </form>

        </div>

        @if($loop->last)
        </div>
        @endif

    @empty
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-house-door"></i>
            </div>
            <p>Aucune ferme trouvée</p>
            @if(request()->hasAny(['search']))
                <a href="{{ route('admin.farms.index') }}"
                   class="btn btn-outline-primary btn-sm mt-2">
                    Réinitialiser les filtres
                </a>
            @endif
        </div>
    @endforelse

    {{-- ── Pagination ──────────────────────────────────────── --}}
    @if(isset($meta['last_page']) && $meta['last_page'] > 1)
    <div class="pagination-bar">
        <span>
            Page {{ $meta['current_page'] }} sur {{ $meta['last_page'] }}
            — {{ $meta['total'] }} résultat(s)
        </span>
        <nav>
            <ul class="pagination">
                <li class="page-item {{ $meta['current_page'] <= 1 ? 'disabled' : '' }}">
                    <a class="page-link"
                       href="{{ request()->fullUrlWithQuery(['page' => $meta['current_page'] - 1]) }}">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>

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

    {{-- ── Modal confirmation ──────────────────────────────── --}}
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

                    <div class="confirm-modal-icon"
                         :class="{
                             'icon-danger':  confirmModal.type === 'danger',
                             'icon-warning': confirmModal.type === 'warning',
                             'icon-success': confirmModal.type === 'success',
                         }">
                        <i class="bi fs-4"
                           :class="{
                               'bi-archive':     confirmModal.type === 'danger',
                               'bi-pause-circle':confirmModal.type === 'warning',
                               'bi-play-circle': confirmModal.type === 'success',
                           }"></i>
                    </div>

                    <h6 class="confirm-modal-title" x-text="confirmModal.title"></h6>
                    <p class="confirm-modal-message" x-html="confirmModal.message"></p>

                    <div class="confirm-modal-actions">
                        <button type="button"
                                class="btn-confirm-cancel"
                                @click="confirmModal.show = false">
                            Annuler
                        </button>
                        <button type="button"
                                class="btn-confirm-ok"
                                :class="{
                                    'ok-danger':  confirmModal.type === 'danger',
                                    'ok-warning': confirmModal.type === 'warning',
                                    'ok-success': confirmModal.type === 'success',
                                }"
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
    <script src="{{ asset('admin/js/farms/index.js') }}"></script>
@endpush