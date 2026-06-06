<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Farm extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'farms';

    protected $fillable = [
        'name',
        'location',
        'description',
        'owner_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'farm_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function animals()
    {
        return $this->hasMany(Animal::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function evenements()
    {
        return $this->hasMany(Evenement::class);
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function naissances()
    {
        return $this->hasMany(Naissance::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('users', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->orWhere('owner_id', $userId);
    }
}
