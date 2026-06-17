@extends('admin.layouts.app')

@section('title', 'Modifier un rôle')
@section('page-title', 'Modifier un rôle')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/roles/create.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('admin/js/roles/create.js') }}"></script>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.roles.index') }}">Rôles & Permissions</a>
    </li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.roles.update', $role['id'] ?? '') }}" class="role-create-form">
    @csrf
    @method('PUT')

    <div class="form-section">

        {{-- ── Nom du rôle ──────────────────────────────────── --}}
        <div class="role-name-block">
            <div class="role-name-icon">
                <i class="bi bi-shield-plus"></i>
            </div>
            <div class="role-name-field">
                <label for="role_name" class="role-name-label">
                    Nom du rôle
                    <span class="text-danger">*</span>
                </label>
                <input type="text"
                       id="role_name"
                       name="name"
                       class="role-name-input"
                       placeholder="ex : Gestionnaire…"
                       value="{{ old('name', $role['name'] ?? '') }}"
                       autocomplete="off">
            </div>
        </div>

        {{-- ── Séparateur ────────────────────────────────────── --}}
        <div class="permissions-divider">
            <span>Permissions associées au rôle</span>
        </div>

        {{-- ── Toolbar ───────────────────────────────────────── --}}
        <div class="permission-toolbar">
            <label class="perm-select-all-label">
                <input type="checkbox" id="select-all" class="perm-checkbox">
                <span class="perm-select-all-text">Tout sélectionner</span>
            </label>

            <span class="perm-counter" id="perm-counter">
                0 permission(s) sélectionnée(s)
            </span>
        </div>

        {{-- ── Grille permissions ───────────────────────────── --}}
        <div class="permissions-grid">
            @php
                $selectedPermissions = collect($role['permissions'] ?? [])->map(fn($p) => $p['name'] ?? $p)->values()->all();
            @endphp

            @foreach(($permissions ?? []) as $group)
                @php
                    $module = $group['module'] ?? '';
                    $items  = $group['permissions'] ?? [];
                @endphp

                <div class="permission-card">
                    <div class="permission-card-header">
                        <label class="perm-module-label">
                            <input type="checkbox"
                                   class="perm-checkbox module-checkbox"
                                   {{ isset($module) && count($items) > 0 ? '' : '' }}>
                            <div class="perm-module-icon">
                                <i class="bi bi-grid-3x3-gap"></i>
                            </div>
                            <span>{{ ucfirst(str_replace('_', ' ', $module)) }}</span>
                        </label>
                        <span class="perm-module-count">{{ count($items) }}</span>
                    </div>

                    <div class="permission-card-body">
                        @foreach($items as $permission)
                            @php
                                $permName = $permission['name'] ?? '';
                                $checked  = in_array($permName, $selectedPermissions, true);
                            @endphp

                            <label class="permission-item">
                                <input type="checkbox"
                                       class="perm-checkbox perm-item-checkbox"
                                       name="permissions[]"
                                       value="{{ $permName }}"
                                       {{ $checked ? 'checked' : '' }}>
                                <span class="perm-item-action">
                                    {{ ucfirst($permission['action'] ?? ($permission['name'] ?? '')) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="role-create-actions">
            <button type="submit" class="btn btn-primary">
                Enregistrer le rôle
            </button>
        </div>

    </div>
</form>
@endsection

