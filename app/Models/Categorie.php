<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categorie extends Model
{
    use SoftDeletes;

    protected $table = 'categories';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nom_categorie',
        'type',
        'description',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'sync_status' => 'string',
        'type' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categorie) {
            if (empty($categorie->id)) {
                $categorie->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
        });
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}