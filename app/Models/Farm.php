<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Farm extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'farms';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'location',
        'description',
        'type_elevage',
        'photo',
        'owner_id',
        'sync_status',
        'last_modified_by',
        'version',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'sync_status' => 'string',
        'version' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($farm) {
            if (empty($farm->id)) {
                $farm->id = substr(\Illuminate\Support\Str::random(20), 0, 20);
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'farm_user')
            ->using(FarmUser::class)
            ->withPivot('role_id')
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
