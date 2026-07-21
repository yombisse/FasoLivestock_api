<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Espece extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'especes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nom',
        'description',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($espece) {
            if (empty($espece->id)) {
                $espece->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
        });
    }


    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function parametre()
    {
        return $this->hasOne(EspeceParametre::class);
    }
}