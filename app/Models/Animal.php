<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasFarmScope;

class Animal extends Model
{
    use HasFactory, SoftDeletes, HasFarmScope;

    protected $table = 'animals';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'farm_id',
        'farm_source_id',
        'nom',
        'race',
        'sexe',
        'date_naissance',
        'poids',
        'espece_id',
        'lot_id',
        'mother_id',
        'origine',
        'numero_identification',
        'photo',
        'naissance_id',
        'statut',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($animal) {
            if (empty($animal->id)) {
                $animal->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
            // Définir un statut par défaut si non fourni
            if (empty($animal->statut)) {
                $animal->statut = 'SAIN';
            }
            // Si aucun numéro d'identification n'est fourni, en générer un
            if (empty($animal->numero_identification)) {
                $animal->numero_identification = self::generateNumeroIdentification();
            }
        });
    }

    /**
     * Générer un numéro d'identification unique
     * Format: ANI-YYYY-XXXXXX (ex: ANI-2026-000001)
     */
    public static function generateNumeroIdentification(): string
    {
        $year = date('Y');
        $prefix = "ANI-{$year}-";
        
        $lastNumero = self::where('numero_identification', 'like', "{$prefix}%")
            ->orderBy('numero_identification', 'desc')
            ->value('numero_identification');
        
        $sequence = $lastNumero 
            ? (int) substr($lastNumero, -6) + 1 
            : 1;
        
        return sprintf("{$prefix}%06d", $sequence);
    }

    const ORIGINE_IMPORT     = 'import';
    const ORIGINE_ACHAT      = 'achat';
    const ORIGINE_NAISSANCE  = 'naissance';

    const ORIGINES = [
        self::ORIGINE_IMPORT,
        self::ORIGINE_ACHAT,
        self::ORIGINE_NAISSANCE,
    ];

    const SEXE_MALE    = 'male';
    const SEXE_FEMELLE = 'femelle';

    const SEXES = [
        self::SEXE_MALE,
        self::SEXE_FEMELLE,
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'poids' => 'decimal:2',
        'sync_status' => 'string',
    ];


    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function farmSource()
    {
        return $this->belongsTo(Farm::class, 'farm_source_id');
    }

    public function espece()
    {
        return $this->belongsTo(Espece::class);
    }


    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function mother()
    {
        return $this->belongsTo(Animal::class, 'mother_id');
    }


    public function children()
    {
        return $this->hasMany(Animal::class, 'mother_id');
    }

    public function evenements()
    {
        return $this->hasMany(Evenement::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function naissances()
    {
        return $this->hasMany(Naissance::class, 'mother_id');
    }

    // =========================================================
    // SCOPES REPRODUCTIFS
    // =========================================================

    /**
     * Femelles avec une gestation confirmée EN_COURS.
     * Utilisé pour filtrer les mères éligibles à une déclaration
     * de naissance.
     */
    public function scopeEligiblesNaissance($query, $farmId)
    {
        return $query
            ->where('farm_id', $farmId)
            ->where('sexe', 'femelle')
            ->where('statut', 'SAIN')
            ->whereHas('evenements', function ($q) {
                $q->whereHas('type', function ($tq) {
                    $tq->where('nom_type', 'Gestation');
                })
                ->where('statut', 'EN_COURS');
            });
    }

    /**
     * Vérifie si cet animal a une gestation confirmée en cours.
     */
    public function aGestationEnCours(): bool
    {
        return $this->evenements()
            ->whereHas('type', fn($q) =>
                $q->where('nom_type', 'Gestation confirmée')
            )
            ->where('statut', 'EN_COURS')
            ->exists();
    }
}