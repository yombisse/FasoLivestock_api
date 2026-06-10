<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Evenement extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'evenements';

    // ─── Types d'événements qui constituent un mouvement ─────────
    const TYPES_MOUVEMENT = ['VENTE', 'ACHAT', 'TRANSFERT', 'DECES', 'PERTE', 'ABATTAGE'];

    protected $fillable = [
        'farm_id',
        'type_evenement_id',
        'animal_id',
        'date_evenement',
        'description',
        'cout',
        // ─── Nouveaux champs mouvement ───
        'farm_destination_id',
        'statut_avant',
        'statut_apres',
        'transaction_id',
        // ────────────────────────────────
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'date_evenement' => 'date',
        'cout'           => 'decimal:2',
        'sync_status'    => 'string',
        'statut_avant'   => 'string',
        'statut_apres'   => 'string',
    ];

    // =========================================================
    // RELATIONS EXISTANTES
    // =========================================================

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function type()
    {
        return $this->belongsTo(TypeEvenement::class, 'type_evenement_id');
    }

    // =========================================================
    // NOUVELLES RELATIONS
    // =========================================================

    /**
     * Ferme de destination (transferts uniquement)
     */
    public function farmDestination()
    {
        return $this->belongsTo(Farm::class, 'farm_destination_id');
    }

    /**
     * Transaction financière associée (vente, achat...)
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Utilisateur qui a effectué la dernière modification
     */
    public function modificateur()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    // =========================================================
    // ACCESSEURS
    // =========================================================

    /**
     * Vérifie si cet événement est un mouvement (vente, transfert, décès...)
     */
    public function getEstMouvementAttribute(): bool
    {
        return in_array(
            strtoupper($this->type?->nom_type ?? ''),
            self::TYPES_MOUVEMENT
        );
    }

    /**
     * Vérifie si c'est un transfert entre fermes
     */
    public function getEstTransfertAttribute(): bool
    {
        return strtoupper($this->type?->nom_type ?? '') === 'TRANSFERT'
            && $this->farm_destination_id !== null;
    }

    /**
     * Vérifie si cet événement a une transaction financière liée
     */
    public function getATransactionAttribute(): bool
    {
        return $this->transaction_id !== null;
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Uniquement les événements sanitaires (non-mouvements)
     */
    public function scopeSanitaires($query)
    {
        return $query->whereHas('type', function ($q) {
            $q->whereNotIn('nom_type', self::TYPES_MOUVEMENT);
        });
    }

    /**
     * Uniquement les mouvements
     */
    public function scopeMouvements($query)
    {
        return $query->whereHas('type', function ($q) {
            $q->whereIn('nom_type', self::TYPES_MOUVEMENT);
        });
    }

    /**
     * Événements d'une période donnée
     */
    public function scopePeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_evenement', [$debut, $fin]);
    }

    /**
     * Événements en attente de synchronisation
     */
    public function scopeEnAttente($query)
    {
        return $query->where('sync_status', 'pending');
    }

    // =========================================================
    // OBSERVER INTÉGRÉ — logique métier automatique
    // =========================================================

    protected static function booted(): void
    {
        static::created(function (Evenement $evenement) {
            $nomType = strtoupper($evenement->type?->nom_type ?? '');

            // 1. Mettre à jour le statut de l'animal si c'est un mouvement
            if ($evenement->statut_apres) {
                $evenement->animal->update([
                    'statut' => $evenement->statut_apres
                ]);
            }

            // 2. Changer la ferme de l'animal si c'est un transfert
            if ($nomType === 'TRANSFERT' && $evenement->farm_destination_id) {
                $evenement->animal->update([
                    'farm_id' => $evenement->farm_destination_id
                ]);
            }
        });
    }
}