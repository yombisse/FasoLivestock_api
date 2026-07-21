@extends('admin.layouts.app')

@section('title', 'Rôles & Permissions')
@section('page-title', 'Rôles & Permissions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/shared/table-list.css') }}">
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

    {{-- ── Table des rôles ──────────────────────────────────── --}}
    @if(!empty($roles) && count($roles) > 0)

        <div class="table-list-card">
            <div class="table-responsive">
                <table class="table-list">
                    <thead>
                        <tr>
                            <th>Rôle</th>
                            <th>Permissions</th>
                            <th>Utilisateurs</th>
                            <th>Type</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            @php
                                $permissions = $role['permissions'] ?? [];
                            @endphp
                            <tr>
                                {{-- Rôle --}}
                                <td>
                                    <div class="table-avatar">
                                        <div class="table-avatar-icon role">
                                            <i class="bi bi-shield{{ $role['is_system'] ? '-fill' : '' }}"></i>
                                        </div>
                                        <div class="table-avatar-info">
                                            <div class="table-avatar-name">{{ ucfirst($role['name']) }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Permissions --}}
                                <td>
                                    @if(count($permissions) > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach(array_slice($permissions, 0, 3) as $perm)
                                                <span class="table-badge table-badge-info">{{ $perm }}</span>
                                            @endforeach
                                            @if(count($permissions) > 3)
                                                <span class="table-badge table-badge-secondary">+{{ count($permissions) - 3 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted" style="font-size:.78rem">Aucune</span>
                                    @endif
                                </td>

                                {{-- Utilisateurs --}}
                                <td>
                                    <span class="table-badge table-badge-primary">
                                        {{ $role['users_count'] }} utilisateur(s)
                                    </span>
                                </td>

                                {{-- Type --}}
                                <td>
                                    @if($role['is_system'])
                                        <span class="table-badge table-badge-warning">
                                            <i class="bi bi-lock-fill me-1"></i> Système
                                        </span>
                                    @else
                                        <span class="table-badge table-badge-success">Personnalisé</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div class="table-actions">
                                        {{-- Voir --}}
                                        <a href="{{ route('admin.roles.show', $role['id']) }}"
                                           class="btn-icon"
                                           title="Voir les détails">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        @if(!$role['is_system'])
                                            {{-- Modifier --}}
                                            <a href="{{ route('admin.roles.edit', $role['id']) }}"
                                               class="btn-icon"
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>

                                            {{-- Archiver --}}
                                            <button type="button"
                                                    class="btn-icon btn-outline-danger"
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @else

        <div class="table-empty-state">
            <div class="table-empty-state-icon">
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