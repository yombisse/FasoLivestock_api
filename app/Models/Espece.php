<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Espece extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'especes';

    protected $fillable = [
        'nom',
        'description',
        'synced',
        'last_sync_at',
    ];

    protected $casts = [
        'synced' => 'boolean',
        'last_sync_at' => 'datetime',
    ];


    public function animals()
    {
        return $this->hasMany(Animal::class);
    }
}