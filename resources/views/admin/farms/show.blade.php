@extends('admin.layouts.app')

@section('title', 'Détails de la ferme')
@section('page-title', 'Détails de la ferme')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/common/detail.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.farms.index') }}">Fermes</a></li>
    <li class="breadcrumb-item active">Détails</li>
@endsection

@section('content')
<div class="detail-page farm-show fade-in">

    {{-- ─── Header card ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center gap-4 mb-4">
                <div class="avatar">
                    {{ strtoupper(substr($farm['name'] ?? 'F', 0, 1)) }}
                </div>
                <div>
                    <h4 class="mb-1">{{ $farm['name'] ?? 'Ferme' }}</h4>
                    <p class="text-muted mb-0">{{ $farm['location'] ?? 'Non défini' }}</p>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-success">{{ $farm['status'] ?? 'ACTIF' }}</span>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">{{ $farm['animals_count'] ?? 0 }}</div>
                    <div class="stat-label">Animaux</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $farm['type_elevage'] ?? 'Non défini' }}</div>
                    <div class="stat-label">Type d'élevage</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        {{ !empty($farm['created_at']) ? \Carbon\Carbon::parse($farm['created_at'])->format('d/m/Y') : '—' }}
                    </div>
                    <div class="stat-label">Créée le</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        {{ !empty($farm['updated_at']) ? \Carbon\Carbon::parse($farm['updated_at'])->format('d/m/Y') : '—' }}
                    </div>
                    <div class="stat-label">Modifiée le</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Details ─────────────────────────────────────────── --}}
    <div class="card mt-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Nom</div>
                        <div class="value">{{ $farm['name'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Localisation</div>
                        <div class="value">{{ $farm['location'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Type d'élevage</div>
                        <div class="value">{{ $farm['type_elevage'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Description</div>
                        <div class="value">{{ $farm['description'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Propriétaire</div>
                        <div class="value">{{ $farm['owner']['name'] ?? '—' }}</div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="field">
                        <div class="label">Statut</div>
                        <div class="value">
                            <span class="badge {{ ($farm['status'] ?? 'ACTIF') === 'ACTIF' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $farm['status'] ?? 'ACTIF' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Actions ─────────────────────────────────────────── --}}
    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="{{ route('admin.farms.edit', $farm['id']) }}" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <a href="{{ route('admin.farms.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
