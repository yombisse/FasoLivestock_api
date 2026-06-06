<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Lot extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'lots';

    protected $fillable = [
        'farm_id',
        'nom_lot',
        'nombre',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'nombre' => 'integer',
        'sync_status' => 'string',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }
}