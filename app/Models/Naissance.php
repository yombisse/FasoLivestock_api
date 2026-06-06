<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Naissance extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'naissances';

    protected $fillable = [
        'farm_id',
        'mother_id',
        'date_naissance',
        'nombre_petits',
        'poids_naissance',
        'observation',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'poids_naissance' => 'decimal:2',
        'nombre_petits' => 'integer',
        'sync_status' => 'string',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function mother()
    {
        return $this->belongsTo(Animal::class, 'mother_id');
    }
}