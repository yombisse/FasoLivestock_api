<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notification extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'animal_id',
        'titre',
        'message',
        'sent_at',
        'synced',
        'last_sync_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'synced' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

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