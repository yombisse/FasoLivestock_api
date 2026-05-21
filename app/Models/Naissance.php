<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Naissance extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'naissances';

    protected $fillable = [
        'mother_id',
        'date_naissance',
        'nombre_petits',
        'poids_naissance',
        'observation',
        'synced',
        'last_sync_at',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'poids_naissance' => 'decimal:2',
        'nombre_petits' => 'integer',
        'synced' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function mother()
    {
        return $this->belongsTo(Animal::class, 'mother_id');
    }
}