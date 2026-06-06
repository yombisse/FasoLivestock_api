<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFarmScope;

class Notification extends Model
{
    use HasUuids, SoftDeletes, HasFarmScope;

    protected $table = 'notifications';

    protected $fillable = [
        'farm_id',
        'animal_id',
        'titre',
        'message',
        'sent_at',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'sync_status' => 'string',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'recevoir')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps();
    }
}