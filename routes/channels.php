<?php

declare(strict_types=1);

use App\Models\Member;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('member.{memberId}', function (Member $member, string $memberId): bool {
    return (string) $member->id === (string) $memberId;
});
