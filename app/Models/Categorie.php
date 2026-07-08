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
        'description',
        'farm_id',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'sync_status' => 'string',
        'type' => 'string',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }
}