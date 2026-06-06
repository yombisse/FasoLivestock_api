<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Evenement extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'evenements';

    protected $fillable = [
        'farm_id',
        'type_evenement_id',
        'animal_id',
        'date_evenement',
        'description',
        'cout',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'date_evenement' => 'date',
        'cout' => 'decimal:2',
        'sync_status' => 'string',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function type()
    {
        return $this->belongsTo(TypeEvenement::class, 'type_evenement_id');
    }
}