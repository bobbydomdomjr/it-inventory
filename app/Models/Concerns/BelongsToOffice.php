<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

trait BelongsToOffice
{
    protected static function bootBelongsToOffice(): void
    {
        static::addGlobalScope('office', function (Builder $builder): void {
            $user = Auth::user();
            if (! $user || $user->roles->contains('slug', 'super-admin')) {
                return;
            }

            if (! $user->office_id) {
                $builder->whereRaw('1 = 0');
                return;
            }

            $officeColumn = $builder->getModel()->qualifyColumn('office_id');
            $builder->where($officeColumn, $user->office_id)
                ->whereExists(function ($query) use ($officeColumn): void {
                    $query->selectRaw('1')
                        ->from('offices')
                        ->whereColumn('offices.id', $officeColumn)
                        ->where('offices.is_active', true)
                        ->whereNull('offices.deleted_at');
                });
        });

        static::creating(function ($model): void {
            $user = Auth::user();
            if (! $user) {
                return;
            }

            // Super administrators may create records across offices. When no
            // office was selected explicitly, place the new record in their
            // own office, or in the first active office as a safe default.
            if ($user->roles->contains('slug', 'super-admin')) {
                if (! $model->getAttribute('office_id')) {
                    $officeId = $user->office_id
                        ?: \App\Models\Office::query()->where('is_active', true)->value('id');

                    if ($officeId) {
                        $model->setAttribute('office_id', $officeId);
                    }
                }

                return;
            }

            if (! $user->office_id) {
                throw ValidationException::withMessages(['office_id' => 'Your account is not assigned to an office. Contact a system administrator.']);
            }

            $model->setAttribute('office_id', $user->office_id);
        });

        static::updating(function ($model): void {
            $user = Auth::user();
            if (! $user || $user->roles->contains('slug', 'super-admin')) {
                return;
            }

            if (! $user->office_id || (int) $model->getOriginal('office_id') !== (int) $user->office_id) {
                abort(403, 'You cannot modify records outside your assigned office.');
            }
            $model->setAttribute('office_id', $user->office_id);
        });
    }
}
