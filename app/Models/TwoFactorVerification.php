<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class TwoFactorVerification extends Model
{
    use HasFactory;

    protected $table = 'two_factor_verifications';

    protected $fillable = [
        'id',
        'user_id',
        'channel',
        'identifier',
        'code_hash',
        'expires_at',
        'used',
        'used_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
        'used_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public $incrementing = false;

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeValid($query)
    {
        return $query
            ->where('used', false)
            ->where('expires_at', '>', now());
    }
}

