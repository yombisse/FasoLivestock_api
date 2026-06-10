<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class SanteRappel extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'sante_rappels';

    const TYPE_VACCINATION = 'VACCINATION';
    const TYPE_TRAITEMENT  = 'TRAITEMENT';
    const TYPE_CONTROLE    = 'CONTROLE';

    const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    const STATUT_REALISE    = 'REALISE';
    const STATUT_EN_RETARD  = 'EN_RETARD';

    protected $fillable = [
        'farm_id',
        'animal_id',
        'type_rappel',
        'date_prevue',
        'date_realisee',
        'statut',
        'note',
        'evenement_id',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'date_prevue'   => 'date',
        'date_realisee' => 'date',
        'statut'        => 'string',
        'type_rappel'   => 'string',
        'sync_status'   => 'string',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
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
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    public function scopeEnRetard($query)
    {
        return $query->where('statut', self::STATUT_EN_RETARD);
    }

    public function scopeRealises($query)
    {
        return $query->where('statut', self::STATUT_REALISE);
    }

    public function scopeDeType($query, string $type)
    {
        return $query->where('type_rappel', $type);
    }

    /**
     * Rappels prévus dans les X prochains jours
     */
    public function scopeAVenir($query, int $jours = 7)
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE)
                     ->whereBetween('date_prevue', [now(), now()->addDays($jours)]);
    }

    public function scopeEnAttenteSynchro($query)
    {
        return $query->where('sync_status', 'pending');
    }

    // =========================================================
    // ACCESSEURS
    // =========================================================

    public function getEstEnRetardAttribute(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE
            && $this->date_prevue->isPast();
    }

    public function getJoursRestantsAttribute(): int
    {
        if ($this->statut === self::STATUT_REALISE) return 0;
        return (int) now()->diffInDays($this->date_prevue, false);
    }

    // =========================================================
    // METHODES METIER
    // =========================================================

    /**
     * Marquer le rappel comme réalisé et lier l'événement
     */
    public function marquerRealise(string $evenementId): void
    {
        $this->update([
            'statut'        => self::STATUT_REALISE,
            'date_realisee' => now(),
            'evenement_id'  => $evenementId,
        ]);
    }

    /**
     * Mettre à jour les statuts EN_RETARD automatiquement
     * À appeler via un Job schedulé quotidiennement
     */
    public static function mettreAJourRetards(): void
    {
        static::where('statut', self::STATUT_EN_ATTENTE)
              ->where('date_prevue', '<', now())
              ->update(['statut' => self::STATUT_EN_RETARD]);
    }
}