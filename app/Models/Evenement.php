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

    // ─── Statuts des événements reproductifs ─────────────────────
    const STATUT_EN_COURS = 'EN_COURS';
    const STATUT_TERMINE = 'TERMINE';
    const STATUT_ANNULE = 'ANNULE';

    // ─── Types d'événements reproductifs (nom_type dans type_evenements) ───
    const TYPE_GESTATION = 'Gestation confirmée';
    const TYPE_MISE_BAS = 'Mise bas';
    const TYPE_SAILLIE = 'Saillie';
    const TYPE_CHALEUR = 'Chaleur';

    protected $fillable = [
        'farm_id',
        'type_evenement_id',
        'categorie',
        'animal_id',
        'date_evenement',
        'description',
        'metadonnees',
        'cout',
        'statut',
        'date_fin',
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
        'date_fin' => 'date',
        'cout'           => 'decimal:2',
        'sync_status'    => 'string',
        'statut_avant'   => 'string',
        'statut_apres'   => 'string',
        'categorie'      => 'string',
        'metadonnees'    => 'array',
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
     * Utilise la colonne categorie au lieu du nom_type pour plus de robustesse
     */
    public function getEstMouvementAttribute(): bool
    {
        return $this->categorie === 'MOUVEMENT';
    }

    /**
     * Vérifie si c'est un transfert entre fermes
     */
    public function getEstTransfertAttribute(): bool
    {
        return $this->categorie === 'MOUVEMENT'
            && strtoupper($this->type?->nom_type ?? '') === 'TRANSFERT'
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
        return $query->where('categorie', 'SANITAIRE');
    }

    /**
     * Uniquement les mouvements
     */
    public function scopeMouvements($query)
    {
        return $query->where('categorie', 'MOUVEMENT');
    }

    /**
     * Uniquement les événements reproductifs
     */
    public function scopeReproduction($query)
    {
        return $query->where('categorie', 'REPRODUCTION');
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
}