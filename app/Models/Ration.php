<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Ration extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'rations';

    protected $fillable = [
        'farm_id',
        'aliment_id',
        'animal_id',
        'lot_id',
        'quantite',
        'date_distribution',
        'heure_distribution',
        'observation',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'quantite'           => 'decimal:2',
        'date_distribution'  => 'date',
        'heure_distribution' => 'datetime',
        'sync_status'        => 'string',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function aliment()
    {
        return $this->belongsTo(Aliment::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
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
        return $query->whereBetween('date_distribution', [$debut, $fin]);
    }

    public function scopePourAnimal($query, string $animalId)
    {
        return $query->where('animal_id', $animalId);
    }

    public function scopePourLot($query, string $lotId)
    {
        return $query->where('lot_id', $lotId);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('sync_status', 'pending');
    }

    // =========================================================
    // ACCESSEURS
    // =========================================================

    /**
     * La ration cible un lot ou un animal individuel ?
     */
    public function getEstPourLotAttribute(): bool
    {
        return $this->lot_id !== null;
    }

    /**
     * Coût total de la ration
     */
    public function getCoutTotalAttribute(): float
    {
        return $this->quantite * ($this->aliment->prix_unitaire ?? 0);
    }

    // =========================================================
    // METHODES METIER
    // =========================================================

    /**
     * Enregistrer la ration et déduire automatiquement du stock
     */
    protected static function booted(): void
    {
        static::created(function (Ration $ration) {
            // Déduire la quantité du stock de l'aliment
            $ration->aliment->deduireStock($ration->quantite);
        });

        static::deleted(function (Ration $ration) {
            // Remettre en stock si ration supprimée
            $ration->aliment->approvisionner($ration->quantite);
        });
    }
}