<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryItemLocation;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockLedgerService
{
    public function availableQuantity(InventoryItem $item, ?int $locationId = null): int
    {
        $locationId ??= $item->location_id;
        $balance = $this->locationBalance($item, $locationId);
        return max(0, $balance - $this->reservedQuantity($item->id, $locationId));
    }

    public function reservedQuantity(int $itemId, ?int $locationId = null): int
    {
        $query = StockReservation::query()
            ->where('inventory_item_id', $itemId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        $locationId === null ? $query->whereNull('location_id') : $query->where('location_id', $locationId);

        return (int) $query->selectRaw('COALESCE(SUM(quantity - fulfilled_qty), 0) AS reserved_total')->value('reserved_total');
    }

    public function totalReservedQuantity(int $itemId): int
    {
        return (int) StockReservation::query()
            ->where('inventory_item_id', $itemId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->selectRaw('COALESCE(SUM(quantity - fulfilled_qty), 0) AS reserved_total')
            ->value('reserved_total');
    }

    public function move(
        int $itemId,
        string $type,
        int $quantity,
        ?int $userId,
        ?string $reference = null,
        ?string $remarks = null,
        ?string $batchNo = null,
        ?string $expiryDate = null,
        ?int $locationId = null,
        ?int $toLocationId = null,
        ?int $batchId = null,
        ?int $reservationId = null,
        bool $useItemDefaultLocation = true,
    ): InventoryItem {
        $allowed = ['receive', 'issue', 'return', 'adjust', 'damaged', 'lost', 'transfer'];
        if (! in_array($type, $allowed, true)) {
            throw ValidationException::withMessages(['type' => 'Unsupported stock movement type.']);
        }
        if ($quantity < 0 || ($type !== 'adjust' && $quantity < 1)) {
            throw ValidationException::withMessages(['quantity' => 'Enter a valid stock quantity.']);
        }
        if ($expiryDate && ! $batchNo && $type === 'receive') {
            throw ValidationException::withMessages(['batch_no' => 'Enter a batch or lot number when recording an expiration date.']);
        }
        if ($type === 'transfer' && (! $locationId || ! $toLocationId || $locationId === $toLocationId)) {
            throw ValidationException::withMessages(['to_location_id' => 'Select two different source and destination locations.']);
        }

        return DB::transaction(function () use ($itemId, $type, $quantity, $userId, $reference, $remarks, $batchNo, $expiryDate, $locationId, $toLocationId, $batchId, $reservationId, $useItemDefaultLocation) {
            $item = InventoryItem::query()->whereKey($itemId)->lockForUpdate()->firstOrFail();
            $sourceLocationId = $useItemDefaultLocation ? ($locationId ?? $item->location_id) : $locationId;
            $source = $this->lockOrCreateBalance($item, $sourceLocationId);
            $before = (int) $source->quantity_on_hand;
            $globalBefore = (int) $item->quantity_on_hand;

            if ($type === 'transfer') {
                $destination = $this->lockOrCreateBalance($item, $toLocationId);
                $this->assertAvailable($item, $sourceLocationId, $quantity, null);
                $allocations = $this->allocateLots($item, $sourceLocationId, $quantity, $batchId, false);

                $source->quantity_on_hand = $before - $quantity;
                $source->save();
                $destination->quantity_on_hand = (int) $destination->quantity_on_hand + $quantity;
                $destination->save();

                foreach ($allocations as $allocation) {
                    $sourceBatch = $allocation['batch'];
                    $qty = $allocation['quantity'];
                    if ($sourceBatch) {
                        $sourceBatch->quantity_on_hand -= $qty;
                        $sourceBatch->save();
                        $destinationBatch = $this->findOrCreateBatch(
                            $item,
                            $toLocationId,
                            $sourceBatch->batch_no,
                            $sourceBatch->expiry_date?->toDateString(),
                            (float) $sourceBatch->unit_cost,
                            $sourceBatch->supplier_id
                        );
                        $destinationBatch->quantity_on_hand += $qty;
                        $destinationBatch->save();
                    } else {
                        $destinationBatch = null;
                    }
                    $this->recordMovement($item, 'transfer_out', -$qty, $userId, $reference, $remarks, $sourceLocationId, $toLocationId, $sourceBatch?->id, $sourceBatch?->batch_no, $sourceBatch?->expiry_date?->toDateString(), $reference, $sourceBatch ? (float) $sourceBatch->unit_cost : (float) $item->unit_cost);
                    $this->recordMovement($item, 'transfer_in', $qty, $userId, $reference, $remarks, $toLocationId, $sourceLocationId, $destinationBatch?->id, $destinationBatch?->batch_no, $destinationBatch?->expiry_date?->toDateString(), $reference, $destinationBatch ? (float) $destinationBatch->unit_cost : (float) $item->unit_cost);
                }

                StockTransaction::create([
                    'inventory_item_id' => $item->id, 'type' => 'transfer', 'quantity' => $quantity,
                    'balance_before' => $globalBefore, 'balance_after' => $globalBefore,
                    'reference' => $reference, 'remarks' => $remarks, 'user_id' => $userId,
                    'transaction_date' => today(),
                ]);
                return $item->refresh();
            }

            $delta = match ($type) {
                'receive', 'return' => $quantity,
                'issue', 'damaged', 'lost' => -$quantity,
                'adjust' => $quantity - $before,
            };
            $after = $before + $delta;
            if ($type === 'adjust' && $delta === 0) {
                throw ValidationException::withMessages(['quantity' => 'The entered balance is the same as the current balance. No adjustment is required.']);
            }
            if ($after < 0) {
                throw ValidationException::withMessages(['quantity' => "Insufficient stock at the selected location. Available quantity: {$before}."]);
            }

            if ($delta < 0 && $type !== 'adjust') {
                $this->assertAvailable($item, $sourceLocationId, abs($delta), $reservationId);
            }

            $allocations = [];
            if ($delta < 0) {
                $allocations = $this->allocateLots($item, $sourceLocationId, abs($delta), $batchId, $type === 'issue');
            } elseif ($delta > 0 && $batchId) {
                $batch = InventoryBatch::query()->whereKey($batchId)
                    ->where('inventory_item_id', $item->id)
                    ->where(function ($query) use ($sourceLocationId) {
                        $sourceLocationId === null ? $query->whereNull('location_id') : $query->where('location_id', $sourceLocationId);
                    })->lockForUpdate()->firstOrFail();
                $batch->quantity_on_hand += $delta;
                $batch->save();
                $allocations = [['batch' => $batch, 'quantity' => $delta]];
            } elseif ($delta > 0 && $batchNo) {
                $batch = $this->findOrCreateBatch($item, $sourceLocationId, $batchNo, $expiryDate, (float) $item->unit_cost, $item->supplier_id);
                $batch->quantity_on_hand += $delta;
                $batch->save();
                $allocations = [['batch' => $batch, 'quantity' => $delta]];
            } else {
                $allocations = [['batch' => null, 'quantity' => abs($delta)]];
            }

            $source->quantity_on_hand = $after;
            $source->save();
            $item->quantity_on_hand = $globalBefore + $delta;
            if (! $item->location_id && $sourceLocationId) $item->location_id = $sourceLocationId;
            $item->save();

            foreach ($allocations as $allocation) {
                $batch = $allocation['batch'];
                $qty = (int) $allocation['quantity'];
                if ($delta < 0 && $batch) {
                    $batch->quantity_on_hand -= $qty;
                    $batch->save();
                }
                $this->recordMovement(
                    $item,
                    $type,
                    $delta < 0 ? -$qty : $qty,
                    $userId,
                    $reference,
                    $remarks,
                    $sourceLocationId,
                    null,
                    $batch?->id,
                    $batch?->batch_no ?? ($batchNo ?: null),
                    $batch?->expiry_date?->toDateString() ?? $expiryDate,
                    $reference,
                    $batch ? (float) $batch->unit_cost : (float) $item->unit_cost
                );
            }

            StockTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => $type,
                'quantity' => abs($delta),
                'balance_before' => $globalBefore,
                'balance_after' => $globalBefore + $delta,
                'reference' => $reference,
                'remarks' => $remarks,
                'user_id' => $userId,
                'transaction_date' => today(),
            ]);

            if ($reservationId && $type === 'issue') {
                $reservation = StockReservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();
                abort_unless((int) $reservation->inventory_item_id === (int) $item->id && $reservation->status === 'active', 422, 'The selected reservation is no longer active for this item.');
                abort_unless($reservation->location_id === $sourceLocationId, 422, 'Reservation belongs to a different stock location.');
                abort_unless($quantity <= $reservation->remainingQuantity(), 422, 'The issue quantity exceeds the remaining reserved quantity.');
                $reservation->fulfilled_qty += $quantity;
                if ($reservation->fulfilled_qty >= $reservation->quantity) {
                    $reservation->status = 'fulfilled';
                    $reservation->fulfilled_at = now();
                }
                $reservation->save();
            }

            return $item->refresh();
        }, 3);
    }

    private function assertAvailable(InventoryItem $item, ?int $locationId, int $quantity, ?int $reservationId): void
    {
        $reservedQuery = StockReservation::query()
            ->where('inventory_item_id', $item->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        $locationId === null ? $reservedQuery->whereNull('location_id') : $reservedQuery->where('location_id', $locationId);
        $reserved = (int) $reservedQuery->selectRaw('COALESCE(SUM(quantity - fulfilled_qty),0) AS reserved_total')->value('reserved_total');
        if ($reservationId) {
            $reservation = StockReservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();
            abort_unless((int) $reservation->inventory_item_id === (int) $item->id, 422, 'Reservation does not belong to this stock item.');
            abort_unless($reservation->location_id === $locationId, 422, 'Reservation belongs to a different stock location.');
            abort_unless($reservation->status === 'active' && (! $reservation->expires_at || $reservation->expires_at->isFuture()), 422, 'This reservation has expired or is no longer active.');
            $reserved -= $reservation->remainingQuantity();
            abort_if($quantity > $reservation->remainingQuantity(), 422, 'The issue quantity exceeds the remaining reserved quantity.');
        }
        $balance = $this->locationBalance($item, $locationId);
        abort_if($quantity > ($balance - $reserved), 422, 'Insufficient available stock after active reservations.');
    }

    /** @return array<int, array{batch:?InventoryBatch,quantity:int}> */
    private function allocateLots(InventoryItem $item, ?int $locationId, int $quantity, ?int $batchId, bool $excludeExpired): array
    {
        $query = InventoryBatch::query()->where('inventory_item_id', $item->id)->where('quantity_on_hand', '>', 0);
        $locationId === null ? $query->whereNull('location_id') : $query->where('location_id', $locationId);
        if ($batchId) $query->whereKey($batchId);
        if ($excludeExpired) $query->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()));
        $batches = $query->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date')->orderBy('id')->lockForUpdate()->get();
        $allocations = [];
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $take = min($remaining, (int) $batch->quantity_on_hand);
            if ($take > 0) {
                $allocations[] = ['batch' => $batch, 'quantity' => $take];
                $remaining -= $take;
            }
        }

        $locationBalance = $this->locationBalance($item, $locationId);
        $trackedTotalQuery = InventoryBatch::query()->where('inventory_item_id', $item->id);
        $locationId === null ? $trackedTotalQuery->whereNull('location_id') : $trackedTotalQuery->where('location_id', $locationId);
        $trackedTotal = (int) $trackedTotalQuery->sum('quantity_on_hand');
        $untracked = max(0, $locationBalance - $trackedTotal);
        if ($remaining > 0 && ! $batchId && $remaining <= $untracked) {
            $allocations[] = ['batch' => null, 'quantity' => $remaining];
            $remaining = 0;
        }
        if ($remaining > 0) {
            $message = $batchId ? 'The selected batch does not contain enough unexpired stock.' : 'Not enough unexpired batch or untracked stock is available.';
            throw ValidationException::withMessages(['quantity' => $message]);
        }
        return $allocations;
    }

    private function lockOrCreateBalance(InventoryItem $item, ?int $locationId): InventoryItemLocation
    {
        $query = InventoryItemLocation::query()->where('inventory_item_id', $item->id);
        $locationId === null ? $query->whereNull('location_id') : $query->where('location_id', $locationId);
        $row = $query->lockForUpdate()->first();
        if ($row) return $row;

        return InventoryItemLocation::create([
            'office_id' => $item->office_id,
            'inventory_item_id' => $item->id,
            'location_id' => $locationId,
            'quantity_on_hand' => 0,
        ]);
    }

    private function locationBalance(InventoryItem $item, ?int $locationId): int
    {
        $query = InventoryItemLocation::query()->where('inventory_item_id', $item->id);
        $locationId === null ? $query->whereNull('location_id') : $query->where('location_id', $locationId);
        return (int) ($query->value('quantity_on_hand') ?? 0);
    }

    private function findOrCreateBatch(InventoryItem $item, ?int $locationId, string $batchNo, ?string $expiryDate, float $unitCost, ?int $supplierId): InventoryBatch
    {
        $query = InventoryBatch::query()
            ->where('inventory_item_id', $item->id)
            ->where('batch_no', $batchNo);
        $locationId === null ? $query->whereNull('location_id') : $query->where('location_id', $locationId);
        $expiryDate === null ? $query->whereNull('expiry_date') : $query->whereDate('expiry_date', $expiryDate);
        $batch = $query->lockForUpdate()->first();
        if ($batch) return $batch;

        return InventoryBatch::create([
            'office_id' => $item->office_id,
            'inventory_item_id' => $item->id,
            'location_id' => $locationId,
            'supplier_id' => $supplierId,
            'batch_no' => $batchNo,
            'expiry_date' => $expiryDate,
            'quantity_on_hand' => 0,
            'unit_cost' => $unitCost,
            'received_at' => today(),
        ]);
    }

    private function recordMovement(InventoryItem $item, string $type, int $signedQty, ?int $userId, ?string $reference, ?string $remarks, ?int $locationId, ?int $toLocationId, ?int $batchId, ?string $batchNo, ?string $expiryDate, ?string $referenceText = null, ?float $unitCost = null): void
    {
        StockMovement::create([
            'stock_item_id' => $item->id,
            'batch_id' => $batchId,
            'location_id' => $locationId,
            'to_location_id' => $toLocationId,
            'type' => $type,
            'qty' => $signedQty,
            'unit_cost' => $unitCost ?? (float) $item->unit_cost,
            'reference_text' => $referenceText,
            'batch_no' => $batchNo,
            'expiry_date' => $expiryDate,
            'performed_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
