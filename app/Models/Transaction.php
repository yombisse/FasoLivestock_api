<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Transaction extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'transactions';

    // ─── Constantes de type ───────────────────────────────────────
    const TYPE_ENTREE     = 'ENTREE';
    const TYPE_SORTIE     = 'SORTIE';
    const TYPE_TRANSFERT  = 'TRANSFERT';
    const TYPE_AJUSTEMENT = 'AJUSTEMENT';

    // Types qui génèrent une entrée d'argent
    const TYPES_REVENUS = ['ENTREE'];

    // Types qui génèrent une sortie d'argent
    const TYPES_CHARGES = ['SORTIE'];

    protected $fillable = [
        'farm_id',
        'numero_transaction',
        'type_transaction',
        'montant',
        'date_transaction',
        'user_id',
        'animal_id',
        'categorie_id',
        'description',
        // ─── Nouveau champ ───────────────
        'evenement_id',
        // ─────────────────────────────────
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'montant'          => 'decimal:2',
        'date_transaction' => 'date',
        'sync_status'      => 'string',
        'type_transaction' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            if (empty($transaction->numero_transaction)) {
                $transaction->numero_transaction = self::generateNumero();
            }
        });
    }

    /**
     * Générer un numéro de transaction unique
     * Format: TRX-YYYY-XXXXXX (ex: TRX-2026-000001)
     */
    public static function generateNumero(): string
    {
        $year = date('Y');
        $prefix = "TRX-{$year}-";
        
        $lastNumero = self::where('numero_transaction', 'like', "{$prefix}%")
            ->orderBy('numero_transaction', 'desc')
            ->value('numero_transaction');
        
        $sequence = $lastNumero 
            ? (int) substr($lastNumero, -6) + 1 
            : 1;
        
        return sprintf("{$prefix}%06d", $sequence);
    }

    // =========================================================
    // RELATIONS EXISTANTES
    // =========================================================

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    // =========================================================
    // NOUVELLES RELATIONS
    // =========================================================

    /**
     * Événement qui a généré cette transaction (vente, achat...)
     */
    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
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
     * Est-ce que cette transaction est un revenu ?
     */
    public function getEstRevenuAttribute(): bool
    {
        return $this->type_transaction === self::TYPE_ENTREE;
    }

    /**
     * Est-ce que cette transaction est une charge ?
     */
    public function getEstChargeAttribute(): bool
    {
        return $this->type_transaction === self::TYPE_SORTIE;
    }

    /**
     * Montant signé : positif si entrée, négatif si sortie
     * Utile pour les calculs de solde
     */
    public function getMontantSigneAttribute(): float
    {
        return $this->type_transaction === self::TYPE_SORTIE
            ? -abs($this->montant)
            : abs($this->montant);
    }

    /**
     * Est-ce que cette transaction est liée à un événement ?
     */
    public function getAEvenementAttribute(): bool
    {
        return $this->evenement_id !== null;
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Uniquement les entrées d'argent
     */
    public function scopeRevenus($query)
    {
        return $query->where('type_transaction', self::TYPE_ENTREE);
    }

    /**
     * Uniquement les sorties d'argent
     */
    public function scopeCharges($query)
    {
        return $query->where('type_transaction', self::TYPE_SORTIE);
    }

    /**
     * Transactions d'une période donnée
     */
    public function scopePeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_transaction', [$debut, $fin]);
    }

    /**
     * Transactions liées à un animal spécifique
     */
    public function scopePourAnimal($query, string $animalId)
    {
        return $query->where('animal_id', $animalId);
    }

    /**
     * Transactions en attente de synchronisation
     */
    public function scopeEnAttente($query)
    {
        return $query->where('sync_status', 'pending');
    }

    /**
     * Calcul du bilan financier sur une période
     * Retourne [ 'revenus' => X, 'charges' => Y, 'benefice' => Z ]
     */
    public function scopeBilan($query, $debut = null, $fin = null): array
    {
        $q = $debut && $fin
            ? $query->periode($debut, $fin)
            : $query;

        $revenus = (clone $q)->revenus()->sum('montant');
        $charges = (clone $q)->charges()->sum('montant');

        return [
            'revenus'  => $revenus,
            'charges'  => $charges,
            'benefice' => $revenus - $charges,
        ];
    }
}