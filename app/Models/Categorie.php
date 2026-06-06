<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Categorie extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'nom_categorie',
        'type',
    ];


    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}