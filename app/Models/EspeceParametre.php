<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EspeceParametre extends Model
{
    protected $table = 'espece_parametres';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'espece_id',
        'duree_gestation_jours',
        'age_reproduction_mois',
        'nombre_petits_typique',
        'intervalle_vaccin_jours',
        'age_sevrage_jours',
        'poids_naissance_moyen_kg',
        'poids_adulte_moyen_kg',
    ];

    protected $casts = [
        'duree_gestation_jours'    => 'integer',
        'age_reproduction_mois'    => 'integer',
        'nombre_petits_typique'    => 'integer',
        'intervalle_vaccin_jours'  => 'integer',
        'age_sevrage_jours'        => 'integer',
        'poids_naissance_moyen_kg' => 'decimal:2',
        'poids_adulte_moyen_kg'    => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($parametre) {
            if (empty($parametre->id)) {
                $parametre->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
        });
    }

    // =========================================================
    // RELATIONS
    // =========================================================

    public function espece()
    {
        return $this->belongsTo(Espece::class);
    }

    // =========================================================
    // ACCESSEURS
    // =========================================================

    /**
     * Calculer la date de mise bas prévue à partir d'une date de saillie
     */
    public function calculerDateMiseBas(string $dateSaillie): ?\Carbon\Carbon
    {
        if (!$this->duree_gestation_jours) return null;

        return \Carbon\Carbon::parse($dateSaillie)
            ->addDays($this->duree_gestation_jours);
    }

    /**
     * Calculer la date du prochain vaccin à partir du dernier
     */
    public function calculerProchainVaccin(string $dernierVaccin): ?\Carbon\Carbon
    {
        if (!$this->intervalle_vaccin_jours) return null;

        return \Carbon\Carbon::parse($dernierVaccin)
            ->addDays($this->intervalle_vaccin_jours);
    }

    /**
     * Vérifier si un animal est en âge de reproduire
     */
    public function estEnAgeDeReproduire(\Carbon\Carbon $dateNaissance): bool
    {
        if (!$this->age_reproduction_mois) return false;

        return $dateNaissance->diffInMonths(now()) >= $this->age_reproduction_mois;
    }
}