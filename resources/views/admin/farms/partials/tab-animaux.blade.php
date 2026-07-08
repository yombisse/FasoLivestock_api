<div class="farm-animals">
    
    {{-- En-tête avec bouton ajouter --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Animaux de cette ferme</h5>
        <a href="{{ route('admin.animals.create') }}?farm_id={{ $farm['id'] }}"
           class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Ajouter un animal
        </a>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.farms.manage', $farm['id']) }}" class="mb-4">
        <input type="hidden" name="tab" value="animaux">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text"
                       name="search"
                       class="form-control form-control-sm"
                       placeholder="Nom, numéro, race..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Espèce</label>
                <select name="espece_id" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    <option value="1" {{ request('espece_id') === '1' ? 'selected' : '' }}>Bovin</option>
                    <option value="2" {{ request('espece_id') === '2' ? 'selected' : '' }}>Ovin</option>
                    <option value="3" {{ request('espece_id') === '3' ? 'selected' : '' }}>Caprin</option>
                    <option value="4" {{ request('espece_id') === '4' ? 'selected' : '' }}>Porcin</option>
                    <option value="5" {{ request('espece_id') === '5' ? 'selected' : '' }}>Volaille</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sexe</label>
                <select name="sexe" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="male" {{ request('sexe') === 'male' ? 'selected' : '' }}>Mâle</option>
                    <option value="femelle" {{ request('sexe') === 'femelle' ? 'selected' : '' }}>Femelle</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="statut" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="ACTIF" {{ request('statut') === 'ACTIF' ? 'selected' : '' }}>Actif</option>
                    <option value="VENDU" {{ request('statut') === 'VENDU' ? 'selected' : '' }}>Vendu</option>
                    <option value="DÉCÉDÉ" {{ request('statut') === 'DÉCÉDÉ' ? 'selected' : '' }}>Décédé</option>
                    <option value="TRANSFÉRÉ" {{ request('statut') === 'TRANSFÉRÉ' ? 'selected' : '' }}>Transféré</option>
                    <option value="ABATTU" {{ request('statut') === 'ABATTU' ? 'selected' : '' }}>Abattu</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                    @if(request()->hasAny(['search', 'espece_id', 'sexe', 'statut']))
                        <a href="{{ route('admin.farms.manage', ['id' => $farm['id'], 'tab' => 'animaux']) }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-lg"></i> Réinitialiser
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Tableau animaux --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Animal</th>
                            <th>Espèce</th>
                            <th>Sexe</th>
                            <th>Statut</th>
                            <th>Date naissance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($animals as $animal)
                        <tr>
                            {{-- Photo + Numéro + Nom --}}
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="animal-avatar">
                                        @if(!empty($animal['photo']))
                                            <img src="{{ $animal['photo'] }}" alt="" class="rounded-circle">
                                        @else
                                            {{ strtoupper(substr($animal['espece']['nom'] ?? 'A', 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="animal-info">
                                        <div class="animal-number">{{ $animal['numero_identification'] ?? '—' }}</div>
                                        <div class="animal-name">{{ $animal['nom'] ?? 'Sans nom' }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Espèce --}}
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $animal['espece']['nom'] ?? '—' }}
                                </span>
                            </td>

                            {{-- Sexe --}}
                            <td>
                                <span class="badge {{ ($animal['sexe'] ?? '') === 'male' ? 'bg-primary' : 'bg-info' }}">
                                    {{ ($animal['sexe'] ?? '') === 'male' ? 'Mâle' : 'Femelle' }}
                                </span>
                            </td>

                            {{-- Statut --}}
                            <td>
                                <span class="badge status-badge {{ strtolower($animal['statut'] ?? 'actif') }}">
                                    {{ $animal['statut'] ?? 'ACTIF' }}
                                </span>
                            </td>

                            {{-- Date naissance --}}
                            <td style="font-size:.8rem;color:var(--text-muted)">
                                @if(!empty($animal['date_naissance']))
                                    {{ \Carbon\Carbon::parse($animal['date_naissance'])->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="text-end">
                                <div class="actions-cell justify-content-end">
                                    <a href="{{ route('admin.animals.show', $animal['id']) }}"
                                       class="btn btn-icon btn-outline-secondary btn-sm"
                                       title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.animals.edit', $animal['id']) }}"
                                       class="btn btn-icon btn-outline-primary btn-sm"
                                       title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form id="form-archive-{{ $animal['id'] }}"
                                          method="POST"
                                          action="{{ route('admin.animals.destroy', $animal['id']) }}"
                                          style="display:none">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button type="button"
                                            class="btn btn-icon btn-outline-danger btn-sm"
                                            title="Archiver"
                                            @click="document.getElementById('form-archive-{{ $animal['id'] }}').submit()">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="bi bi-box2-heart"></i>
                                    </div>
                                    <p>Aucun animal trouvé</p>
                                    @if(request()->hasAny(['search', 'espece_id', 'sexe', 'statut']))
                                        <a href="{{ route('admin.farms.manage', ['id' => $farm['id'], 'tab' => 'animaux']) }}"
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
            @if(isset($animalsMeta['last_page']) && $animalsMeta['last_page'] > 1)
            <div class="pagination-bar">
                <span>
                    Page {{ $animalsMeta['current_page'] }} sur {{ $animalsMeta['last_page'] }}
                    — {{ $animalsMeta['total'] }} résultat(s)
                </span>
                <nav>
                    <ul class="pagination">
                        <li class="page-item {{ $animalsMeta['current_page'] <= 1 ? 'disabled' : '' }}">
                            <a class="page-link"
                               href="{{ request()->fullUrlWithQuery(['page' => $animalsMeta['current_page'] - 1, 'tab' => 'animaux']) }}">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        @for($i = 1; $i <= $animalsMeta['last_page']; $i++)
                            @if($i == 1 || $i == $animalsMeta['last_page'] || abs($i - $animalsMeta['current_page']) <= 1)
                            <li class="page-item {{ $i == $animalsMeta['current_page'] ? 'active' : '' }}">
                                <a class="page-link"
                                   href="{{ request()->fullUrlWithQuery(['page' => $i, 'tab' => 'animaux']) }}">
                                    {{ $i }}
                                </a>
                            </li>
                            @elseif(abs($i - $animalsMeta['current_page']) == 2)
                            <li class="page-item disabled">
                                <span class="page-link">…</span>
                            </li>
                            @endif
                        @endfor

                        <li class="page-item {{ $animalsMeta['current_page'] >= $animalsMeta['last_page'] ? 'disabled' : '' }}">
                            <a class="page-link"
                               href="{{ request()->fullUrlWithQuery(['page' => $animalsMeta['current_page'] + 1, 'tab' => 'animaux']) }}">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            @endif

        </div>
    </div>

</div>
