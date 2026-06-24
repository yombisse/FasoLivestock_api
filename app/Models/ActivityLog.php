<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ActivityLog extends Model
{
    use HasUuids;

    protected $table = 'activity_logs';

    public $timestamps = false;

    protected $fillable = [
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
    ];

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
