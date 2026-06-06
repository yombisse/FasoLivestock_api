<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasFarmScope
{
    /**
     * Boot the trait
     */
    protected static function bootHasFarmScope()
    {
        static::addGlobalScope('farm', function (Builder $builder) {
            $user = Auth::user();
            $request = request();

            if ($user && $request && $request->has('current_farm_id')) {
                $builder->where('farm_id', $request->input('current_farm_id'));
            }
        });
    }

    /**
     * Get all records without farm scope
     */
    public function scopeWithoutFarmScope($query)
    {
        return $query->withoutGlobalScope('farm');
    }

    /**
     * Get records for a specific farm
     */
    public function scopeForFarm($query, $farmId)
    {
        return $query->where('farm_id', $farmId);
    }
}
