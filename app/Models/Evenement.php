<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Evenement extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'evenements';

    protected $fillable = [
        'type_evenement_id',
        'animal_id',
        'date_evenement',
        'description',
        'cout',
        'synced',
        'last_sync_at',
    ];

    protected $casts = [
        'date_evenement' => 'date',
        'cout' => 'decimal:2',
        'synced' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function type()
    {
        return $this->belongsTo(TypeEvenement::class, 'type_evenement_id');
    }
}