<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Aliment extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'aliments';

    const UNITE_KG    = 'KG';
    const UNITE_LITRE = 'LITRE';
    const UNITE_SAC   = 'SAC';
    const UNITE_BOTTE = 'BOTTE';

    protected $fillable = [
        'farm_id',
        'nom',
        'unite',
        'prix_unitaire',
        'stock_actuel',
        'description',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'prix_unitaire' => 'decimal:2',
        'stock_actuel'  => 'decimal:2',
        'sync_status'   => 'string',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function rations()
    {
        return $this->hasMany(Ration::class);
    }

    public function modificateur()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeEnAttente($query)
    {
        return $query->where('sync_status', 'pending');
    }

    public function scopeEnRupture($query)
    {
        return $query->where('stock_actuel', '<=', 0);
    }

    // =========================================================
    // ACCESSEURS
    // =========================================================

    public function getEstEnRuptureAttribute(): bool
    {
        return $this->stock_actuel <= 0;
    }

    // =========================================================
    // METHODES METIER
    // =========================================================

    /**
     * Déduire du stock après une distribution de ration
     */
    public function deduireStock(float $quantite): void
    {
        $this->decrement('stock_actuel', $quantite);
    }

    /**
     * Ajouter au stock après un achat
     */
    public function approvisionner(float $quantite): void
    {
        $this->increment('stock_actuel', $quantite);
    }
}