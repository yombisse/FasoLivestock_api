@extends('admin.layouts.app')

@section('title', 'Naissances')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Naissances</h1>
                <a href="{{ route('admin.naissances.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nouvelle Naissance
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Liste des naissances</h5>
                </div>
                <div class="card-body">
                    @if($naissances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mère</th>
                                        <th>Date Naissance</th>
                                        <th>Nombre Petits</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($naissances as $naissance)
                                    <tr>
                                        <td>{{ $naissance->mother->nom ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($naissance->date_naissance)->format('d/m/Y') }}</td>
                                        <td>{{ $naissance->nombre_petits ?? 0 }}</td>
                                        <td>
                                            @if($naissance->date_naissance)
                                                <span class="badge bg-success">Née</span>
                                            @elseif($naissance->date_mise_bas_prevue)
                                                <span class="badge bg-warning">Prévue</span>
                                            @else
                                                <span class="badge bg-info">En cours</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.naissances.show', $naissance->id) }}" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.naissances.edit', $naissance->id) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            Aucune naissance enregistrée.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
