@extends('admin.layouts.app')

@section('title', 'Rapports')
@section('page-title', 'Rapports & Exportations')

@section('breadcrumb')
    <li class="breadcrumb-item active">Rapports</li>
@endsection

@section('content')
<div class="rapports-page fade-in">
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-file-earmark-text me-2 text-primary"></i>Rapports & Exportations</h2>
            <p>Exportez vos données et générez des rapports PDF</p>
        </div>
    </div>

    {{-- Sélection de ferme --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Sélectionner une ferme:</label>
                </div>
                <div class="col-md-5">
                    <select id="farm-selector" class="form-select">
                        <option value="">-- Choisir une ferme --</option>
                        @foreach($farms ?? [] as $farm)
                            <option value="{{ is_array($farm) ? $farm['id'] : $farm->id }}">
                                {{ is_array($farm) ? $farm['name'] : $farm->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Sélectionnez une ferme avant d'exporter les données</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Export Excel --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-file-earmark-excel me-2 text-success"></i>Exports Excel</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button onclick="exportWithFarm('animaux')" class="btn btn-outline-success">
                            <i class="bi bi-download me-2"></i>Export Animaux
                        </button>
                        <button onclick="exportWithFarm('transactions')" class="btn btn-outline-success">
                            <i class="bi bi-download me-2"></i>Export Transactions
                        </button>
                        <button onclick="exportWithFarm('sante-rappels')" class="btn btn-outline-success">
                            <i class="bi bi-download me-2"></i>Export Rappels Sanitaires
                        </button>
                        <button onclick="exportWithFarm('naissances')" class="btn btn-outline-success">
                            <i class="bi bi-download me-2"></i>Export Naissances
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rapports PDF --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Rapports PDF</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button onclick="exportWithFarm('cheptel', 'pdf')" class="btn btn-outline-danger">
                            <i class="bi bi-file-pdf me-2"></i>Rapport Cheptel
                        </button>
                        <button onclick="exportWithFarm('sanitaire', 'pdf')" class="btn btn-outline-danger">
                            <i class="bi bi-file-pdf me-2"></i>Rapport Sanitaire
                        </button>
                        <button onclick="exportWithFarm('financier', 'pdf')" class="btn btn-outline-danger">
                            <i class="bi bi-file-pdf me-2"></i>Rapport Financier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger mt-3">
            {{ session('error') }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success mt-3">
            {{ session('success') }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        const exportRoutes = {
            'animaux': '{{ route('admin.rapports.export.animaux') }}',
            'transactions': '{{ route('admin.rapports.export.transactions') }}',
            'sante-rappels': '{{ route('admin.rapports.export.sante-rappels') }}',
            'naissances': '{{ route('admin.rapports.export.naissances') }}',
            'cheptel': '{{ route('admin.rapports.pdf.cheptel') }}',
            'sanitaire': '{{ route('admin.rapports.pdf.sanitaire') }}',
            'financier': '{{ route('admin.rapports.pdf.financier') }}'
        };

        function exportWithFarm(type, format = 'excel') {
            const farmId = document.getElementById('farm-selector').value;
            
            if (!farmId) {
                alert('Veuillez sélectionner une ferme avant d\'exporter les données.');
                return;
            }

            const url = exportRoutes[type];
            const separator = url.includes('?') ? '&' : '?';
            window.location.href = url + separator + 'farm_id=' + farmId;
        }
    </script>
@endpush
