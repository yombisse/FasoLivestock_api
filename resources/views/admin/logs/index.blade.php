@extends('admin.layouts.ferme')

@section('title', 'Logs d\'activité')
@section('page-title', 'Logs d\'activité')

@section('breadcrumb')
    <li class="breadcrumb-item active">Logs</li>
@endsection

@section('content')
<div class="logs-page fade-in">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-clock-history me-2 text-primary"></i>Logs d'activité</h2>
            <p>Historique des actions sur la ferme</p>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.logs.index', ['farm' => request()->route('farm')]) }}">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <select name="action" class="form-select">
                            <option value="">Toutes</option>
                            <option value="created" {{ $filters['action'] === 'created' ? 'selected' : '' }}>Création</option>
                            <option value="updated" {{ $filters['action'] === 'updated' ? 'selected' : '' }}>Modification</option>
                            <option value="deleted" {{ $filters['action'] === 'deleted' ? 'selected' : '' }}>Suppression</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type de modèle</label>
                        <select name="model_type" class="form-select">
                            <option value="">Tous</option>
                            <option value="User" {{ $filters['model_type'] === 'User' ? 'selected' : '' }}>Utilisateur</option>
                            <option value="Farm" {{ $filters['model_type'] === 'Farm' ? 'selected' : '' }}>Ferme</option>
                            <option value="Animal" {{ $filters['model_type'] === 'Animal' ? 'selected' : '' }}>Animal</option>
                            <option value="Transaction" {{ $filters['model_type'] === 'Transaction' ? 'selected' : '' }}>Transaction</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date début</label>
                        <input type="date" name="date_debut" class="form-control" value="{{ $filters['date_debut'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date fin</label>
                        <input type="date" name="date_fin" class="form-control" value="{{ $filters['date_fin'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Logs globaux</label>
                        <select name="include_global" class="form-select">
                            <option value="1" {{ ($filters['include_global'] ?? true) ? 'selected' : '' }}>Inclure</option>
                            <option value="0" {{ !($filters['include_global'] ?? true) ? 'selected' : '' }}>Exclure</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tableau des logs --}}
    <div class="card">
        <div class="card-body">
            @if(isset($error))
                <div class="alert alert-danger">{{ $error }}</div>
            @elseif(empty($logs))
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">Aucun log d'activité trouvé</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Utilisateur</th>
                                <th>Modèle</th>
                                <th>Description</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($log['created_at'])->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ $log['action'] === 'created' ? 'bg-success' : ($log['action'] === 'updated' ? 'bg-warning' : 'bg-danger') }}">
                                            {{ ucfirst($log['action']) }}
                                        </span>
                                    </td>
                                    <td>{{ $log['user']['name'] ?? '—' }}</td>
                                    <td>
                                        {{ $log['model_type'] ?? '—' }}
                                        @if(!$log['farm'])
                                            <span class="badge bg-info ms-1">Global</span>
                                        @endif
                                    </td>
                                    <td>{{ $log['description'] ?? '—' }}</td>
                                    <td class="text-muted small">{{ $log['ip_address'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if(isset($meta) && $meta['last_page'] > 1)
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            @if($meta['current_page'] > 1)
                                <li class="page-item">
                                    <a class="page-link" href="?page={{ $meta['current_page'] - 1 }}">Précédent</a>
                                </li>
                            @endif

                            @for($i = 1; $i <= $meta['last_page']; $i++)
                                @if($i == $meta['current_page'])
                                    <li class="page-item active">
                                        <span class="page-link">{{ $i }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="?page={{ $i }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor

                            @if($meta['current_page'] < $meta['last_page'])
                                <li class="page-item">
                                    <a class="page-link" href="?page={{ $meta['current_page'] + 1 }}">Suivant</a>
                                </li>
                            @endif
                        </ul>
                    </nav>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
