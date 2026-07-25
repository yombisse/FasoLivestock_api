@extends('admin.layouts.ferme')

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
        const farmId = '{{ $farmId }}';

        function exportWithFarm(type, format = 'excel') {
            const routes = {
                'animaux': '{{ route('admin.rapports.export.animaux', ['farm' => $farmId]) }}',
                'transactions': '{{ route('admin.rapports.export.transactions', ['farm' => $farmId]) }}',
                'sante-rappels': '{{ route('admin.rapports.export.sante-rappels', ['farm' => $farmId]) }}',
                'naissances': '{{ route('admin.rapports.export.naissances', ['farm' => $farmId]) }}',
                'cheptel': '{{ route('admin.rapports.pdf.cheptel', ['farm' => $farmId]) }}',
                'sanitaire': '{{ route('admin.rapports.pdf.sanitaire', ['farm' => $farmId]) }}',
                'financier': '{{ route('admin.rapports.pdf.financier', ['farm' => $farmId]) }}'
            };

            window.location.href = routes[type];
        }
    </script>
@endpush
