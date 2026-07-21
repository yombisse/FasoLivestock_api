<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, HasApiTokens;

    protected $table = 'users';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->id)) {
                $user->id = Str::random(20);
            }
        });
    }

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
            ->withPivot('role_id')
            ->withTimestamps();
    }
}