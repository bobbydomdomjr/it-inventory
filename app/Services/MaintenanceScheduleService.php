<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceRecord;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaintenanceScheduleService
{
    /** @return array{generated:int,skipped:int} */
    public function generateDue(?\DateTimeInterface $asOf = null): array
    {
        $date = $asOf ? \Illuminate\Support\Carbon::parse($asOf)->startOfDay() : today();
        $generated = 0;
        $skipped = 0;

        MaintenanceSchedule::query()
            ->where('is_active', true)
            ->whereDate('next_due', '<=', $date)
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $scheduleId) use ($date, &$generated, &$skipped): void {
                $result = DB::transaction(function () use ($scheduleId, $date): array {
                    $schedule = MaintenanceSchedule::whereKey($scheduleId)->lockForUpdate()->first();
                    if (! $schedule || ! $schedule->is_active || $schedule->next_due?->gt($date)) {
                        return ['generated' => 0, 'skipped' => 1];
                    }

                    $assets = Asset::query()
                        ->where(function ($query) use ($schedule) {
                            if ($schedule->asset_id) {
                                $query->whereKey($schedule->asset_id);
                            } else {
                                $query->where('category_id', $schedule->category_id);
                            }
                        })
                        ->whereNotIn('status', ['retired', 'lost', 'disposed'])
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    $created = 0;
                    foreach ($assets as $asset) {
                        $activeWorkOrder = WorkOrder::query()
                            ->where('asset_id', $asset->id)
                            ->whereIn('status', ['pending', 'assigned', 'in_progress', 'waiting_parts', 'external_repair'])
                            ->exists();
                        $activeLegacyRecord = MaintenanceRecord::query()
                            ->where('asset_id', $asset->id)
                            ->whereNotIn('status', ['completed', 'cancelled'])
                            ->exists();

                        if ($activeWorkOrder || $activeLegacyRecord) {
                            continue;
                        }

                        $sequenceKey = 'work-order:' . $date->format('Ym');
                        DB::table('number_sequences')->insertOrIgnore([
                            'key' => $sequenceKey, 'current_value' => 0,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $sequence = DB::table('number_sequences')->where('key', $sequenceKey)->lockForUpdate()->first();
                        $next = ((int) $sequence->current_value) + 1;
                        DB::table('number_sequences')->where('id', $sequence->id)->update(['current_value' => $next, 'updated_at' => now()]);

                        WorkOrder::create([
                            'wo_no' => sprintf('PM-%s-%05d', $date->format('Ym'), $next),
                            'asset_id' => $asset->id,
                            'maintenance_schedule_id' => $schedule->id,
                            'type' => 'preventive',
                            'priority' => 'normal',
                            'technician_id' => $schedule->assigned_to,
                            'status' => 'pending',
                            'findings' => 'Automatically generated from preventive maintenance schedule.',
                            'parts_used' => [],
                        ]);

                        if (in_array($asset->status, ['available', 'defective'], true)) {
                            $asset->update(['status' => 'maintenance']);
                        }
                        $created++;
                    }

                    // Move the next due date forward once this due cycle is processed.
                    $schedule->update(['next_due' => $date->copy()->addDays(max(1, (int) $schedule->interval_days))]);

                    return ['generated' => $created, 'skipped' => $created === 0 ? 1 : 0];
                }, 3);

                $generated += $result['generated'];
                $skipped += $result['skipped'];
            });

        return compact('generated', 'skipped');
    }
}
