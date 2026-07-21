@extends('admin.layouts.app')

@section('title', 'Modifier Naissance')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1>Modifier Naissance</h1>
            <a href="{{ route('admin.naissances.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Modifier les informations de naissance</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.naissances.update', $naissance->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="mother_id" class="form-label">Mère</label>
                            <select class="form-select" id="mother_id" name="mother_id" required>
                                <option value="">Sélectionner une mère</option>
                                @foreach($femellesEligibles as $femelle)
                                    <option value="{{ $femelle->id }}" {{ $femelle->id == $naissance->mother_id ? 'selected' : '' }}>
                                        {{ $femelle->nom }} ({{ $femelle->numero_identification }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="date_naissance" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="{{ $naissance->date_naissance ? \Carbon\Carbon::parse($naissance->date_naissance)->format('Y-m-d') : '' }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_petits" class="form-label">Nombre de petits</label>
                            <input type="number" class="form-control" id="nombre_petits" name="nombre_petits" min="1" value="{{ $naissance->nombre_petits ?? 0 }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="date_mise_bas_prevue" class="form-label">Date mise bas prévue (optionnel)</label>
                            <input type="date" class="form-control" id="date_mise_bas_prevue" name="date_mise_bas_prevue" value="{{ $naissance->date_mise_bas_prevue ? \Carbon\Carbon::parse($naissance->date_mise_bas_prevue)->format('Y-m-d') : '' }}">
                        </div>

                        <div class="mb-3">
                            <label for="date_saillie" class="form-label">Date saillie (optionnel)</label>
                            <input type="date" class="form-control" id="date_saillie" name="date_saillie" value="{{ $naissance->date_saillie ? \Carbon\Carbon::parse($naissance->date_saillie)->format('Y-m-d') : '' }}">
                        </div>

                        @if($warningMessage)
                            <div class="alert alert-warning">
                                {{ $warningMessage }}
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Mettre à jour
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
