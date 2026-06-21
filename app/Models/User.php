<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasRoles,HasApiTokens, HasFactory;


    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'telephone',
        'photo',
        'password',
        'is_active',
        'last_sync_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

  
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'recevoir')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps();
    }

    public function ownedFarms()
    {
        return $this->hasMany(Farm::class, 'owner_id');
    }

    public function farms()
    {
        return $this->belongsToMany(Farm::class, 'farm_user')
            ->withPivot('role')
            ->withTimestamps();
    }
}