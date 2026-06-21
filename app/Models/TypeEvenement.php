<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TypeEvenement extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'type_evenements';

    protected $fillable = [
        'nom_type',
        'description',
        'farm_id',
        'sync_status',
        'last_modified_by',
    ];

    protected $casts = [
        'sync_status' => 'string',
    ];

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