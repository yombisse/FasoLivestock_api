@if(auth()->user()->hasRole('superadmin') || auth()->user()->can('farms.update'))
<div class="row g-4">
    
    {{-- Colonne gauche: Formulaire édition --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Modifier les informations
            </div>
            <div class="card-body">
                <form method="POST"
                      action="{{ route('admin.farms.update', $farm['id']) }}"
                      class="farm-info-form">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nom de la ferme *</label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="{{ $farm['name'] ?? '' }}"
                                   required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Localisation *</label>
                            <input type="text"
                                   name="location"
                                   class="form-control"
                                   value="{{ $farm['location'] ?? '' }}"
                                   required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="3">{{ $farm['description'] ?? '' }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Type d'élevage</label>
                            <select name="type_elevage" class="form-select">
                                <option value="Bovin" {{ ($farm['type_elevage'] ?? '') === 'Bovin' ? 'selected' : '' }}>Bovin</option>
                                <option value="Ovin" {{ ($farm['type_elevage'] ?? '') === 'Ovin' ? 'selected' : '' }}>Ovin</option>
                                <option value="Caprin" {{ ($farm['type_elevage'] ?? '') === 'Caprin' ? 'selected' : '' }}>Caprin</option>
                                <option value="Porcin" {{ ($farm['type_elevage'] ?? '') === 'Porcin' ? 'selected' : '' }}>Porcin</option>
                                <option value="Volaille" {{ ($farm['type_elevage'] ?? '') === 'Volaille' ? 'selected' : '' }}>Volaille</option>
                                <option value="Mixte" {{ ($farm['type_elevage'] ?? '') === 'Mixte' ? 'selected' : '' }}>Mixte</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Photo (URL)</label>
                            <input type="url"
                                   name="photo"
                                   class="form-control"
                                   value="{{ $farm['photo'] ?? '' }}"
                                   placeholder="https://...">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Colonne droite: Actions --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-speedometer2"></i> Statistiques
            </div>
            <div class="card-body">
                <div class="farm-stats-list">
                    <div class="farm-stat-item">
                        <div class="farm-stat-icon animals">
                            <i class="bi bi-box2-heart"></i>
                        </div>
                        <div class="farm-stat-content">
                            <div class="farm-stat-value">{{ $farm['animals_count'] ?? 0 }}</div>
                            <div class="farm-stat-label">Animaux</div>
                        </div>
                    </div>
                    <div class="farm-stat-item">
                        <div class="farm-stat-icon members">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="farm-stat-content">
                            <div class="farm-stat-value">{{ count($farm['users'] ?? []) }}</div>
                            <div class="farm-stat-label">Membres</div>
                        </div>
                    </div>
                    <div class="farm-stat-item">
                        <div class="farm-stat-icon lots">
                            <i class="bi bi-grid-3x3"></i>
                        </div>
                        <div class="farm-stat-content">
                            <div class="farm-stat-value">{{ $farm['lots_count'] ?? 0 }}</div>
                            <div class="farm-stat-label">Lots</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-gear"></i> Actions
            </div>
            <div class="card-body">
                <button type="button"
                        class="btn btn-danger w-100"
                        @click="confirmArchiveFarm(
                            @js($farm['id']),
                            @js($farm['name'])
                        )">
                    <i class="bi bi-archive"></i> Archiver la ferme
                </button>
            </div>
        </div>
    </div>

</div>

{{-- Formulaire caché archivage --}}
<form id="form-archive-{{ $farm['id'] }}"
      method="POST"
      action="{{ route('admin.farms.destroy', $farm['id']) }}"
      style="display:none">
    @csrf @method('DELETE')
</form>
@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-shield-lock fs-1 text-muted"></i>
        <p class="text-muted mt-3">Vous n'avez pas la permission de modifier cette ferme.</p>
    </div>
</div>
@endif
