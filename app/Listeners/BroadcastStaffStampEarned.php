<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Broadcasting\StaffStampEarned as StaffStampEarnedBroadcast;
use App\Events\StampEarned;
use App\Models\StampTransaction;
use Illuminate\Support\Facades\Log;

/**
 * Broadcasts a websocket event to the member when staff awards a stamp.
 */
class BroadcastStaffStampEarned
{
    public function handle(StampEarned $event): void
    {
        if ($event->transaction->staff_id === null) {
            return;
        }

        if ($event->transaction->event !== StampTransaction::EVENT_STAMP_EARNED) {
            return;
        }

        try {
            broadcast(new StaffStampEarnedBroadcast(
                transaction: $event->transaction,
                card: $event->card,
                member: $event->member,
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to broadcast staff stamp earned event', [
                'transaction_id' => $event->transaction->id,
                'member_id' => $event->member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
