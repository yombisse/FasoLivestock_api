@extends('admin.layouts.ferme')

@section('title', 'Animaux archivés')
@section('page-title', 'Animaux archivés')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/animals/index.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('admin.animals.index', ['farm' => request()->route('farm')]) }}">Animaux</a>
    </li>
    <li class="breadcrumb-item active">Archivés</li>
@endsection

@section('content')
<div x-data="animalsTrashed()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-archive me-2 text-warning"></i>Animaux archivés</h2>
            <p>Animaux supprimés (soft delete)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.animals.index', ['farm' => request()->route('farm')]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
                Retour
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.animals.trashed', ['farm' => request()->route('farm')]) }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Rechercher par nom, race..."
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
            <a href="{{ route('admin.animals.trashed', ['farm' => request()->route('farm')]) }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} animal(s) archivé(s)
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
                        <th>Espèce</th>
                        <th>Race</th>
                        <th>Sexe</th>
                        <th>Statut</th>
                        <th>Origine</th>
                        <th>Archivé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($animals as $animal)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if(!empty($animal['photo']))
                                    <img src="{{ asset('storage/' . $animal['photo']) }}"
                                         alt="{{ $animal['nom'] }}"
                                         class="rounded-circle"
                                         style="width:32px;height:32px;object-fit:cover">
                                @else
                                    <div class="user-avatar inactive">
                                        <i class="bi bi-cow"></i>
                                    </div>
                                @endif
                                <div>
                                    <strong>{{ $animal['nom'] ?? '—' }}</strong>
                                    @if(!empty($animal['numero_identification']))
                                        <br><small class="text-muted">{{ $animal['numero_identification'] }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            {{ $animal['espece']['nom'] ?? '—' }}
                        </td>
                        <td>
                            {{ $animal['race'] ?? '—' }}
                        </td>
                        <td>
                            @if($animal['sexe'] === 'M')
                                <span class="badge bg-primary">Mâle</span>
                            @elseif($animal['sexe'] === 'F')
                                <span class="badge bg-danger">Femelle</span>
                            @else
                                <span class="badge bg-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            @switch($animal['statut'])
                                @case('ACTIF')
                                    <span class="badge bg-success">Actif</span>
                                    @break
                                @case('VENDU')
                                    <span class="badge bg-info">Vendu</span>
                                    @break
                                @case('MORT')
                                    <span class="badge bg-dark">Mort</span>
                                    @break
                                @case('PERDU')
                                    <span class="badge bg-warning">Perdu</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ $animal['statut'] }}</span>
                            @endswitch
                        </td>
                        <td>
                            @switch($animal['origine'])
                                @case('import')
                                    <span class="badge bg-light text-dark">Import</span>
                                    @break
                                @case('achat')
                                    <span class="badge bg-primary">Achat</span>
                                    @break
                                @case('naissance')
                                    <span class="badge bg-success">Naissance</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ $animal['origine'] ?? '—' }}</span>
                            @endswitch
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($animal['deleted_at'])->format('d/m/Y H:i') }}
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <form action="{{ route('admin.animals.restore', $animal['id']) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-success"
                                            title="Restaurer"
                                            onclick="return confirm('Êtes-vous sûr de vouloir restaurer cet animal ?')">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <i class="bi bi-archive fs-1 text-muted"></i>
                            <p class="mt-2 text-muted">Aucun animal archivé</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ────────────────────────────────────── --}}
        @if(isset($meta['last_page']) && $meta['last_page'] > 1)
        <div class="pagination-wrapper">
            {{ $animals->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
    <script>
        function animalsTrashed() {
            return {
                init() {
                    // Focus sur le champ de recherche
                    if (this.$refs.searchInput) {
                        this.$refs.searchInput.focus();
                    }
                }
            }
        }
    </script>
@endpush
