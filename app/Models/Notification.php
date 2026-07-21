<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasFarmScope;

class Notification extends Model
{
    use SoftDeletes, HasFarmScope;

    protected $table = 'notifications';
    protected $keyType = 'string';
    public $incrementing = false;

    const TYPE_VACCINATION = 'VACCINATION';
    const TYPE_TRAITEMENT  = 'TRAITEMENT';
    const TYPE_NAISSANCE   = 'NAISSANCE';
    const TYPE_MOUVEMENT   = 'MOUVEMENT';
    const TYPE_ALERTE      = 'ALERTE';
    const TYPE_INFO        = 'INFO';

    protected $fillable = [
        'farm_id',
        'animal_id',
        'titre',
        'message',
        'sent_at',
        'evenement_id',
        'type',
        // ─── Nouveau ─────────────
        'sante_rappel_id',
        // ─────────────────────────
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'sent_at'     => 'datetime',
        'sync_status' => 'string',
        'type'        => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            if (empty($notification->id)) {
                $notification->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
        });
    }

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

    public function users()
    {
        return $this->belongsToMany(User::class, 'recevoir')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps();
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }

    public function modificateur()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    // ─── Nouvelle relation ────────────────────────────────────
    public function santeRappel()
    {
        return $this->belongsTo(SanteRappel::class);
    }
    // ─────────────────────────────────────────────────────────

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeNonEnvoyees($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeEnvoyees($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeDeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeNonLuesPar($query, string $userId)
    {
        return $query->whereHas('users', function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('is_read', false);
        });
    }

    public function scopeEnAttente($query)
    {
        return $query->where('sync_status', 'pending');
    }

    // =========================================================
    // ACCESSEURS
    // =========================================================

    public function getEstEnvoyeeAttribute(): bool
    {
        return $this->sent_at !== null;
    }

    public function getNombreLusAttribute(): int
    {
        return $this->users()->wherePivot('is_read', true)->count();
    }

    /**
     * La notification vient-elle d'un rappel sanitaire ?
     */
    public function getEstRappelSanitaireAttribute(): bool
    {
        return $this->sante_rappel_id !== null;
    }

    // =========================================================
    // METHODES METIER
    // =========================================================

    public function marquerCommeLue(string $userId): void
    {
        $this->users()->updateExistingPivot($userId, [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function envoyerALaFerme(): void
    {
        $userIds  = $this->farm->users()->pluck('users.id');
        $existants = $this->users()->pluck('users.id');
        $nouveaux  = $userIds->diff($existants);

        if ($nouveaux->isNotEmpty()) {
            $this->users()->attach(
                $nouveaux->mapWithKeys(fn ($id) => [
                    $id => ['is_read' => false]
                ])->toArray()
            );
        }

        $this->update(['sent_at' => now()]);
    }
}