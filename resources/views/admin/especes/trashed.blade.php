@extends('admin.layouts.app')

@section('title', 'Espèces archivées')
@section('page-title', 'Espèces archivées')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/especes/trashed.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.especes.index') }}">Espèces</a>
    </li>
    <li class="breadcrumb-item active">Archivées</li>
@endsection

@section('content')
<div x-data="especesTrashed()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-archive me-2 text-warning"></i>Espèces archivées</h2>
            <p>Espèces supprimées (soft delete)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.especes.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.especes.trashed') }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Rechercher par nom..."
                       value="{{ request('search') }}"
                       x-ref="searchInput">
            </div>

            {{-- Bouton recherche --}}
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i>
                Rechercher
            </button>

            {{-- Reset --}}
            @if(request()->has('search'))
            <a href="{{ route('admin.especes.trashed') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} espèce(s) archivée(s)
            </span>

        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────── --}}
    <div class="users-card">
        <div class="table-responsive">
            <table class="table table-hover users-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Archivé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($especes as $espece)
                    <tr>
                        <td>
                            <strong>{{ $espece['nom'] }}</strong>
                        </td>
                        <td>
                            {{ Str::limit($espece['description'] ?? '-', 50) }}
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($espece['deleted_at'])->format('d/m/Y H:i') }}
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <form action="{{ route('admin.especes.restore', $espece['id']) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-success"
                                            title="Restaurer"
                                            onclick="return confirm('Êtes-vous sûr de vouloir restaurer cette espèce ?')">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <i class="bi bi-archive fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">Aucune espèce archivée</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ────────────────────────────────────── --}}
        @if(isset($meta['last_page']) && $meta['last_page'] > 1)
        <div class="pagination-wrapper">
            {{ $especes->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/especes/trashed.js') }}"></script>
@endpush
