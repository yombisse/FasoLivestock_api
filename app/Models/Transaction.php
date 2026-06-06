<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Transaction extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'transactions';

    protected $fillable = [
        'farm_id',
        'type_transaction',
        'montant',
        'date_transaction',
        'user_id',
        'animal_id',
        'categorie_id',
        'description',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_transaction' => 'date',
        'sync_status' => 'string',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }
}