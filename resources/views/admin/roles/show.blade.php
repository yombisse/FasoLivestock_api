@extends('admin.layouts.app')

@section('title', 'Détail du rôle')
@section('page-title', 'Détail du rôle')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/roles/show.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('admin/js/roles/show.js') }}"></script>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.roles.index') }}">Rôles & Permissions</a>
    </li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('content')
<div class="role-show-wrapper fade-in"
     x-data="roleShow()"
     data-role-id="{{ $role['id'] ?? '' }}">

    {{-- ── En-tête ──────────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2>
                <i class="bi bi-shield-check me-2 text-success"></i>
                {{ ucfirst($role['name'] ?? 'Rôle') }}
            </h2>
            <p>Détails du rôle et permissions associées</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>

            @if(!($role['is_system'] ?? false))
                <a href="{{ route('admin.roles.edit', $role['id'] ?? '') }}"
                   class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            {{-- ── Meta (ID / Statut) ───────────────────── --}}
            <div class="role-meta-grid">
                <div class="role-meta-item">
                    <span class="role-meta-label">ID</span>
                    <span class="role-meta-value">{{ $role['id'] ?? '—' }}</span>
                </div>

                <div class="role-meta-item">
                    <span class="role-meta-label">Nom</span>
                    <span class="role-meta-value">{{ ucfirst($role['name'] ?? '—') }}</span>
                </div>

                <div class="role-meta-item">
                    <span class="role-meta-label">Type</span>
                    <span class="role-meta-value">
                        @if($role['name'] === 'superadmin' || ($role['is_system'] ?? false))
                            <span class="badge text-bg-dark">
                                <i class="bi bi-lock-fill me-1"></i> Système
                            </span>
                        @else
                            <span class="badge text-bg-primary">
                                <i class="bi bi-shield-check me-1"></i> Personnalisé
                            </span>
                        @endif
                    </span>
                </div>

                <div class="role-meta-item">
                    <span class="role-meta-label">Créé le</span>
                    <span class="role-meta-value">
                        {{ isset($role['created_at'])
                            ? \Carbon\Carbon::parse($role['created_at'])->format('d/m/Y')
                            : '—' }}
                    </span>
                </div>
            </div>

            {{-- ── Section utilisateurs ─────────────────── --}}
            <div class="section-divider">
                <span><i class="bi bi-people"></i> Utilisateurs assignés</span>
            </div>

            <div class="section-header">
                <h5><i class="bi bi-people"></i> Utilisateurs du rôle</h5>
                <span class="section-counter">
                    <span id="role-users-count">0</span> utilisateur(s)
                </span>
            </div>

            @php $isSystem = (bool)($role['is_system'] ?? false); @endphp

            @if(!$isSystem)
                <div class="role-users-attach mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-8">
                            <label class="form-label mb-1" for="role-user-select">
                                Ajouter un utilisateur
                            </label>
                            <select id="role-user-select" class="form-select" disabled>
                                <option value="">Chargement…</option>
                            </select>
                            <div class="form-text">
                                Sélectionnez un utilisateur pour lui assigner ce rôle.
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <button type="button"
                                    id="role-user-attach-btn"
                                    class="btn btn-primary w-100"
                                    disabled>
                                <i class="bi bi-plus-lg me-1"></i> Ajouter
                            </button>
                        </div>
                    </div>

                    <div id="role-users-error"
                         class="alert alert-danger mt-3 d-none"
                         role="alert"></div>
                    <div id="role-users-success"
                         class="alert alert-success mt-3 d-none"
                         role="alert"></div>
                </div>
            @endif

            {{-- Liste utilisateurs --}}
            <div class="role-users-list">

                <div id="role-users-loading" class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <p>Chargement des utilisateurs…</p>
                </div>

                <div id="role-users-container" class="d-none">
                    <div class="users-table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="role-users-tbody">
                                {{-- rempli via JS --}}
                            </tbody>
                        </table>
                    </div>

                    <div id="role-users-empty" class="empty-state d-none">
                        <div class="empty-state-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <p>Aucun utilisateur assigné à ce rôle.</p>
                    </div>
                </div>

            </div>

            {{-- ── Section permissions ──────────────────── --}}
            @php
                $permissions = $role['permissions'] ?? [];
                $count = is_array($permissions) ? count($permissions) : 0;
            @endphp

            <div class="section-divider">
                <span><i class="bi bi-shield-lock"></i> Permissions</span>
            </div>

            <div class="section-header">
                <h5><i class="bi bi-shield-lock"></i> Permissions associées</h5>
                <span class="section-counter">{{ $count }} permission(s)</span>
            </div>

            @if($count > 0)
                <div class="permissions-grid">
                    @foreach($permissions as $permission)
                        @php
                            $permName = is_array($permission)
                                ? ($permission['name'] ?? '')
                                : $permission;
                            $parts  = explode('.', $permName);
                            $action = ucfirst($parts[1] ?? $permName);
                            $module = ucfirst($parts[0] ?? '');
                        @endphp

                        <div class="permission-item">
                            <input type="checkbox" checked disabled>
                            <span class="permission-label">{{ $action }}</span>
                            @if(str_contains($permName, '.'))
                                <span class="permission-code">{{ $module }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-shield"></i>
                    </div>
                    <p>Aucune permission associée à ce rôle.</p>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection