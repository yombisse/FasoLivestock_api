<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class FarmUser extends Pivot
{
    protected $table = 'farm_user';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'farm_id',
        'user_id',
        'role_id',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'sync_status' => 'string',
        'version' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($farmUser) {
            if (empty($farmUser->id)) {
                $farmUser->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
        });
    }
}
