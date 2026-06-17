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
<div class="role-show-wrapper fade-in">

    <div class="page-header">
        <div class="page-header-left">
            <h2>
                <i class="bi bi-shield-check me-2 text-success"></i>
                {{ ucfirst($role['name'] ?? 'Rôle') }}
            </h2>
            <p>Détails du rôle et permissions associées</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>

            @if(!($role['is_system'] ?? false))
                <a href="{{ route('admin.roles.edit', $role['id'] ?? '') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <div class="text-muted small">ID</div>
                        <div class="fw-semibold">{{ $role['id'] ?? '' }}</div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <div class="text-muted small">Statut</div>
                        <div class="fw-semibold">
                            @if($role['name'] === 'superadmin' || ($role['is_system'] ?? false))
                                <span class="badge text-bg-dark">
                                    <i class="bi bi-lock-fill me-1"></i> Système
                                </span>
                            @else
                                <span class="badge text-bg-primary">
                                    <i class="bi bi-shield-check me-1"></i> Personnalisé
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0">Permissions</h5>
                @php
                    $permissions = $role['permissions'] ?? [];
                    $count = is_array($permissions) ? count($permissions) : 0;
                @endphp
                <div class="text-muted small">{{ $count }} permission(s)</div>
            </div>

            @if(!empty($permissions) && $count > 0)
                <div class="permissions-grid">
                    @foreach($permissions as $permission)
                        @php
                            $permName = is_array($permission) ? ($permission['name'] ?? '') : $permission;
                            $label = is_array($permission) ? (ucfirst(explode('.', $permName)[1] ?? $permName)) : ucfirst(explode('.', $permName)[1] ?? $permName);
                        @endphp

                        <label class="permission-item mb-2">
                            <input type="checkbox" checked disabled>
                            <span class="permission-label">{{ $label }}</span>
                            @if(is_string($permName) && str_contains($permName, '.'))
                                <span class="permission-code text-muted">({{ $permName }})</span>
                            @endif
                        </label>
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

