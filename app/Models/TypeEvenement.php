<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeEvenement extends Model
{
    use SoftDeletes;

    protected $table = 'type_evenements';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nom_type',
        'description',
        'categorie',
        'is_system',
        'farm_id',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'sync_status' => 'string',
        'categorie' => 'string',
        'is_system' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($typeEvenement) {
            if (empty($typeEvenement->id)) {
                $typeEvenement->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
        });
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'type_evenement_id');
    }

    public function scopeVisiblePour($query, string $farmId)
    {
        return $query->whereNull('farm_id')
                    ->orWhere('farm_id', $farmId);
    }
}