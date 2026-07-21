@extends('admin.layouts.app')

@section('title', 'Événements Reproduction')
@section('page-title', 'Événements Reproduction')

@push('styles')
<style>
    .reproduction-table {
        font-size: 1rem;
    }
    .reproduction-table th {
        font-size: 1.1rem;
        font-weight: 600;
        padding: 1rem;
        background-color: #f8f9fa;
    }
    .reproduction-table td {
        padding: 1rem;
        vertical-align: middle;
    }
    .reproduction-table .animal-info {
        font-size: 1rem;
    }
    .reproduction-table .animal-number {
        font-weight: 600;
        color: #333;
    }
    .reproduction-table .animal-espece {
        font-size: 0.9rem;
        color: #666;
    }
    .reproduction-table .badge {
        font-size: 0.95rem;
        padding: 0.5rem 1rem;
    }
    .reproduction-table .description {
        font-size: 0.95rem;
        color: #555;
        max-width: 300px;
    }
    .reproduction-table .date {
        font-weight: 500;
        color: #333;
    }
    .reproduction-table .actions {
        font-size: 1rem;
    }
    .reproduction-table .btn-icon {
        padding: 0.5rem;
        font-size: 1.1rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0" style="font-size: 1.5rem;">Liste des événements de reproduction</h3>
                    <div class="d-flex gap-2 align-items-center">
                        <ul class="nav nav-pills mb-0 gap-2">
                            <li class="nav-item">
                                <a class="nav-link bg-danger text-white" href="{{ route('admin.evenements-reproduction.create') }}?type=SAILLIE">
                                    <i class="bi bi-heart-pulse me-1"></i>
                                    Saillie
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link bg-warning text-dark" href="{{ route('admin.evenements-reproduction.create') }}?type=GESTATION">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Gestation
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link bg-success text-white" href="{{ route('admin.evenements-reproduction.create') }}?type=MISE_BAS">
                                    <i class="bi bi-baby me-1"></i>
                                    Mise bas
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($evenements) && count($evenements) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover reproduction-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Animal</th>
                                        <th>Description</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($evenements as $evenement)
                                        <tr>
                                            <td class="date">
                                                {{ \Carbon\Carbon::parse($evenement['date_evenement'] ?? now())->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ $evenement['type']['nom_type'] ?? '-' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(isset($evenement['animal']))
                                                    <div class="animal-info">
                                                        <div class="animal-number">{{ $evenement['animal']['numero_identification'] ?? '—' }}</div>
                                                        <div class="animal-espece">{{ $evenement['animal']['espece']['nom_espece'] ?? '—' }}</div>
                                                    </div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="description">
                                                {{ \Illuminate\Support\Str::limit($evenement['description'] ?? '—', 50) }}
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ ($evenement['statut'] ?? '') === 'TERMINE' ? 'success' : 'warning' }}">
                                                    {{ $evenement['statut'] ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <a href="{{ route('admin.evenements-reproduction.show', $evenement['id']) }}" 
                                                   class="btn btn-icon btn-outline-primary" title="Voir">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.evenements-reproduction.edit', $evenement['id']) }}" 
                                                   class="btn btn-icon btn-outline-warning" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if(isset($meta['last_page']) && $meta['last_page'] > 1)
                            <div class="pagination mt-4">
                                {{ $evenements->appends(request()->query())->links() }}
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info" style="font-size: 1.1rem; padding: 1.5rem;">
                            <i class="bi bi-info-circle"></i> Aucun événement de reproduction trouvé.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
