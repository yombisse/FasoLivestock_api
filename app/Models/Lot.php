<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Lot extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'lots';

    protected $fillable = [
        'nom_lot',
        'nombre',
        'synced',
        'last_sync_at',
    ];

    protected $casts = [
        'nombre' => 'integer',
        'synced' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }
}