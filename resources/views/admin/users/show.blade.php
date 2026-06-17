@extends('admin.layouts.app')

@section('title', 'Détail utilisateur')
@section('page-title', 'Détail utilisateur')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/users/show.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.users.index') }}">Utilisateurs</a>
    </li>
    <li class="breadcrumb-item active">Détail</li>
@endsection

@section('content')
<div x-data="userShow()" class="fade-in">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-person me-2 text-primary"></i>Détail utilisateur</h2>
            <p>Informations et rôles associés</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('admin.users.edit', $user['id']) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Modifier
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Nom</div>
                        <div class="value fw-semibold text-dark">{{ $user['name'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Email</div>
                        <div class="value fw-semibold text-dark">{{ $user['email'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Téléphone</div>
                        <div class="value fw-semibold text-dark">{{ $user['telephone'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Statut</div>
                        <div class="value fw-semibold">
                            @if (!empty($user['is_active']))
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Créé le</div>
                        <div class="value fw-semibold text-dark">
                            {{ !empty($user['created_at']) ? \Carbon\Carbon::parse($user['created_at'])->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Supprimé (archivé)</div>
                        <div class="value fw-semibold text-dark">
                            {{ !empty($user['deleted_at']) ? \Carbon\Carbon::parse($user['deleted_at'])->format('d/m/Y H:i') : '—' }}
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4"/>

            <div class="d-flex align-items-center justify-content-between mb-2">
                <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Rôles</h5>
                <span class="text-muted small">{{ count($user['roles'] ?? []) }} rôle(s)</span>
            </div>

            <div class="roles-list">
                @forelse(($user['roles'] ?? []) as $role)
                    <span class="badge rounded-pill bg-light text-dark border me-2 mb-2">
                        {{ $role['name'] ?? '—' }}
                    </span>
                @empty
                    <div class="text-muted">Aucun rôle</div>
                @endforelse
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- JS : séparé de la Blade --}}
    <script src="{{ asset('admin/js/users/show.js') }}"></script>
@endpush
