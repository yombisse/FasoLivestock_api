<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasFarmScope;

class Lot extends Model
{
    use SoftDeletes, HasFarmScope;

    protected $table = 'lots';
    protected $keyType = 'string';
    public $incrementing = false;

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lot) {
            if (empty($lot->id)) {
                $lot->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
        });
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }
}