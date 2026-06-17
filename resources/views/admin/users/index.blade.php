@extends('admin.layouts.app')

@section('title', 'Utilisateurs')
@section('page-title', 'Utilisateurs')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/users/index.css') }}">
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Utilisateurs</li>
@endsection

@section('content')
<div x-data="usersIndex()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-people me-2 text-success"></i>Utilisateurs</h2>
            <p>Gérez les comptes utilisateurs de la plateforme</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.trashed') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-archive"></i>
                Archivés
            </a>
            <a href="{{ route('admin.users.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i>
                Nouvel utilisateur
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.users.index') }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Rechercher par nom, email, téléphone..."
                       value="{{ request('search') }}"
                       x-ref="searchInput">
            </div>

            {{-- Filtre rôle --}}
            <select name="role"
                    class="form-select filter-select"
                    @change="autoSubmit()">
                <option value="">Tous les rôles</option>
                @foreach($roles as $role)
                    <option value="{{ $role['name'] }}"
                            {{ request('role') === $role['name'] ? 'selected' : '' }}>
                        {{ ucfirst($role['name']) }}
                    </option>
                @endforeach
            </select>

            {{-- Filtre statut --}}
            <select name="is_active"
                    class="form-select filter-select"
                    @change="autoSubmit()">
                <option value="">Tous les statuts</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>
                    Actifs
                </option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>
                    Inactifs
                </option>
            </select>

            {{-- Bouton recherche --}}
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i>
                Rechercher
            </button>

            {{-- Reset --}}
            @if(request()->hasAny(['search', 'role', 'is_active']))
            <a href="{{ route('admin.users.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} utilisateur(s)
            </span>

        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────── --}}
    <div class="users-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Téléphone</th>
                        <th>Rôles</th>
                        <th>Fermes</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        {{-- Avatar + Nom + Email --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar {{ $user['is_active'] ? '' : 'inactive' }}">
                                    {{ strtoupper(substr($user['name'], 0, 1)) }}
                                </div>
                                <div class="user-info">
                                    <div class="user-name">{{ $user['name'] }}</div>
                                    <div class="user-email">
                                        {{ $user['email'] ?? $user['telephone'] ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Téléphone --}}
                        <td>{{ $user['telephone'] ?? '—' }}</td>

                        {{-- Rôles --}}
                        <td>
                            @forelse($user['roles'] as $role)
                                <span class="role-badge {{ $role['name'] === 'superadmin' ? 'superadmin' : '' }}">
                                    <i class="bi bi-shield-check"></i>
                                    {{ ucfirst($role['name']) }}
                                </span>
                            @empty
                                <span class="text-muted" style="font-size:.78rem">—</span>
                            @endforelse
                        </td>

                        {{-- Fermes --}}
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $user['farms_count'] ?? 0 }}
                            </span>
                        </td>

                        {{-- Statut --}}
                        <td>
                            <span class="status-badge {{ $user['is_active'] ? 'active' : 'inactive' }}">
                                <span class="status-dot {{ $user['is_active'] ? 'active' : 'inactive' }}"></span>
                                {{ $user['is_active'] ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>

                        {{-- Date --}}
                        <td style="font-size:.78rem;color:var(--text-muted)">
                            {{ \Carbon\Carbon::parse($user['created_at'])->format('d/m/Y') }}
                        </td>

                        {{-- Actions --}}
                        <td>
                            <div class="actions-cell justify-content-end">

                                {{-- Voir --}}
                                <a href="{{ route('admin.users.show', $user['id']) }}"
                                   class="btn btn-icon btn-outline-secondary"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Modifier --}}
                                <a href="{{ route('admin.users.edit', $user['id']) }}"
                                   class="btn btn-icon btn-outline-primary"
                                   title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Toggle actif --}}
                                <button type="button"
                                        class="btn btn-icon {{ $user['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $user['is_active'] ? 'Désactiver' : 'Activer' }}"
                                        @click="toggleUser(
                                            @js($user['id']),
                                            @js($user['name']),
                                            @js($user['is_active'])
                                        )">
                                    <i class="bi {{ $user['is_active'] ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                </button>

                                {{-- Archiver --}}
                                @if(!collect($user['roles'])->contains('name', 'superadmin'))
                                <button type="button"
                                        class="btn btn-icon btn-outline-danger"
                                        title="Archiver"
                                        @click="deleteUser(
                                            @js($user['id']),
                                            @js($user['name'])
                                        )">
                                    <i class="bi bi-archive"></i>
                                </button>
                                @endif

                                {{-- Formulaires cachés --}}
                                <form id="form-toggle-{{ $user['id'] }}"
                                      method="POST"
                                      action="{{ route('admin.users.toggle-active', $user['id']) }}"
                                      style="display:none">
                                    @csrf @method('PATCH')
                                </form>

                                <form id="form-delete-{{ $user['id'] }}"
                                      method="POST"
                                      action="{{ route('admin.users.destroy', $user['id']) }}"
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
                                    <i class="bi bi-people"></i>
                                </div>
                                <p>Aucun utilisateur trouvé</p>
                                @if(request()->hasAny(['search', 'role', 'is_active']))
                                    <a href="{{ route('admin.users.index') }}"
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
                    <div class="confirm-modal-icon"
                        :class="{
                            'icon-danger':  confirmModal.type === 'danger',
                            'icon-warning': confirmModal.type === 'warning',
                            'icon-success': confirmModal.type === 'success',
                        }">
                        <i class="bi fs-4"
                        :class="{
                            'bi-trash3':       confirmModal.type === 'danger',
                            'bi-pause-circle': confirmModal.type === 'warning',
                            'bi-play-circle':  confirmModal.type === 'success',
                        }"></i>
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
    <script src="{{ asset('admin/js/users/index.js') }}"></script>
@endpush