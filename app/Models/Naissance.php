<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Naissance extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'naissances';

    protected $fillable = [
        'farm_id',
        'mother_id',
        'date_naissance',
        'nombre_petits',
        'poids_naissance',
        'observation',
        'evenement_id',
        // ─── Nouveaux ────────────────
        'date_saillie',
        'date_mise_bas_prevue',
        // ─────────────────────────────
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'date_naissance'      => 'date',
        'date_saillie'        => 'date',
        'date_mise_bas_prevue' => 'date',
        'poids_naissance'     => 'decimal:2',
        'nombre_petits'       => 'integer',
        'sync_status'         => 'string',
    ];

    // =========================================================
    // RELATIONS EXISTANTES
    // =========================================================

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function mother()
    {
        return $this->belongsTo(Animal::class, 'mother_id');
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }

    public function petits()
    {
        return $this->hasMany(Animal::class, 'naissance_id');
    }

    public function modificateur()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopePeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_naissance', [$debut, $fin]);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Mises bas prévues dans les X prochains jours
     */
    public function scopeMisesBasAVenir($query, int $jours = 7)
    {
        return $query->whereNotNull('date_mise_bas_prevue')
                     ->whereBetween('date_mise_bas_prevue', [
                         now(),
                         now()->addDays($jours)
                     ]);
    }

    // =========================================================
    // ACCESSEURS
    // =========================================================

    public function getNombreEnregistresAttribute(): int
    {
        return Animal::where('mother_id', $this->mother_id)
                     ->whereDate('date_naissance', $this->date_naissance)
                     ->count();
    }

    public function getNombreNonEnregistresAttribute(): int
    {
        return max(0, $this->nombre_petits - $this->nombre_enregistres);
    }

    /**
     * Calculer automatiquement la date de mise bas prévue
     * à partir de la date de saillie et des paramètres de l'espèce
     */
    public function getDateMiseBasPrevueCalculeeAttribute(): ?\Carbon\Carbon
    {
        if (!$this->date_saillie) return null;

        $parametres = $this->mother
            ?->espece
            ?->parametres;

        if (!$parametres?->duree_gestation_jours) return null;

        return $this->date_saillie->addDays($parametres->duree_gestation_jours);
    }
}