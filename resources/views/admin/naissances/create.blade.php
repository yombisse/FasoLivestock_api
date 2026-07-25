@extends('admin.layouts.ferme')

@section('title', 'Nouvelle Naissance')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1>Nouvelle Naissance</h1>
            <a href="{{ route('admin.naissances.index', ['farm' => $farmId]) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Enregistrer une naissance</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.naissances.store', ['farm' => $farmId]) }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="mother_id" class="form-label">Mère</label>
                            <select class="form-select" id="mother_id" name="mother_id" required>
                                <option value="">Sélectionner une mère</option>
                                @foreach($femellesEligibles as $femelle)
                                    <option value="{{ $femelle->id }}">{{ $femelle->nom }} ({{ $femelle->numero_identification }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="date_naissance" class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_petits" class="form-label">Nombre de petits</label>
                            <input type="number" class="form-control" id="nombre_petits" name="nombre_petits" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="date_mise_bas_prevue" class="form-label">Date mise bas prévue (optionnel)</label>
                            <input type="date" class="form-control" id="date_mise_bas_prevue" name="date_mise_bas_prevue">
                        </div>

                        <div class="mb-3">
                            <label for="date_saillie" class="form-label">Date saillie (optionnel)</label>
                            <input type="date" class="form-control" id="date_saillie" name="date_saillie">
                        </div>

                        @if($warningMessage)
                            <div class="alert alert-warning">
                                {{ $warningMessage }}
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
