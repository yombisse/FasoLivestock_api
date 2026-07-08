<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FarmUser extends Pivot
{
    use HasUuids;

    protected $table = 'farm_user';

    protected $fillable = [
        'farm_id',
        'user_id',
        'role',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'role' => 'string',
        'sync_status' => 'string',
        'version' => 'integer',
    ];
}
