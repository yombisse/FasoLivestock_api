<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    /**
     * Table associée
     */
    protected $table = 'password_reset_tokens';

    /**
     * Clé primaire
     */
    public $incrementing = true;
    protected $primaryKey = 'id';

    /**
     * Timestamps
     */
    public $timestamps = true;

    /**
     * Champs assignables
     */
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used',
        'used_at',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope pour les tokens valides (non expirés et non utilisés)
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->where('used', false);
    }
}