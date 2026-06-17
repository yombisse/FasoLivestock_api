@extends('admin.layouts.app')

@section('title', 'Rôles & Permissions')
@section('page-title', 'Rôles & Permissions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/roles/index.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Rôles & Permissions</li>
@endsection

@section('content')
<div x-data="rolesIndex()" class="fade-in">

    {{-- ── En-tête ────────────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2>
                <i class="bi bi-shield-check me-2 text-success"></i>
                Rôles & Permissions
            </h2>
            <p>Gérez les rôles et leurs permissions associées</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.trashed') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-archive"></i> Archivés
            </a>

            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Nouveau rôle
            </a>
        </div>
    </div>

    {{-- ── Grille des rôles ───────────────────────────────── --}}
    @if(!empty($roles) && count($roles) > 0)

        <div class="roles-grid">
            @foreach($roles as $role)

                @php
                    $permissions = $role['permissions'] ?? [];
                @endphp

                <div class="role-card {{ $role['is_system'] ? 'system' : '' }}">

                    {{-- Header --}}
                    <div class="role-card-header">
                        <div class="d-flex align-items-center gap-2">

                            <div class="role-icon {{ $role['is_system'] ? 'system' : '' }}">
                                <i class="bi bi-shield{{ $role['is_system'] ? '-fill' : '' }}"></i>
                            </div>

                            <div>
                                <h3 class="role-name">{{ ucfirst($role['name']) }}</h3>

                                <div class="role-meta">
                                    <span>{{ count($permissions) }} permission(s)</span>

                                    @if($role['is_system'])
                                        <span class="system-badge">
                                            <i class="bi bi-lock-fill"></i> Système
                                        </span>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Permissions preview --}}
                    <div class="permissions-preview">
                        <div class="permissions-preview-title">Permissions</div>

                        <div class="permissions-tags">

                            @forelse(array_slice($permissions, 0, 6) as $perm)
                                <span class="permission-tag">{{ $perm }}</span>
                            @empty
                                <span class="text-muted" style="font-size:.78rem">
                                    Aucune permission
                                </span>
                            @endforelse

                            @if(count($permissions) > 6)
                                <span class="permission-tag more">
                                    +{{ count($permissions) - 6 }} autres
                                </span>
                            @endif

                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="role-card-footer">

                        <div class="role-users-count">
                            <i class="bi bi-people"></i>
                            {{ $role['users_count'] }} utilisateur(s)
                        </div>

                        <div class="role-actions">

                            {{-- Voir --}}
                            <a href="{{ route('admin.roles.show', $role['id']) }}"
                               class="btn btn-icon btn-outline-secondary"
                               title="Voir les détails">
                                <i class="bi bi-eye"></i>
                            </a>

                            @if(!$role['is_system'])

                                {{-- Modifier --}}
                                <a href="{{ route('admin.roles.edit', $role['id']) }}"
                                   class="btn btn-icon btn-outline-primary"
                                   title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Archiver --}}
                                <button type="button"
                                        class="btn btn-icon btn-outline-danger"
                                        title="Archiver"
                                        @click="deleteRole(
                                            '{{ $role['id'] }}',
                                            '{{ addslashes($role['name']) }}'
                                        )">
                                    <i class="bi bi-archive"></i>
                                </button>

                                <form id="form-delete-{{ $role['id'] }}"
                                      method="POST"
                                      action="{{ route('admin.roles.destroy', $role['id']) }}"
                                      style="display:none">
                                    @csrf
                                    @method('DELETE')
                                </form>

                            @endif

                        </div>
                    </div>

                </div>
            @endforeach
        </div>

    @else

        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-shield"></i>
            </div>

            <p>Aucun rôle trouvé</p>

            <a href="{{ route('admin.roles.create') }}"
               class="btn btn-primary btn-sm mt-2">
                <i class="bi bi-plus-lg"></i> Créer un rôle
            </a>
        </div>

    @endif

    {{-- ── Modal confirmation ─────────────────────────────── --}}
    <div class="modal fade"
         :class="{ show: confirmModal.show }"
         :style="confirmModal.show ? 'display:block' : ''"
         x-show="confirmModal.show"
         x-transition.opacity
         x-cloak>

        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle text-danger"></i>
                        <span x-text="confirmModal.title"></span>
                    </h5>

                    <button type="button"
                            class="btn-close"
                            @click="confirmModal.show = false">
                    </button>
                </div>

                <div class="modal-body" x-html="confirmModal.message"></div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="confirmModal.show = false">
                        Annuler
                    </button>

                    <button type="button"
                            class="btn btn-danger btn-sm"
                            @click="confirmAction()">
                        Confirmer
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal-backdrop fade show"
         x-show="confirmModal.show"
         x-transition.opacity
         @click="confirmModal.show = false"
         x-cloak>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/roles/index.js') }}"></script>
@endpush