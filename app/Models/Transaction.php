<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'type_transaction',
        'montant',
        'date_transaction',
        'user_id',
        'animal_id',
        'categorie_id',
        'description',
        'synced',
        'last_sync_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_transaction' => 'date',
        'synced' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

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