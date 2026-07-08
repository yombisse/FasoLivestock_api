@extends('admin.layouts.app')

@section('title', 'Rappels en retard')
@section('page-title', 'Rappels en retard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/sante-rappels/en-retard.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.sante-rappels.index') }}">Rappels sanitaires</a>
    </li>
    <li class="breadcrumb-item active">En retard</li>
@endsection

@section('content')
<div class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Rappels en retard</h2>
            <p>Rappels sanitaires non effectués après la date prévue</p>
        </div>
        <a href="{{ route('admin.sante-rappels.index') }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i>
            Retour
        </a>
    </div>

    {{-- ── Tableau des rappels en retard ─────────────────── --}}
    <div class="rappels-card">
        @if($rappels && count($rappels) > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Animal</th>
                            <th>Type</th>
                            <th>Date prévue</th>
                            <th>Jours de retard</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rappels as $rappel)
                        @php
                            $type = $rappel['type'] ?? '—';
                            $badgeClass = match($type) {
                                'VACCINATION' => 'bg-success',
                                'TRAITEMENT' => 'bg-primary',
                                'CONTROLE' => 'bg-info text-dark',
                                default => 'bg-secondary'
                            };
                            $daysLate = !empty($rappel['date_prevue']) 
                                ? \Carbon\Carbon::parse($rappel['date_prevue'])->diffInDays(now()) 
                                : 0;
                        @endphp
                        <tr class="table-danger">
                            <td>
                                @if(isset($rappel['animal']))
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar">
                                            {{ substr($rappel['animal']['nom'] ?? 'A', 0, 1) }}
                                        </div>
                                        <div class="user-info">
                                            <div class="user-name">{{ $rappel['animal']['nom'] ?? '—' }}</div>
                                            <small class="text-muted">{{ $rappel['animal']['code'] ?? '' }}</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $badgeClass }}">{{ $type }}</span>
                            </td>
                            <td>
                                @if(!empty($rappel['date_prevue']))
                                    {{ \Carbon\Carbon::parse($rappel['date_prevue'])->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-danger">
                                    {{ $daysLate }} j
                                </span>
                            </td>
                            <td>
                                <small>{{ $rappel['description'] ?? '—' }}</small>
                            </td>
                            <td>
                                <div class="actions-cell justify-content-end">
                                    <a href="{{ route('admin.sante-rappels.show', $rappel['id']) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form id="form-realise-{{ $rappel['id'] }}"
                                          method="POST"
                                          action="{{ route('admin.sante-rappels.marquer-realise', $rappel['id']) }}"
                                          style="display: none;">
                                        @csrf
                                    </form>
                                    <button type="button"
                                            onclick="document.getElementById('form-realise-{{ $rappel['id'] }}').submit()"
                                            class="btn btn-sm btn-outline-success"
                                            title="Marquer réalisé">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <p>Aucun rappel en retard.</p>
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/sante-rappels/en-retard.js') }}"></script>
@endpush
