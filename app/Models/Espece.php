<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Espece extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'especes';

    protected $fillable = [
        'nom',
        'description',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function parametre()
    {
        return $this->hasOne(EspeceParametre::class);
    }
}