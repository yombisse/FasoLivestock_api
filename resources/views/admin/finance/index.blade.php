@extends('admin.layouts.app')

@section('title', 'Gestion des transactions')
@section('page-title', 'Gestion des transactions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/finance/index.css') }}">
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Finance</li>
@endsection

@section('content')
<div x-data="financeIndex()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-currency-dollar me-2 text-success"></i>Gestion des transactions</h2>
            <p>Suivez vos revenus et dépenses</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.finance.bilan') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-graph-up"></i>
                Bilan
            </a>
            <a href="{{ route('admin.finance.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i>
                Nouvelle transaction
            </a>
        </div>
    </div>

    {{-- ── Bilan rapide ───────────────────────────────────── --}}
    @if(isset($bilan))
    <div class="bilan-card mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="bilan-item bilan-revenus">
                    <div class="bilan-label">Total Entrées</div>
                    <div class="bilan-value">{{ number_format($bilan['total_entrees'] ?? 0, 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bilan-item bilan-charges">
                    <div class="bilan-label">Total Sorties</div>
                    <div class="bilan-value">{{ number_format($bilan['total_sorties'] ?? 0, 0, ',', ' ') }} FCFA</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bilan-item bilan-solde">
                    <div class="bilan-label">Solde</div>
                    <div class="bilan-value {{ ($bilan['solde'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($bilan['solde'] ?? 0, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.finance.index') }}">
        <div class="filters-bar">

            {{-- Recherche --}}
            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Rechercher..."
                       value="{{ request('search') }}"
                       x-ref="searchInput">
            </div>

            {{-- Type transaction --}}
            <select name="type_transaction" class="form-select form-select-sm">
                <option value="">Tous les types</option>
                <option value="ENTREE" {{ request('type_transaction') === 'ENTREE' ? 'selected' : '' }}>Entrées</option>
                <option value="SORTIE" {{ request('type_transaction') === 'SORTIE' ? 'selected' : '' }}>Sorties</option>
            </select>

            {{-- Période --}}
            <input type="date"
                   name="date_debut"
                   class="form-control form-control-sm"
                   value="{{ request('date_debut') }}"
                   placeholder="Date début">

            <input type="date"
                   name="date_fin"
                   class="form-control form-control-sm"
                   value="{{ request('date_fin') }}"
                   placeholder="Date fin">

            {{-- Bouton recherche --}}
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i>
                Filtrer
            </button>

            {{-- Reset --}}
            @if(request()->hasAny(['search', 'type_transaction', 'date_debut', 'date_fin']))
            <a href="{{ route('admin.finance.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">
                {{ $meta['total'] ?? 0 }} transaction(s)
            </span>

        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────── --}}
    <div class="users-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Catégorie</th>
                        <th>Animal</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                    <tr>
                        {{-- Date --}}
                        <td style="font-size:.85rem">
                            {{ \Carbon\Carbon::parse($transaction['date_transaction'])->format('d/m/Y') }}
                        </td>

                        {{-- Type --}}
                        <td>
                            @if($transaction['type_transaction'] === 'ENTREE')
                                <span class="badge bg-success">Entrée</span>
                            @else
                                <span class="badge bg-danger">Sortie</span>
                            @endif
                        </td>

                        {{-- Montant --}}
                        <td style="font-weight:600">
                            {{ number_format($transaction['montant'] ?? 0, 0, ',', ' ') }} FCFA
                        </td>

                        {{-- Catégorie --}}
                        <td>{{ $transaction['categorie']['nom_categorie'] ?? '—' }}</td>

                        {{-- Animal --}}
                        <td>{{ $transaction['animal']['numero_identification'] ?? '—' }}</td>

                        {{-- Description --}}
                        <td>{{ \Illuminate\Support\Str::limit($transaction['description'] ?? '—', 30) }}</td>

                        {{-- Actions --}}
                        <td>
                            <div class="actions-cell justify-content-end">
                                {{-- Voir --}}
                                <a href="{{ route('admin.finance.show', $transaction['id']) }}"
                                   class="btn btn-icon btn-outline-primary"
                                   title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>

                                {{-- Modifier --}}
                                <a href="{{ route('admin.finance.edit', $transaction['id']) }}"
                                   class="btn btn-icon btn-outline-warning"
                                   title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Archiver --}}
                                <button type="button"
                                        class="btn btn-icon btn-outline-danger"
                                        title="Archiver"
                                        @click="deleteTransaction(
                                            @js($transaction['id']),
                                            @js($transaction['description'] ?? 'Transaction')
                                        )">
                                    <i class="bi bi-archive"></i>
                                </button>

                                {{-- Formulaire caché --}}
                                <form id="form-delete-{{ $transaction['id'] }}"
                                      method="POST"
                                      action="{{ route('admin.finance.destroy', $transaction['id']) }}"
                                      style="display:none">
                                    @csrf @method('DELETE')
                                </form>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <p>Aucune transaction trouvée</p>
                                @if(request()->hasAny(['search', 'type_transaction', 'date_debut', 'date_fin']))
                                    <a href="{{ route('admin.finance.index') }}"
                                       class="btn btn-outline-primary btn-sm mt-2">
                                        Réinitialiser les filtres
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── Pagination ───────────────────────────────── --}}
        @if(isset($meta['last_page']) && $meta['last_page'] > 1)
        <div class="pagination-bar">
            <span>
                Page {{ $meta['current_page'] }} sur {{ $meta['last_page'] }}
                — {{ $meta['total'] }} résultat(s)
            </span>
            <nav>
                <ul class="pagination">
                    {{-- Précédent --}}
                    <li class="page-item {{ $meta['current_page'] <= 1 ? 'disabled' : '' }}">
                        <a class="page-link"
                           href="{{ request()->fullUrlWithQuery(['page' => $meta['current_page'] - 1]) }}">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    {{-- Pages --}}
                    @for($i = 1; $i <= $meta['last_page']; $i++)
                        @if($i == 1 || $i == $meta['last_page'] || abs($i - $meta['current_page']) <= 1)
                        <li class="page-item {{ $i == $meta['current_page'] ? 'active' : '' }}">
                            <a class="page-link"
                               href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">
                                {{ $i }}
                            </a>
                        </li>
                        @elseif(abs($i - $meta['current_page']) == 2)
                        <li class="page-item disabled">
                            <span class="page-link">…</span>
                        </li>
                        @endif
                    @endfor

                    {{-- Suivant --}}
                    <li class="page-item {{ $meta['current_page'] >= $meta['last_page'] ? 'disabled' : '' }}">
                        <a class="page-link"
                           href="{{ request()->fullUrlWithQuery(['page' => $meta['current_page'] + 1]) }}">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif

    </div>

   {{-- ══════════════════════════════════════════════════════
     MODAL CONFIRMATION (Alpine-only)
═══════════════════════════════════════════════════════ --}}

    {{-- Backdrop --}}
    <div x-cloak
        x-show="confirmModal.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="confirmModal.show = false"
        style="position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)">
    </div>

    {{-- Modal --}}
    <div x-cloak
        x-show="confirmModal.show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="position:fixed;inset:0;z-index:1055;overflow-y:auto;pointer-events:none">

        <div style="min-height:100%;display:flex;align-items:center;justify-content:center;padding:1rem;pointer-events:none">

            <div style="width:100%;max-width:420px;pointer-events:auto">
                <div class="confirm-modal-box">

                    {{-- Icône centrale --}}
                    <div class="confirm-modal-icon icon-danger">
                        <i class="bi fs-4 bi-trash3"></i>
                    </div>

                    {{-- Titre --}}
                    <h6 class="confirm-modal-title" x-text="confirmModal.title"></h6>

                    {{-- Message --}}
                    <p class="confirm-modal-message" x-html="confirmModal.message"></p>

                    {{-- Actions --}}
                    <div class="confirm-modal-actions">
                        <button type="button"
                                class="btn-confirm-cancel"
                                @click="confirmModal.show = false">
                            Annuler
                        </button>
                        <button type="button"
                                class="btn-confirm-ok ok-danger"
                                @click="confirmAction()">
                            Confirmer
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/finance/index.js') }}"></script>
@endpush
