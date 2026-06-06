<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Animal extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'animals';

    protected $fillable = [
        'farm_id',
        'nom',
        'race',
        'sexe',
        'date_naissance',
        'poids',
        'espece_id',
        'lot_id',
        'mother_id',
        'statut',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'poids' => 'decimal:2',
        'sync_status' => 'string',
    ];


    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function espece()
    {
        return $this->belongsTo(Espece::class);
    }


    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function mother()
    {
        return $this->belongsTo(Animal::class, 'mother_id');
    }


    public function children()
    {
        return $this->hasMany(Animal::class, 'mother_id');
    }

    public function evenements()
    {
        return $this->hasMany(Evenement::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function naissances()
    {
        return $this->hasMany(Naissance::class, 'mother_id');
    }
}