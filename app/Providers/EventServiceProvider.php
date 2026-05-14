<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Registers event listeners and subscribers for the application.
 * Includes authentication event tracking for the audit trail.
 *
 * Design Tenets:
 * - **Explicit Registration**: All subscribers clearly listed
 * - **Type Safety**: Strict types for reliability
 */

namespace App\Providers;

use App\Events\MemberTierChanged;
use App\Events\StampCardCompleted;
use App\Events\StampEarned;
use App\Events\VoucherClaimed;
use App\Listeners\AuthEventSubscriber;
use App\Listeners\CreditPointsOnCompletion;
use App\Listeners\LogStampActivity;
use App\Listeners\LogTierChange;
use App\Listeners\LogVoucherActivity;
use App\Listeners\SendCompletionNotification;
use App\Listeners\SendStampMilestoneNotification;
use App\Listeners\SendTierChangeNotification;
use App\Listeners\SendVoucherClaimedNotification;
use App\Listeners\UpdateStampCardCounters;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        MemberTierChanged::class => [
            SendTierChangeNotification::class,
            LogTierChange::class,
        ],
        StampEarned::class => [
            SendStampMilestoneNotification::class,
        ],
        StampCardCompleted::class => [
            SendCompletionNotification::class,
            CreditPointsOnCompletion::class,
        ],
        VoucherClaimed::class => [
            SendVoucherClaimedNotification::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        AuthEventSubscriber::class,
        LogStampActivity::class,
        UpdateStampCardCounters::class,
        LogVoucherActivity::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
