@extends('admin.layouts.app')

@section('title', 'Détails Naissance')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1>Détails Naissance</h1>
            <a href="{{ route('admin.naissances.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations sur la naissance</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Mère:</dt>
                                <dd class="col-sm-8">{{ $naissance->mother->nom ?? 'N/A' }}</dd>

                                <dt class="col-sm-4">Date Naissance:</dt>
                                <dd class="col-sm-8">{{ \Carbon\Carbon::parse($naissance->date_naissance)->format('d/m/Y') }}</dd>

                                <dt class="col-sm-4">Nombre Petits:</dt>
                                <dd class="col-sm-8">{{ $naissance->nombre_petits ?? 0 }}</dd>

                                <dt class="col-sm-4">Date Saillie:</dt>
                                <dd class="col-sm-8">{{ $naissance->date_saillie ? \Carbon\Carbon::parse($naissance->date_saillie)->format('d/m/Y') : 'N/A' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-sm-4">Date Mise Bas Prévue:</dt>
                                <dd class="col-sm-8">{{ $naissance->date_mise_bas_prevue ? \Carbon\Carbon::parse($naissance->date_mise_bas_prevue)->format('d/m/Y') : 'N/A' }}</dd>

                                <dt class="col-sm-4">Statut:</dt>
                                <dd class="col-sm-8">
                                    @if($naissance->date_naissance)
                                        <span class="badge bg-success">Née</span>
                                    @elseif($naissance->date_mise_bas_prevue)
                                        <span class="badge bg-warning">Prévue</span>
                                    @else
                                        <span class="badge bg-info">En cours</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('admin.naissances.edit', $naissance->id) }}" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Modifier
                        </a>
                        <form method="POST" action="{{ route('admin.naissances.destroy', $naissance->id) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette naissance ?')">
                                <i class="bi bi-trash"></i> Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
