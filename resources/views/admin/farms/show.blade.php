@extends('admin.layouts.app')

@section('title', 'Détails de la ferme')
@section('page-title', 'Détails de la ferme')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.farms.index') }}">Fermes</a></li>
    <li class="breadcrumb-item active">Détails</li>
@endsection

@section('content')
<div class="farm-show fade-in">

    {{-- ─── Header card ─────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-3">
                <div class="farm-avatar">
                    {{ strtoupper(substr($farm['name'] ?? 'F', 0, 1)) }}
                </div>
                <div>
                    <h4>{{ $farm['name'] ?? 'Ferme' }}</h4>
                    <p class="text-muted mb-0">{{ $farm['location'] ?? 'Non défini' }}</p>
                </div>
            </div>
            <div class="ms-auto">
                <span class="badge bg-success">{{ $farm['status'] ?? 'ACTIF' }}</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value">{{ $farm['animals_count'] ?? 0 }}</div>
                        <div class="stat-label">Animaux</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value">{{ $farm['type_elevage'] ?? 'Non défini' }}</div>
                        <div class="stat-label">Type d'élevage</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value">{{ $farm['created_at'] ?? '—' }}</div>
                        <div class="stat-label">Créée le</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value">{{ $farm['updated_at'] ?? '—' }}</div>
                        <div class="stat-label">Modifiée le</div>
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
@push('styles')
<style>
.farm-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--primary, #16a34a);
    color: #fff;
    font-size: 1.5rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 0.5rem;
}

.stat-item .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text, #1e293b);
}

.stat-item .stat-label {
    font-size: 0.75rem;
    color: var(--text-muted, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 0.25rem;
}
</style>
@endpush
