@extends('admin.layouts.app')

@section('title', 'Animaux')
@section('page-title', 'Animaux')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/users/index.css') }}">
    <style>
        [x-cloak] { display: none !important; }

        .origin-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: .5rem;
        }

        .origin-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .75rem;
            padding: 1.25rem 1rem;
            border: 1.5px solid var(--bs-border-color);
            border-radius: 12px;
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s, box-shadow .15s, transform .1s;
        }

        .origin-card:hover {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), .12);
            text-decoration: none;
            color: inherit;
            transform: translateY(-2px);
        }

        .origin-card .oc-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .origin-card .oc-label {
            font-weight: 600;
            font-size: .9rem;
            text-align: center;
        }

        .origin-card .oc-desc {
            font-size: .78rem;
            color: #6c757d;
            text-align: center;
            line-height: 1.4;
        }

        .oc-birth  .oc-icon { background: #e8f5e9; color: #2e7d32; }
        .oc-buy    .oc-icon { background: #e3f2fd; color: #1565c0; }
        .oc-reg    .oc-icon { background: #fafafa; color: #555; border: 1px solid #e0e0e0; }

        /* Modal overlay – Alpine-only, pas de Bootstrap JS */
        .origin-backdrop {
            position: fixed; inset: 0; z-index: 1050;
            background: rgba(15,23,42,.45);
            backdrop-filter: blur(2px);
        }
        .origin-modal-wrap {
            position: fixed; inset: 0; z-index: 1055;
            overflow-y: auto; pointer-events: none;
            display: flex; align-items: center; justify-content: center; padding: 1rem;
        }
        .origin-modal-box {
            pointer-events: auto;
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            width: 100%;
            max-width: 540px;
            box-shadow: 0 20px 40px rgba(0,0,0,.12);
        }
        .origin-modal-box h6 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: .25rem;
        }
        .origin-modal-box .sub {
            font-size: .83rem;
            color: #6c757d;
            margin-bottom: 1.25rem;
        }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item active">Animaux</li>
@endsection

@section('content')
<div x-data="animalsIndex()" class="fade-in">

    {{-- ── En-tête page ──────────────────────────────────── --}}
    <div class="page-header">
        <div class="page-header-left">
            <h2><i class="bi bi-cow me-2 text-success"></i>Animaux</h2>
            <p>Gérez le cheptel de vos fermes</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.animals.trashed') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-archive"></i>
                Archivés
            </a>
            <a href="{{ route('admin.animals.import') }}"
               class="btn btn-outline-warning btn-sm">
                <i class="bi bi-upload"></i>
                Import cheptel
            </a>
            <a href="{{ route('admin.animals.create-achat') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-cart-plus"></i>
                Achat
            </a>
            <a href="{{ route('admin.animals.create-naissance') }}"
               class="btn btn-success btn-sm">
                <i class="bi bi-heart"></i>
                Naissance
            </a>
        </div>
    </div>

    {{-- ── Filtres ────────────────────────────────────────── --}}
    <form id="filter-form"
          method="GET"
          action="{{ route('admin.animals.index') }}">
        <div class="filters-bar">

            <div class="filter-search">
                <i class="bi bi-search search-icon"></i>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Rechercher par nom, race, numéro..."
                       value="{{ request('search') }}"
                       x-ref="searchInput">
            </div>

            <select name="statut"
                    class="form-select filter-select"
                    @change="autoSubmit()">
                <option value="">Tous les statuts</option>
                <option value="actif"    {{ request('statut') === 'actif'    ? 'selected' : '' }}>Actif</option>
                <option value="malade"   {{ request('statut') === 'malade'   ? 'selected' : '' }}>Malade</option>
                <option value="mort"     {{ request('statut') === 'mort'     ? 'selected' : '' }}>Mort</option>
                <option value="vendu"    {{ request('statut') === 'vendu'    ? 'selected' : '' }}>Vendu</option>
            </select>

            <select name="etat_sante"
                    class="form-select filter-select"
                    @change="autoSubmit()">
                <option value="">Tous les états de santé</option>
                <option value="SAIN"         {{ request('etat_sante') === 'SAIN'         ? 'selected' : '' }}>Sain</option>
                <option value="MALADE"       {{ request('etat_sante') === 'MALADE'       ? 'selected' : '' }}>Malade</option>
                <option value="QUARANTAINE"  {{ request('etat_sante') === 'QUARANTAINE'  ? 'selected' : '' }}>Quarantaine</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-search"></i>
                Rechercher
            </button>

            @if(request()->hasAny(['search', 'statut', 'etat_sante']))
            <a href="{{ route('admin.animals.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg"></i>
                Réinitialiser
            </a>
            @endif

            <span class="filter-count">{{ $meta['total'] ?? 0 }} animal(s)</span>
        </div>
    </form>

    {{-- ── Table ──────────────────────────────────────────── --}}
    <div class="users-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Animal</th>
                        <th>N° identification</th>
                        <th>Espèce</th>
                        <th>Race</th>
                        <th>Sexe</th>
                        <th>Ferme</th>
                        <th>Origine</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($animals as $animal)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar"><i class="bi bi-cow"></i></div>
                                <div class="user-info">
                                    <div class="user-name">{{ $animal['nom'] ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $animal['numero_identification'] ?? '—' }}</td>
                        <td>{{ $animal['espece']['nom'] ?? '—' }}</td>
                        <td>{{ $animal['race'] ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $animal['sexe'] === 'male' ? 'bg-primary' : 'bg-pink' }}">
                                {{ $animal['sexe'] === 'male' ? 'Mâle' : 'Femelle' }}
                            </span>
                        </td>
                        <td>{{ $animal['farm']['name'] ?? '—' }}</td>

                        {{-- Colonne Origine (basée sur les mouvements ou naissance_id) --}}
                        <td>
                            @if($animal['naissance_id'])
                                <span class="badge" style="background:#e8f5e9;color:#2e7d32;font-weight:500">
                                    <i class="bi bi-stars me-1"></i>Naissance
                                </span>
                            @elseif(isset($animal['origine']) && $animal['origine'] === 'achat')
                                <span class="badge" style="background:#e3f2fd;color:#1565c0;font-weight:500">
                                    <i class="bi bi-cart me-1"></i>Achat
                                </span>
                            @else
                                <span class="badge" style="background:#f5f5f5;color:#555;font-weight:500">
                                    <i class="bi bi-box-arrow-in-down me-1"></i>Import
                                </span>
                            @endif
                        </td>

                        <td>
                            <span class="status-badge {{ $animal['statut'] === 'actif' ? 'active' : 'inactive' }}">
                                <span class="status-dot {{ $animal['statut'] === 'actif' ? 'active' : 'inactive' }}"></span>
                                {{ ucfirst($animal['statut'] ?? 'Inconnu') }}
                            </span>
                        </td>
                        <td style="font-size:.78rem;color:var(--text-muted)">
                            {{ \Carbon\Carbon::parse($animal['created_at'])->format('d/m/Y') }}
                        </td>
                        <td>
                            <div class="actions-cell justify-content-end">
                                <a href="{{ route('admin.animals.show', $animal['id']) }}"
                                   class="btn btn-icon btn-outline-secondary" title="Voir">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.animals.edit', $animal['id']) }}"
                                   class="btn btn-icon btn-outline-primary" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-icon btn-outline-danger"
                                        title="Archiver"
                                        @click="deleteAnimal(@js($animal['id']), @js($animal['nom']))">
                                    <i class="bi bi-archive"></i>
                                </button>
                                <form id="form-delete-{{ $animal['id'] }}"
                                      method="POST"
                                      action="{{ route('admin.animals.destroy', $animal['id']) }}"
                                      style="display:none">
                                    @csrf @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="bi bi-cow"></i></div>
                                <p>Aucun animal trouvé</p>
                                @if(request()->hasAny(['search', 'statut']))
                                    <a href="{{ route('admin.animals.index') }}"
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

        {{-- Pagination --}}
        @if(isset($meta['last_page']) && $meta['last_page'] > 1)
        <div class="pagination-bar">
            <span>Page {{ $meta['current_page'] }} sur {{ $meta['last_page'] }} — {{ $meta['total'] }} résultat(s)</span>
            <nav>
                <ul class="pagination">
                    <li class="page-item {{ $meta['current_page'] <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $meta['current_page'] - 1]) }}">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    @for($i = 1; $i <= $meta['last_page']; $i++)
                        @if($i == 1 || $i == $meta['last_page'] || abs($i - $meta['current_page']) <= 1)
                        <li class="page-item {{ $i == $meta['current_page'] ? 'active' : '' }}">
                            <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $i]) }}">{{ $i }}</a>
                        </li>
                        @elseif(abs($i - $meta['current_page']) == 2)
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                        @endif
                    @endfor
                    <li class="page-item {{ $meta['current_page'] >= $meta['last_page'] ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $meta['current_page'] + 1]) }}">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL CHOIX D'ORIGINE
    ══════════════════════════════════════════════════════ --}}

    <div x-cloak x-show="originModal.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="originModal.show = false"
         class="origin-backdrop"></div>

    <div x-cloak x-show="originModal.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="origin-modal-wrap">
        <div class="origin-modal-box">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <h6>Comment cet animal entre-t-il dans le troupeau ?</h6>
                <button type="button" class="btn-close btn-sm" @click="originModal.show = false"></button>
            </div>
            <p class="sub">Le mode d'entrée détermine les mouvements et la traçabilité enregistrés.</p>

            <div class="origin-cards">

                {{-- Naissance --}}
                <a href="{{ route('admin.animals.create', ['mode' => 'naissance']) }}"
                   class="origin-card oc-birth">
                    <div class="oc-icon"><i class="bi bi-stars"></i></div>
                    <div class="oc-label">Naissance</div>
                    <div class="oc-desc">Né dans le troupeau. Crée un événement naissance et lie la mère.</div>
                </a>

                {{-- Achat --}}
                <a href="{{ route('admin.animals.create', ['mode' => 'achat']) }}"
                   class="origin-card oc-buy">
                    <div class="oc-icon"><i class="bi bi-cart-plus"></i></div>
                    <div class="oc-label">Achat</div>
                    <div class="oc-desc">Acheté auprès d'un fournisseur. Crée une dépense et un événement ACHAT.</div>
                </a>

                {{-- Import cheptel existant --}}
                <a href="{{ route('admin.animals.create', ['mode' => 'import']) }}"
                   class="origin-card oc-reg">
                    <div class="oc-icon"><i class="bi bi-box-arrow-in-down"></i></div>
                    <div class="oc-label">Import</div>
                    <div class="oc-desc">Déjà dans le troupeau. Simple saisie, aucun mouvement créé.</div>
                </a>

            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         MODAL CONFIRMATION ARCHIVAGE
    ══════════════════════════════════════════════════════ --}}

    <div x-cloak x-show="confirmModal.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="confirmModal.show = false"
         style="position:fixed;inset:0;z-index:1050;background:rgba(15,23,42,.45);backdrop-filter:blur(2px)"></div>

    <div x-cloak x-show="confirmModal.show"
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
                    <div class="confirm-modal-icon icon-danger">
                        <i class="bi fs-4 bi-trash3"></i>
                    </div>
                    <h6 class="confirm-modal-title" x-text="confirmModal.title"></h6>
                    <p class="confirm-modal-message" x-html="confirmModal.message"></p>
                    <div class="confirm-modal-actions">
                        <button type="button" class="btn-confirm-cancel" @click="confirmModal.show = false">Annuler</button>
                        <button type="button" class="btn-confirm-ok ok-danger" @click="confirmAction()">Confirmer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/animals/index.js') }}"></script>
@endpush