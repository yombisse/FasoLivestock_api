@extends('admin.layouts.app')

@section('title', 'Espèces')
@section('page-title', 'Espèces')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/especes/index.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Espèces</li>
@endsection

@section('content')
<div x-data="especesIndex()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-tree me-2 text-success"></i>Espèces</h2>
            <p>Gérez les espèces animales</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.especes.trashed') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-archive"></i>
                Archivés
            </a>
            <a href="{{ route('admin.especes.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i>
                Nouvelle espèce
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.especes.index') }}">
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
            <a href="{{ route('admin.especes.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} espèce(s)
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
                        <th>Paramètres</th>
                        <th>Créé le</th>
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
                            @if($espece['parametre'])
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Configurés
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <i class="bi bi-exclamation-circle"></i> Non configurés
                                </span>
                            @endif
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($espece['created_at'])->format('d/m/Y') }}
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="{{ route('admin.especes.parametres', $espece['id']) }}"
                                   class="btn btn-sm btn-outline-info"
                                   title="Paramétrer">
                                    <i class="bi bi-sliders"></i>
                                </a>
                                <a href="{{ route('admin.especes.show', $espece['id']) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.especes.edit', $espece['id']) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.especes.destroy', $espece['id']) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Archiver"
                                            onclick="return confirm('Êtes-vous sûr de vouloir archiver cette espèce ?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">Aucune espèce trouvée</p>
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
    <script src="{{ asset('admin/js/especes/index.js') }}"></script>
@endpush
