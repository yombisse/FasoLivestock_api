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
        'synced',
        'last_sync_at',
    ];

    protected $casts = [
        'synced' => 'boolean',
        'last_sync_at' => 'datetime',
    ];


    public function evenements()
    {
        return $this->hasMany(Evenement::class, 'type_evenement_id');
    }
}