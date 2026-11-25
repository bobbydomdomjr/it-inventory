<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

trait TracksCreators
{
    protected static function bootTracksCreators(): void
    {
        static::creating(function ($model): void {
            $userId = Auth::id();
            if ($userId && $model->getAttribute('created_by') === null) {
                $model->setAttribute('created_by', $userId);
            }
            if ($userId && $model->getAttribute('updated_by') === null) {
                $model->setAttribute('updated_by', $userId);
            }
        });

        static::updating(function ($model): void {
            if ($userId = Auth::id()) {
                $model->setAttribute('updated_by', $userId);
            }
        });
    }
}
