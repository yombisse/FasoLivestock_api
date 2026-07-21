<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $keyType = 'string';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'user_id',
        'farm_id',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'farm_id' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                // Générer un ID simple compatible WatermelonDB (20 caractères max)
                $model->id = substr(strtoupper(md5(uniqid(mt_rand(), true))), 0, 20);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function scopeParFerme($query, string $farmId)
    {
        return $query->where('farm_id', $farmId);
    }

    public function scopeParModele($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    public function scopeParAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopePeriode($query, ?string $debut, ?string $fin)
    {
        if ($debut) {
            $query->where('created_at', '>=', $debut);
        }
        if ($fin) {
            $query->where('created_at', '<=', $fin);
        }
        return $query;
    }
}
