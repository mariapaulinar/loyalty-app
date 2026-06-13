<?php

declare(strict_types=1);

namespace App\Events\Broadcasting;

use App\Models\Member;
use App\Models\StampCard;
use App\Models\StampTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the member's browser when staff awards a stamp.
 * Triggers the experience-rating modal on the member frontend.
 */
class StaffStampEarned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public StampTransaction $transaction,
        public StampCard $card,
        public Member $member,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('member.'.$this->member->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stamp.earned';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'stamp_card_id' => $this->card->id,
            'stamp_card_title' => $this->card->title,
            'stamps' => $this->transaction->stamps,
            'current_stamps' => $this->transaction->stamps_after,
            'stamps_required' => $this->card->stamps_required,
        ];
    }
}
