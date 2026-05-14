{{--
  Reward Loyalty - Proprietary Software
  Copyright (c) 2025 NowSquare. All rights reserved.
  See LICENSE file for terms.

  Purpose:
  Displays a vertical timeline of activity log entries with event icons,
  descriptions, timestamps, and optional causer information.

  Usage:
  <x-ui.activity-timeline :activities="$activities" :limit="10" />

  Props:
  - activities: Collection of Activity models
  - limit: Maximum items to display (default: 10)
  - showCauser: Whether to show who caused the activity (default: true)
  - compact: Use compact styling (default: false)
--}}

@props([
    'activities',
    'limit' => 10,
    'showCauser' => true,
    'compact' => false
])

@php
    $displayActivities = $activities->take($limit);

    // Event color mapping
    $eventColors = [
        'created' => 'bg-green-500',
        'updated' => 'bg-blue-500',
        'deleted' => 'bg-red-500',
        'login' => 'bg-purple-500',
        'logout' => 'bg-secondary-400',
        'login_failed' => 'bg-amber-500',
        'lockout' => 'bg-red-600',
        'password_reset' => 'bg-indigo-500',
        'api_request' => 'bg-cyan-500',
    ];

    // Event icon mapping
    $eventIcons = [
        'created' => 'plus-circle',
        'updated' => 'edit',
        'deleted' => 'trash-2',
        'login' => 'log-in',
        'logout' => 'log-out',
        'login_failed' => 'alert-triangle',
        'lockout' => 'lock',
        'password_reset' => 'key',
        'api_request' => 'zap',
    ];
@endphp

@if($displayActivities->isEmpty())
    <div class="text-center py-8 text-secondary-500 dark:text-secondary-400">
        <x-ui.icon icon="activity" class="w-12 h-12 mx-auto mb-3 opacity-50" />
        <p class="text-sm">{{ trans('common.no_activity_yet') }}</p>
    </div>
@else
    <div class="flow-root">
        <ul role="list" class="{{ $compact ? '-mb-6' : '-mb-8' }}">
            @foreach($displayActivities as $activity)
                <li>
                    <div class="relative {{ $compact ? 'pb-6' : 'pb-8' }}">
                        {{-- Timeline connector line --}}
                        @unless($loop->last)
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-secondary-200 dark:bg-secondary-700" aria-hidden="true"></span>
                        @endunless

                        <div class="relative flex {{ $compact ? 'space-x-2' : 'space-x-3' }}">
                            {{-- Event icon --}}
                            <div>
                                @php
                                    $colorClass = $eventColors[$activity->event] ?? 'bg-secondary-500';
                                    $iconName = $eventIcons[$activity->event] ?? 'activity';
                                @endphp
                                <span class="flex {{ $compact ? 'h-6 w-6' : 'h-8 w-8' }} items-center justify-center rounded-full {{ $colorClass }} ring-4 ring-white dark:ring-secondary-900 transition-transform hover:scale-110">
                                    <x-ui.icon :icon="$iconName" class="{{ $compact ? 'h-3 w-3' : 'h-4 w-4' }} text-white" />
                                </span>
                            </div>

                            {{-- Content --}}
                            <div class="flex min-w-0 flex-1 justify-between {{ $compact ? 'pt-0.5' : 'pt-1.5' }} space-x-4">
                                <div>
                                    <p class="{{ $compact ? 'text-xs' : 'text-sm' }} text-secondary-700 dark:text-secondary-300">
                                        {{ $activity->description }}
                                        @if($showCauser && $activity->causer)
                                            <span class="font-medium text-secondary-900 dark:text-white">
                                                {{ trans('common.by') }} {{ $activity->causer->name ?? $activity->causer->email }}
                                            </span>
                                        @endif
                                    </p>

                                    {{-- Show changes if available (for updates) --}}
                                    @if($activity->event === 'updated' && $activity->hasLoggedChanges())
                                        <div class="mt-1 {{ $compact ? 'text-xs' : 'text-sm' }} text-secondary-500 dark:text-secondary-400">
                                            @php
                                                $changes = $activity->new_values;
                                                $changedFields = array_keys($changes);
                                            @endphp
                                            <span class="italic">
                                                {{ trans('common.changed') }}: {{ implode(', ', array_slice($changedFields, 0, 3)) }}
                                                @if(count($changedFields) > 3)
                                                    {{ trans('common.and_more', ['count' => count($changedFields) - 3]) }}
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- Timestamp --}}
                                <div class="whitespace-nowrap text-right {{ $compact ? 'text-xs' : 'text-sm' }} text-secondary-500 dark:text-secondary-400">
                                    <time datetime="{{ $activity->created_at->toISOString() }}" title="{{ $activity->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </time>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Show more indicator --}}
    @if($activities->count() > $limit)
        <div class="mt-4 text-center">
            <span class="{{ $compact ? 'text-xs' : 'text-sm' }} text-secondary-500 dark:text-secondary-400">
                {{ trans('common.showing_of_total', ['showing' => $limit, 'total' => $activities->count()]) }}
            </span>
        </div>
    @endif
@endif

