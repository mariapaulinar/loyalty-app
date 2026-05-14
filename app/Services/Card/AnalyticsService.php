<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 */

namespace App\Services\Card;

use App\Http\Controllers\Cookie\CookieController;
use App\Models\Analytic;
use App\Models\Card;
use App\Models\Member;
use App\Models\Reward;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Increment the views for a primary and optional secondary model, once per unique visitor.
     *
     * @param  Model  $model  The primary Eloquent model.
     * @param  Model|null  $secondaryModel  Optional secondary Eloquent model.
     * @return bool True if the view is incremented (i.e., the session didn't exist yet),
     *              false if the session already exists and the view count is not incremented.
     */
    public function incrementViews(Model $model, ?Model $secondaryModel = null): bool
    {
        // Define the session key based on the class name and the ID of the model
        $session_key = class_basename(get_class($model)).'_viewed_'.$model->id;

        // Check if a session value exists
        if (! session($session_key)) {
            // Increment the views column and update the last_view column.
            // Increment the views column.
            $model->increment('views');
            $model->where('id', $model->id)->update(['last_view' => Carbon::now()]);

            // Add analytic
            $this->addViewAnalytic($model, $secondaryModel);

            // Set the session value
            session([$session_key => 'true']);

            // Return true as the view count was incremented
            return true;
        }

        // Return false as the view count was not incremented
        return false;
    }

    /**
     * Adds the view analytics entry for a given model and an optional secondary model.
     *
     * @param  Model  $model  The primary Eloquent model.
     * @param  Model|null  $secondaryModel  Optional secondary Eloquent model.
     */
    private function addViewAnalytic(Model $model, ?Model $secondaryModel = null): void
    {
        $eventType = null;
        $cardId = null;
        $rewardId = null;
        $stampCardId = null;
        $voucherId = null;

        if (class_basename($model) === 'Card') {
            $eventType = 'card_view';
            $cardId = $model->id;
        }

        if (class_basename($model) === 'Reward') {
            $eventType = 'reward_view';
            $rewardId = $model->id;
            $cardId = $secondaryModel ? $secondaryModel->id : null;
        }

        if (class_basename($model) === 'StampCard') {
            $eventType = 'stamp_card_view';
            $stampCardId = $model->id;
        }

        if (class_basename($model) === 'Voucher') {
            $eventType = 'voucher_view';
            $voucherId = $model->id;
        }

        $member_id = CookieController::userConsentsToCookies() && auth('member')->check()
            ? auth('member')->user()->id
            : null;

        // Get partner_id - StampCard and Voucher have partner through club relationship
        $partnerId = null;
        if (class_basename($model) === 'StampCard' || class_basename($model) === 'Voucher') {
            $partnerId = $model->club->created_by;
        } else {
            $partnerId = $model->partner->id;
        }

        Analytic::create([
            'partner_id' => $partnerId,
            'member_id' => $member_id,
            'staff_id' => null,
            'card_id' => $cardId,
            'reward_id' => $rewardId,
            'stamp_card_id' => $stampCardId ?? null,
            'voucher_id' => $voucherId ?? null,
            'event' => $eventType,
            'locale' => app()->make('i18n')->language->current->locale,
        ]);
    }

    /**
     * Adds issue points analytics entry.
     *
     * @param  Card  $card  The Card model.
     * @param  Staff  $staff  The Staff model.
     * @param  Member  $member  The Member model.
     * @param  int  $points  The amount of points issued.
     * @param  string  $currency  The currency of the purchase.
     * @param  int  $purchase_amount  The amount of the purchase.
     * @param  string  $created_at  Optional date.
     */
    public function addIssueAnalytic(Card $card, ?Staff $staff, Member $member, int $points, string $currency, int $purchase_amount, ?string $created_at = null): void
    {
        Analytic::create([
            'partner_id' => $card->partner->id,
            'member_id' => $member->id,
            'staff_id' => $staff?->id,
            'card_id' => $card->id,
            'points' => $points,
            'currency' => $currency,
            'purchase_amount' => $purchase_amount,
            'event' => 'issue_points',
            'created_at' => $created_at ?? Carbon::now('UTC'),
            'updated_at' => $created_at ?? Carbon::now('UTC'),
        ]);
    }

    /**
     * Adds redeem code analytics entry.
     *
     * @param  Card  $card  The Card model.
     * @param  Staff  $staff  The Staff model.
     * @param  Member  $member  The Member model.
     * @param  int  $points  The amount of points issued.
     * @param  string  $created_at  Optional date.
     */
    public function addRedeemCodeAnalytic(Card $card, Staff $staff, Member $member, int $points, $created_at = null): void
    {
        Analytic::create([
            'partner_id' => $card->partner->id,
            'member_id' => $member->id,
            'staff_id' => $staff->id,
            'card_id' => $card->id,
            'points' => $points,
            'event' => 'issue_points',
            'created_at' => $created_at ?? Carbon::now('UTC'),
            'updated_at' => $created_at ?? Carbon::now('UTC'),
        ]);
    }

    /**
     * Adds request points analytics entry.
     *
     * @param  string  $event  The event .
     * @param  Card  $card  The Card model.
     * @param  Member  $member  The Member model.
     * @param  int  $points  The amount of points issued.
     * @param  string  $created_at  Optional date.
     */
    public function addRequestPointsAnalytic($event, Card $card, Member $member, int $points, $created_at = null): void
    {
        Analytic::create([
            'partner_id' => $card->partner->id,
            'member_id' => $member->id,
            'card_id' => $card->id,
            'points' => $points,
            'event' => $event,
            'created_at' => $created_at ?? Carbon::now('UTC'),
            'updated_at' => $created_at ?? Carbon::now('UTC'),
        ]);
    }

    /**
     * Adds claim reward analytics entry.
     *
     * @param  Card  $card  The Card model.
     * @param  Staff  $staff  The Staff model.
     * @param  Member  $member  The Member model.
     * @param  Reward  $reward  The Reward model.
     * @param  string  $created_at  Optional date.
     */
    public function addClaimRewardAnalytic(Card $card, ?Staff $staff, Member $member, Reward $reward, ?string $created_at = null): void
    {
        Analytic::create([
            'partner_id' => $card->partner->id,
            'member_id' => $member->id,
            'staff_id' => $staff?->id,
            'card_id' => $card->id,
            'reward_id' => $reward->id,
            'points' => $reward->points,
            'event' => 'claim_reward',
            'created_at' => $created_at ?? Carbon::now('UTC'),
            'updated_at' => $created_at ?? Carbon::now('UTC'),
        ]);
    }

    /**
     * Get the user's time zone if they're logged in, otherwise get the default time zone
     *
     * @return DateTimeZone
     */
    private function getUserTimeZone(): \DateTimeZone
    {
        $timeZone = auth('partner')->check()
            ? auth('partner')->user()->time_zone
            : app()->make('i18n')->time_zone;

        return new \DateTimeZone($timeZone);
    }

    /**
     * Get the number of card views for each hour of a given day or the current day if no day is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $day  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of views for each hour of the day, the hours of the day, and a label with the current day.
     */
    public function cardViewsDay(string $cardId, ?string $day = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no day is given, default to today.
        $day = $day ? Carbon::createFromFormat('Y-m-d', $day, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all hours of the day, from 0 to 23.
        $viewsPerHour = array_fill_keys(range(0, 23), 0);

        // Fetch analytics for the card views on the given day.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection == 'sqlite' ? 'strftime("%H", created_at)' : 'DATE_FORMAT(created_at, "%H")';

        // $analytics = Analytic::whereIn('event', ['card_view', 'reward_view'])
        $analytics = Analytic::where('event', 'card_view')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$day->startOfDay()->toDateTimeString(), $day->endOfDay()->toDateTimeString()])
            ->selectRaw($dateFunction.' as hour, COUNT(*) as views')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $hour => $analytic) {
            $utcTime = Carbon::createFromFormat('H:i', $hour.':00', 'UTC');
            $userTime = $utcTime->setTimezone($timeZone);
            $userHour = $userTime->format('G');
            $viewsPerHour[$userHour] = $analytic->views;
            $total += $analytic->views;
        }

        // Get the hours of the day and convert them to two digit strings.
        $hours = array_map(function ($hour) {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
        }, array_keys($viewsPerHour));

        // Create a label for the current day.
        $label = '<span class="format-date" data-date-format="xl">'.$day->format('Y-m-d\TH:i:sP').'</span>';

        return ['units' => $hours, 'views' => array_values($viewsPerHour), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of card views for each day of a given week or the current week if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of views for each day of the week, the days of the week,
     *               and the label for the week and its range.
     */
    public function cardViewsWeek(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();
        // $timeZone = 'America/New_York';

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all days of the week.
        $viewsPerDay = array_fill_keys(range(0, 6), 0);

        // Fetch analytics for the card views in the given week.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%w", created_at)' : 'DAYOFWEEK(created_at) - 1';

        // $analytics = Analytic::whereIn('event', ['card_view', 'reward_view'])
        $analytics = Analytic::where('event', 'card_view')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->copy()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(), $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as views')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $day = intval($day);
            $viewsPerDay[$day] = $analytic->views;
            $total += $analytic->views;
        }

        // Define days of the week.
        $days = array_map(function ($dayIndex) use ($date) {
            return $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays((int) $dayIndex)->translatedFormat('D');
        }, range(0, 6));

        // Define the label for the week and its range.
        $startOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6);
        $label = trans('common.week').' '.$date->weekOfYear.', '.$date->year.' <span class="format-date-range text-sm ml-2" data-date-from="'.$startOfWeek->format('Y-m-d\TH:i:sP').'" data-date-to="'.$endOfWeek->format('Y-m-d\TH:i:sP').'">• '.$startOfWeek->format('M j').' - '.$endOfWeek->format('M j').'</span>';

        return ['units' => $days, 'views' => array_values($viewsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of card views for each day of a given month or the current month if no month is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of views for each day of the month, the days of the month,
     *               and the label containing the name of the month and the year.
     */
    public function cardViewsMonth(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Determine the number of days in the month.
        $daysInMonth = $date->daysInMonth;

        // Initialize views for all days of the month.
        $viewsPerDay = array_fill_keys(range(1, $daysInMonth), 0);

        // Fetch analytics for the card views in the given month.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%d", created_at)' : 'DAY(created_at)';

        // $analytics = Analytic::whereIn('event', ['card_view', 'reward_view'])
        $analytics = Analytic::where('event', 'card_view')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfMonth()->toDateTimeString(), $date->endOfMonth()->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as views')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $viewsPerDay[intval($day)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define days of the month.
        $days = array_map(function ($day) {
            return str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }, array_keys($viewsPerDay));

        // Define the label for the month and year.
        $label = mb_ucfirst($date->translatedFormat('F')).' '.$date->translatedFormat('Y');

        return ['units' => $days, 'views' => array_values($viewsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of card views for each month of a given year or the current year if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of views for each month of the year, the months of the year,
     *               and the label containing the year.
     */
    public function cardViewsYear(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all months of the year.
        $viewsPerMonth = array_fill_keys(range(1, 12), 0);

        // Fetch analytics for the card views in the given year.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        // $analytics = Analytic::whereIn('event', ['card_view', 'reward_view'])
        $analytics = Analytic::where('event', 'card_view')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfYear()->toDateTimeString(), $date->endOfYear()->toDateTimeString()])
            ->selectRaw($dateFunction.' as month, COUNT(*) as views')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $month => $analytic) {
            $viewsPerMonth[intval($month)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define months of the year.
        $months = array_map(function ($month) use ($date) {
            return $date->copy()->day(1)->month($month)->translatedFormat('F');
        }, array_keys($viewsPerMonth));

        // Define the label for the year.
        $label = $date->format('Y');

        return ['units' => $months, 'views' => array_values($viewsPerMonth), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of reward views for each hour of a given day or the current day if no day is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $day  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of views for each hour of the day, the hours of the day, and a label with the current day.
     */
    public function rewardViewsDay(string $cardId, ?string $day = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no day is given, default to today.
        $day = $day ? Carbon::createFromFormat('Y-m-d', $day, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all hours of the day, from 0 to 23.
        $viewsPerHour = array_fill_keys(range(0, 23), 0);

        // Fetch analytics for the card views on the given day.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection == 'sqlite' ? 'strftime("%H", created_at)' : 'DATE_FORMAT(created_at, "%H")';

        // $analytics = Analytic::whereIn('event', ['card_view', 'reward_view'])
        $analytics = Analytic::where('event', 'reward_view')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$day->startOfDay()->toDateTimeString(), $day->endOfDay()->toDateTimeString()])
            ->selectRaw($dateFunction.' as hour, COUNT(*) as views')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $hour => $analytic) {
            $utcTime = Carbon::createFromFormat('H:i', $hour.':00', 'UTC');
            $userTime = $utcTime->setTimezone($timeZone);
            $userHour = $userTime->format('G');
            $viewsPerHour[$userHour] = $analytic->views;
            $total += $analytic->views;
        }

        // Get the hours of the day and convert them to two digit strings.
        $hours = array_map(function ($hour) {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
        }, array_keys($viewsPerHour));

        // Create a label for the current day.
        $label = '<span class="format-date" data-date-format="xl">'.$day->format('Y-m-d\TH:i:sP').'</span>';

        return ['units' => $hours, 'views' => array_values($viewsPerHour), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of reward views for each day of a given week or the current week if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of views for each day of the week, the days of the week,
     *               and the label for the week and its range.
     */
    public function rewardViewsWeek(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all days of the week.
        $viewsPerDay = array_fill_keys(range(0, 6), 0);

        // Fetch analytics for the card views in the given week.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%w", created_at)' : 'DAYOFWEEK(created_at) - 1';

        // $analytics = Analytic::whereIn('event', ['card_view', 'reward_view'])
        $analytics = Analytic::where('event', 'reward_view')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->copy()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(), $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as views')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $day = intval($day);
            $viewsPerDay[$day] = $analytic->views;
            $total += $analytic->views;
        }

        // Define days of the week.
        $days = array_map(function ($dayIndex) use ($date) {
            return $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays((int) $dayIndex)->translatedFormat('D');
        }, range(0, 6));

        // Define the label for the week and its range.
        $startOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6);
        $label = trans('common.week').' '.$date->weekOfYear.', '.$date->year.' <span class="format-date-range text-sm ml-2" data-date-from="'.$startOfWeek->format('Y-m-d\TH:i:sP').'" data-date-to="'.$endOfWeek->format('Y-m-d\TH:i:sP').'">• '.$startOfWeek->format('M j').' - '.$endOfWeek->format('M j').'</span>';

        return ['units' => $days, 'views' => array_values($viewsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of reward views for each day of a given month or the current month if no month is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of views for each day of the month, the days of the month,
     *               and the label containing the name of the month and the year.
     */
    public function rewardViewsMonth(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Determine the number of days in the month.
        $daysInMonth = $date->daysInMonth;

        // Initialize views for all days of the month.
        $viewsPerDay = array_fill_keys(range(1, $daysInMonth), 0);

        // Fetch analytics for the card views in the given month.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%d", created_at)' : 'DAY(created_at)';

        // $analytics = Analytic::whereIn('event', ['card_view', 'reward_view'])
        $analytics = Analytic::where('event', 'reward_view')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfMonth()->toDateTimeString(), $date->endOfMonth()->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as views')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $viewsPerDay[intval($day)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define days of the month.
        $days = array_map(function ($day) {
            return str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }, array_keys($viewsPerDay));

        // Define the label for the month and year.
        $label = mb_ucfirst($date->translatedFormat('F')).' '.$date->translatedFormat('Y');

        return ['units' => $days, 'views' => array_values($viewsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of reward views for each month of a given year or the current year if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of views for each month of the year, the months of the year,
     *               and the label containing the year.
     */
    public function rewardViewsYear(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all months of the year.
        $viewsPerMonth = array_fill_keys(range(1, 12), 0);

        // Fetch analytics for the card views in the given year.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        // $analytics = Analytic::whereIn('event', ['card_view', 'reward_view'])
        $analytics = Analytic::where('event', 'reward_view')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfYear()->toDateTimeString(), $date->endOfYear()->toDateTimeString()])
            ->selectRaw($dateFunction.' as month, COUNT(*) as views')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $month => $analytic) {
            $viewsPerMonth[intval($month)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define months of the year.
        $months = array_map(function ($month) use ($date) {
            return $date->copy()->day(1)->month($month)->translatedFormat('F');
        }, array_keys($viewsPerMonth));

        // Define the label for the year.
        $label = $date->format('Y');

        return ['units' => $months, 'views' => array_values($viewsPerMonth), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the stamp card views for each hour of a given day or the current day if no day is given.
     *
     * @param  string  $stampCardId  The stamp card id.
     * @param  string|null  $day  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the views for each hour of the day, the hours of the day, and a label with the current day.
     */
    public function stampCardViewsDay(string $stampCardId, ?string $day = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no day is given, default to today.
        $day = $day ? Carbon::createFromFormat('Y-m-d', $day, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all hours of the day, from 0 to 23.
        $viewsPerHour = array_fill_keys(range(0, 23), 0);

        // Fetch analytics for the stamp card views on the given day.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection == 'sqlite' ? 'strftime("%H", created_at)' : 'DATE_FORMAT(created_at, "%H")';

        $analytics = Analytic::where('event', 'stamp_card_view')
            ->where('stamp_card_id', $stampCardId)
            ->whereBetween('created_at', [$day->startOfDay()->toDateTimeString(), $day->endOfDay()->toDateTimeString()])
            ->selectRaw($dateFunction.' as hour, COUNT(*) as views')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $hour => $analytic) {
            $viewsPerHour[intval($hour)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define the hours of the day.
        $hours = array_map(function ($hour) use ($day) {
            return $day->copy()->hour($hour)->minute(0)->second(0)->translatedFormat('g A');
        }, array_keys($viewsPerHour));

        // Define the label for the day.
        $label = $day->translatedFormat('l, M j');

        return ['units' => $hours, 'views' => array_values($viewsPerHour), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the stamp card views for each day of a given week or the current week if no date is given.
     *
     * @param  string  $stampCardId  The stamp card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the views for each day of the week, the days of the week, and a label with the current week.
     */
    public function stampCardViewsWeek(string $stampCardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all days of the week.
        $viewsPerDay = array_fill_keys(range(0, 6), 0);

        // Fetch analytics for the stamp card views in the given week.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%w", created_at)' : 'DAYOFWEEK(created_at) - 1';

        $analytics = Analytic::where('event', 'stamp_card_view')
            ->where('stamp_card_id', $stampCardId)
            ->whereBetween('created_at', [$date->copy()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(), $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as views')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            // Both SQLite and MySQL now return 0 for Sunday
            $viewsPerDay[intval($day)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define the days of the week
        $days = array_map(function ($dayOffset) use ($date) {
            return $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays($dayOffset)->translatedFormat('D');
        }, array_keys($viewsPerDay));

        // Define the label for the week.
        $startOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6);
        $label = trans('common.week').' '.$date->weekOfYear.', '.$date->year.' <span class="format-date-range text-sm ml-2" data-date-from="'.$startOfWeek->format('Y-m-d\TH:i:sP').'" data-date-to="'.$endOfWeek->format('Y-m-d\TH:i:sP').'">• '.$startOfWeek->format('M j').' - '.$endOfWeek->format('M j').'</span>';

        return ['units' => $days, 'views' => array_values($viewsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the stamp card views for each day of a given month or the current month if no date is given.
     *
     * @param  string  $stampCardId  The stamp card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the views for each day of the month, the days of the month, and a label with the current month.
     */
    public function stampCardViewsMonth(string $stampCardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Determine the number of days in the month.
        $daysInMonth = $date->daysInMonth;

        // Initialize views for all days of the month.
        $viewsPerDay = array_fill_keys(range(1, $daysInMonth), 0);

        // Fetch analytics for the stamp card views in the given month.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%d", created_at)' : 'DAY(created_at)';

        $analytics = Analytic::where('event', 'stamp_card_view')
            ->where('stamp_card_id', $stampCardId)
            ->whereBetween('created_at', [$date->startOfMonth()->toDateTimeString(), $date->endOfMonth()->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as views')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $viewsPerDay[intval($day)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define the days of the month.
        $days = array_keys($viewsPerDay);

        // Define the label for the month.
        $label = $date->translatedFormat('F Y');

        return ['units' => $days, 'views' => array_values($viewsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the stamp card views for each month of a given year or the current year if no date is given.
     *
     * @param  string  $stampCardId  The stamp card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the views for each month of the year, the months of the year, and a label with the current year.
     */
    public function stampCardViewsYear(string $stampCardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all months of the year.
        $viewsPerMonth = array_fill_keys(range(1, 12), 0);

        // Fetch analytics for the stamp card views in the given year.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $analytics = Analytic::where('event', 'stamp_card_view')
            ->where('stamp_card_id', $stampCardId)
            ->whereBetween('created_at', [$date->startOfYear()->toDateTimeString(), $date->endOfYear()->toDateTimeString()])
            ->selectRaw($dateFunction.' as month, COUNT(*) as views')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $month => $analytic) {
            $viewsPerMonth[intval($month)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define months of the year.
        $months = array_map(function ($month) use ($date) {
            return $date->copy()->day(1)->month($month)->translatedFormat('F');
        }, array_keys($viewsPerMonth));

        // Define the label for the year.
        $label = $date->format('Y');

        return ['units' => $months, 'views' => array_values($viewsPerMonth), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the total points issued for each hour of a given day or the current day if no day is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $day  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the total points for each hour of the day, the hours of the day, and a label with the current day.
     */
    public function pointsIssuedDay(string $cardId, ?string $day = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no day is given, default to today.
        $day = $day ? Carbon::createFromFormat('Y-m-d', $day, $timeZone) : Carbon::now($timeZone);

        // Initialize points for all hours of the day, from 0 to 23.
        $pointsPerHour = array_fill_keys(range(0, 23), 0);

        // Fetch analytics for the points issued on the given day.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%H", created_at)' : 'HOUR(created_at)';

        $analytics = Analytic::where('event', 'issue_points')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$day->startOfDay()->toDateTimeString(), $day->endOfDay()->toDateTimeString()])
            ->selectRaw($dateFunction.' as hour, SUM(points) as points')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        // Replace the initialized points with actual values.
        $total = 0;
        foreach ($analytics as $hour => $analytic) {
            $utcTime = Carbon::createFromFormat('H:i', $hour.':00', 'UTC');
            $userTime = $utcTime->setTimezone($timeZone);
            $userHour = $userTime->format('G');
            $pointsPerHour[$userHour] = $analytic->points;
            $total += $analytic->points;
        }

        // Get the hours of the day and convert them to two digit strings.
        $hours = array_map(function ($hour) {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
        }, array_keys($pointsPerHour));

        // Create a label for the current day.
        $label = '<span class="format-date" data-date-format="xl">'.$day->format('Y-m-d\TH:i:sP').'</span>';

        return ['units' => $hours, 'points' => array_values($pointsPerHour), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the total points issued for each day of a given week or the current week if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the total points for each day of the week, the days of the week,
     *               and the label for the week and its range.
     */
    public function pointsIssuedWeek(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize points for all days of the week.
        $pointsPerDay = array_fill_keys(range(0, 6), 0);

        // Fetch analytics for the points issued in the given week.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%w", created_at)' : 'DAYOFWEEK(created_at) - 1';

        $analytics = Analytic::where('event', 'issue_points')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->copy()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(), $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, SUM(points) as points') // SUM(points) - COUNT(*)
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized points with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $day = intval($day);
            $pointsPerDay[$day] = $analytic->points;
            $total += $analytic->points;
        }

        // Define days of the week.
        $days = array_map(function ($dayIndex) use ($date) {
            return $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays((int) $dayIndex)->translatedFormat('D');
        }, range(0, 6));

        // Define the label for the week and its range.
        $startOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6);
        $label = trans('common.week').' '.$date->weekOfYear.', '.$date->year.' <span class="format-date-range text-sm ml-2" data-date-from="'.$startOfWeek->format('Y-m-d\TH:i:sP').'" data-date-to="'.$endOfWeek->format('Y-m-d\TH:i:sP').'">• '.$startOfWeek->format('M j').' - '.$endOfWeek->format('M j').'</span>';

        return ['units' => $days, 'points' => array_values($pointsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the total points issued for each day of a given month or the current month if no month is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the total points for each day of the month, the days of the month,
     *               and the label containing the name of the month and the year.
     */
    public function pointsIssuedMonth(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Determine the number of days in the month.
        $daysInMonth = $date->daysInMonth;

        // Initialize points for all days of the month.
        $pointsPerDay = array_fill_keys(range(1, $daysInMonth), 0);

        // Fetch analytics for the points issued in the given month.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%d", created_at)' : 'DAY(created_at)';

        $analytics = Analytic::where('event', 'issue_points')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfMonth()->toDateTimeString(), $date->endOfMonth()->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, SUM(points) as points')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized points with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $pointsPerDay[intval($day)] = $analytic->points;
            $total += $analytic->points;
        }

        // Define days of the month.
        $days = array_map(function ($day) {
            return str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }, array_keys($pointsPerDay));

        // Define the label for the month and year.
        $label = mb_ucfirst($date->translatedFormat('F')).' '.$date->translatedFormat('Y');

        return ['units' => $days, 'points' => array_values($pointsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the total points issued for each month of a given year or the current year if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the total points for each month of the year, the months of the year,
     *               and the label containing the year.
     */
    public function pointsIssuedYear(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize points for all months of the year.
        $pointsPerMonth = array_fill_keys(range(1, 12), 0);

        // Fetch analytics for the points issued in the given year.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $analytics = Analytic::where('event', 'issue_points')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfYear()->toDateTimeString(), $date->endOfYear()->toDateTimeString()])
            ->selectRaw($dateFunction.' as month, SUM(points) as points')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Replace the initialized points with actual values.
        $total = 0;
        foreach ($analytics as $month => $analytic) {
            $pointsPerMonth[intval($month)] = $analytic->points;
            $total += $analytic->points;
        }

        // Define months of the year.
        $months = array_map(function ($month) use ($date) {
            return $date->copy()->day(1)->month($month)->translatedFormat('F');
        }, array_keys($pointsPerMonth));

        // Define the label for the year.
        $label = $date->format('Y');

        return ['units' => $months, 'points' => array_values($pointsPerMonth), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of redeemed points for each hour of a given day or the current day if no day is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $day  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of redeemed points for each hour of the day, the hours of the day, and a label with the current day.
     */
    public function pointsRedeemedDay(string $cardId, ?string $day = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no day is given, default to today.
        $day = $day ? Carbon::createFromFormat('Y-m-d', $day, $timeZone) : Carbon::now($timeZone);

        // Initialize points for all hours of the day, from 0 to 23.
        $pointsPerHour = array_fill_keys(range(0, 23), 0);

        // Fetch analytics for the claimed points on the given day.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%H", created_at)' : 'HOUR(created_at)';

        $analytics = Analytic::where('event', 'claim_reward')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$day->startOfDay()->toDateTimeString(), $day->endOfDay()->toDateTimeString()])
            ->selectRaw($dateFunction.' as hour, SUM(points) as points')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        // Replace the initialized points with actual values.
        $total = 0;
        foreach ($analytics as $hour => $analytic) {
            $utcTime = Carbon::createFromFormat('H:i', $hour.':00', 'UTC');
            $userTime = $utcTime->setTimezone($timeZone);
            $userHour = $userTime->format('G');
            $pointsPerHour[$userHour] = $analytic->points;
            $total += $analytic->points;
        }

        // Get the hours of the day and convert them to two digit strings.
        $hours = array_map(function ($hour) {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
        }, array_keys($pointsPerHour));

        // Create a label for the current day.
        $label = '<span class="format-date" data-date-format="xl">'.$day->format('Y-m-d\TH:i:sP').'</span>';

        return ['units' => $hours, 'points' => array_values($pointsPerHour), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of redeemed points for each day of a given week or the current week if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of redeemed points for each day of the week, the days of the week,
     *               and the label for the week and its range.
     */
    public function pointsRedeemedWeek(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize points for all days of the week.
        $pointsPerDay = array_fill_keys(range(0, 6), 0);

        // Fetch analytics for the claimed points in the given week.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        // +1 to make it consistent with SQLite's strftime %w, which treats Sunday as 0.
        $dateFunction = $connection === 'sqlite' ? 'strftime("%w", created_at)' : 'DAYOFWEEK(created_at) - 1';

        $analytics = Analytic::where('event', 'claim_reward')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->copy()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(), $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, SUM(points) as points')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized points with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $day = intval($day);
            $pointsPerDay[$day] = $analytic->points;
            $total += $analytic->points;
        }

        // Define days of the week.
        $days = array_map(function ($dayIndex) use ($date) {
            return $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays((int) $dayIndex)->translatedFormat('D');
        }, range(0, 6));

        // Define the label for the week and its range.
        $startOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6);
        $label = trans('common.week').' '.$date->weekOfYear.', '.$date->year.' <span class="format-date-range text-sm ml-2" data-date-from="'.$startOfWeek->format('Y-m-d\TH:i:sP').'" data-date-to="'.$endOfWeek->format('Y-m-d\TH:i:sP').'">• '.$startOfWeek->format('M j').' - '.$endOfWeek->format('M j').'</span>';

        return ['units' => $days, 'points' => array_values($pointsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of redeemed points for each day of a given month or the current month if no month is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of points for each day of the month, the days of the month,
     *               and the label containing the name of the month and the year.
     */
    public function pointsRedeemedMonth(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Determine the number of days in the month.
        $daysInMonth = $date->daysInMonth;

        // Initialize points for all days of the month.
        $pointsPerDay = array_fill_keys(range(1, $daysInMonth), 0);

        // Fetch analytics for the claimed points in the given month.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%d", created_at)' : 'DATE_FORMAT(created_at, "%d")';

        $analytics = Analytic::where('event', 'claim_reward')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfMonth()->toDateTimeString(), $date->endOfMonth()->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, SUM(points) as points')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized points with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $pointsPerDay[intval($day)] = $analytic->points;
            $total += $analytic->points;
        }

        // Define days of the month.
        $days = array_map(function ($day) {
            return str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }, array_keys($pointsPerDay));

        // Define the label for the month and year.
        $label = mb_ucfirst($date->translatedFormat('F')).' '.$date->translatedFormat('Y');

        return ['units' => $days, 'points' => array_values($pointsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of redeemed points for each month of a given year or the current year if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of points for each month of the year, the months of the year,
     *               and the label containing the year.
     */
    public function pointsRedeemedYear(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize points for all months of the year.
        $pointsPerMonth = array_fill_keys(range(1, 12), 0);

        // Fetch analytics for the claimed points in the given year.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $analytics = Analytic::where('event', 'claim_reward')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfYear()->toDateTimeString(), $date->endOfYear()->toDateTimeString()])
            ->selectRaw($dateFunction.' as month, SUM(points) as points')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Replace the initialized points with actual values.
        $total = 0;
        foreach ($analytics as $month => $analytic) {
            $pointsPerMonth[intval($month)] = $analytic->points;
            $total += $analytic->points;
        }

        // Define months of the year.
        $months = array_map(function ($month) use ($date) {
            return $date->copy()->day(1)->month($month)->translatedFormat('F');
        }, array_keys($pointsPerMonth));

        // Define the label for the year.
        $label = $date->format('Y');

        return ['units' => $months, 'points' => array_values($pointsPerMonth), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of claimed rewards for each hour of a given day or the current day if no day is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $day  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of claimed rewards for each hour of the day, the hours of the day, and a label with the current day.
     */
    public function rewardsClaimedDay(string $cardId, ?string $day = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no day is given, default to today.
        $day = $day ? Carbon::createFromFormat('Y-m-d', $day, $timeZone) : Carbon::now($timeZone);

        // Initialize rewards for all hours of the day, from 0 to 23.
        $rewardsPerHour = array_fill_keys(range(0, 23), 0);

        // Fetch analytics for the claimed rewards on the given day.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%H", created_at)' : 'HOUR(created_at)';

        $analytics = Analytic::where('event', 'claim_reward')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$day->startOfDay()->toDateTimeString(), $day->endOfDay()->toDateTimeString()])
            ->selectRaw($dateFunction.' as hour, COUNT(*) as rewards')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        // Replace the initialized rewards with actual values.
        $total = 0;
        foreach ($analytics as $hour => $analytic) {
            $utcTime = Carbon::createFromFormat('H:i', $hour.':00', 'UTC');
            $userTime = $utcTime->setTimezone($timeZone);
            $userHour = $userTime->format('G');
            $rewardsPerHour[$userHour] = $analytic->rewards;
            $total += $analytic->rewards;
        }

        // Get the hours of the day and convert them to two digit strings.
        $hours = array_map(function ($hour) {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
        }, array_keys($rewardsPerHour));

        // Create a label for the current day.
        $label = '<span class="format-date" data-date-format="xl">'.$day->format('Y-m-d\TH:i:sP').'</span>';

        return ['units' => $hours, 'rewards' => array_values($rewardsPerHour), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of claimed rewards for each day of a given week or the current week if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of claimed rewards for each day of the week, the days of the week,
     *               and the label for the week and its range.
     */
    public function rewardsClaimedWeek(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize rewards for all days of the week.
        $rewardsPerDay = array_fill_keys(range(0, 6), 0);

        // Fetch analytics for the claimed rewards in the given week.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        // +1 to make it consistent with SQLite's strftime %w, which treats Sunday as 0.
        $dateFunction = $connection === 'sqlite' ? 'strftime("%w", created_at)' : 'DAYOFWEEK(created_at) - 1';

        $analytics = Analytic::where('event', 'claim_reward')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->copy()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(), $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as rewards')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized rewards with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $day = intval($day);
            $rewardsPerDay[$day] = $analytic->rewards;
            $total += $analytic->rewards;
        }

        // Define days of the week.
        $days = array_map(function ($dayIndex) use ($date) {
            return $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays((int) $dayIndex)->translatedFormat('D');
        }, range(0, 6));

        // Define the label for the week and its range.
        $startOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6);
        $label = trans('common.week').' '.$date->weekOfYear.', '.$date->year.' <span class="format-date-range text-sm ml-2" data-date-from="'.$startOfWeek->format('Y-m-d\TH:i:sP').'" data-date-to="'.$endOfWeek->format('Y-m-d\TH:i:sP').'">• '.$startOfWeek->format('M j').' - '.$endOfWeek->format('M j').'</span>';

        return ['units' => $days, 'rewards' => array_values($rewardsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of claimed rewards for each day of a given month or the current month if no month is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of rewards for each day of the month, the days of the month,
     *               and the label containing the name of the month and the year.
     */
    public function rewardsClaimedMonth(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Determine the number of days in the month.
        $daysInMonth = $date->daysInMonth;

        // Initialize rewards for all days of the month.
        $rewardsPerDay = array_fill_keys(range(1, $daysInMonth), 0);

        // Fetch analytics for the claimed rewards in the given month.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%d", created_at)' : 'DATE_FORMAT(created_at, "%d")';

        $analytics = Analytic::where('event', 'claim_reward')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfMonth()->toDateTimeString(), $date->endOfMonth()->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as rewards')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized rewards with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $rewardsPerDay[intval($day)] = $analytic->rewards;
            $total += $analytic->rewards;
        }

        // Define days of the month.
        $days = array_map(function ($day) {
            return str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }, array_keys($rewardsPerDay));

        // Define the label for the month and year.
        $label = mb_ucfirst($date->translatedFormat('F')).' '.$date->translatedFormat('Y');

        return ['units' => $days, 'rewards' => array_values($rewardsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of claimed rewards for each month of a given year or the current year if no date is given.
     *
     * @param  int  $cardId  The card id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the number of rewards for each month of the year, the months of the year,
     *               and the label containing the year.
     */
    public function rewardsClaimedYear(string $cardId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize rewards for all months of the year.
        $rewardsPerMonth = array_fill_keys(range(1, 12), 0);

        // Fetch analytics for the claimed rewards in the given year.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $analytics = Analytic::where('event', 'claim_reward')
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [$date->startOfYear()->toDateTimeString(), $date->endOfYear()->toDateTimeString()])
            ->selectRaw($dateFunction.' as month, COUNT(*) as rewards')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Replace the initialized rewards with actual values.
        $total = 0;
        foreach ($analytics as $month => $analytic) {
            $rewardsPerMonth[intval($month)] = $analytic->rewards;
            $total += $analytic->rewards;
        }

        // Define months of the year.
        $months = array_map(function ($month) use ($date) {
            return $date->copy()->day(1)->month($month)->translatedFormat('F');
        }, array_keys($rewardsPerMonth));

        // Define the label for the year.
        $label = $date->format('Y');

        return ['units' => $months, 'rewards' => array_values($rewardsPerMonth), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the number of points requested/transferred through point requests for each hour of a day.
     *
     * This method analyzes point request transactions (both sent and received) and groups them by hour,
     * providing hourly totals for a specific day. Points are always returned as positive values,
     * even for sent transactions which are stored as negative values in the database.
     *
     * @param  string  $cardId  The card identifier to analyze
     * @param  string  $type  Either 'received' or 'sent' to specify the direction of point transfers
     * @param  string|null  $day  The date to analyze in Y-m-d format. If null, defaults to current day
     * @return array An array containing:
     *               - units: Array of hour labels (00:00 through 23:00)
     *               - points: Array of point totals for each hour
     *               - label: Formatted date label for display
     *               - total: Total points transferred for the day
     */
    public function pointRequestsDay(string $cardId, string $type = 'received', ?string $day = null): array
    {
        // Get the user's timezone for proper date handling
        $timeZone = $this->getUserTimeZone();

        // Parse the provided date or use current date
        $day = $day ? Carbon::createFromFormat('Y-m-d', $day, $timeZone) : Carbon::now($timeZone);

        // Initialize an array for all 24 hours of the day with zero values
        $pointsPerHour = array_fill_keys(range(0, 23), 0);

        // Determine the appropriate date function based on database type
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%H", created_at)' : 'HOUR(created_at)';

        // Set event type based on whether we're tracking received or sent points
        $event = $type === 'received' ? 'points_request_received' : 'points_request_sent';

        // Query the analytics data for the specified day
        $analytics = Analytic::where('event', $event)
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [
                $day->startOfDay()->toDateTimeString(),
                $day->endOfDay()->toDateTimeString(),
            ])
            ->selectRaw($dateFunction.' as hour, SUM(points) as points')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        // Calculate totals and handle timezone conversions
        $total = 0;
        foreach ($analytics as $hour => $analytic) {
            // Convert UTC hour to user's timezone
            $utcTime = Carbon::createFromFormat('H:i', $hour.':00', 'UTC');
            $userTime = $utcTime->setTimezone($timeZone);
            $userHour = $userTime->format('G');

            // Store absolute value since sent points are negative in database
            $pointsPerHour[$userHour] = abs($analytic->points);
            $total += abs($analytic->points);
        }

        // Generate hour labels in 24-hour format (00:00 through 23:00)
        $hours = array_map(function ($hour) {
            return str_pad((string) $hour, 2, '0', STR_PAD_LEFT).':00';
        }, array_keys($pointsPerHour));

        // Create formatted date label for display
        $label = '<span class="format-date" data-date-format="xl">'.
            $day->format('Y-m-d\TH:i:sP').'</span>';

        return [
            'units' => $hours,
            'points' => array_values($pointsPerHour),
            'label' => $label,
            'total' => $total,
        ];
    }

    /**
     * Get the number of points requested/transferred through point requests for each day of a week.
     *
     * This method analyzes point request transactions and groups them by day of the week,
     * providing daily totals for a specific week.
     *
     * @param  string  $cardId  The card identifier to analyze
     * @param  string  $type  Either 'received' or 'sent' to specify the direction of point transfers
     * @param  string|null  $date  Any date within the week to analyze (Y-m-d format). If null, uses current week
     * @return array An array containing:
     *               - units: Array of day labels (Mon through Sun)
     *               - points: Array of point totals for each day
     *               - label: Formatted week label for display
     *               - total: Total points transferred for the week
     */
    public function pointRequestsWeek(string $cardId, string $type = 'received', ?string $date = null): array
    {
        $timeZone = $this->getUserTimeZone();
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize array for all days of the week (0 = Monday through 6 = Sunday)
        $pointsPerDay = array_fill_keys(range(0, 6), 0);

        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%w", created_at)' : 'DAYOFWEEK(created_at) - 1';

        $event = $type === 'received' ? 'points_request_received' : 'points_request_sent';

        $analytics = Analytic::where('event', $event)
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [
                $date->copy()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(),
                $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateTimeString(),
            ])
            ->selectRaw($dateFunction.' as day, SUM(points) as points')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $day = intval($day);
            // Adjust for SQLite's different day numbering (Sunday = 0)
            $pointsPerDay[$day] = abs($analytic->points);
            $total += abs($analytic->points);
        }

        // Generate day labels using translated short day names
        $days = array_map(function ($dayIndex) use ($date) {
            return $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays((int) $dayIndex)->translatedFormat('D');
        }, range(0, 6));

        // Create formatted week label showing date range
        $label = trans('common.week').' '.$date->weekOfYear.', '.$date->year.
            ' <span class="text-sm ml-2"><span class="format-date" data-date-format="md">'.
            $date->copy()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d\TH:i:sP').
            '</span> - <span class="format-date" data-date-format="md">'.
            $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->format('Y-m-d\TH:i:sP').'</span></span>';

        return [
            'units' => $days,
            'points' => array_values($pointsPerDay),
            'label' => $label,
            'total' => $total,
        ];
    }

    /**
     * Get the number of points requested/transferred through point requests for each day of a month.
     *
     * This method analyzes point request transactions and groups them by day of the month,
     * providing daily totals for a specific month.
     *
     * @param  string  $cardId  The card identifier to analyze
     * @param  string  $type  Either 'received' or 'sent' to specify the direction of point transfers
     * @param  string|null  $date  Any date within the month to analyze (Y-m-d format). If null, uses current month
     * @return array An array containing:
     *               - units: Array of day labels (01 through 31)
     *               - points: Array of point totals for each day
     *               - label: Formatted month and year label for display
     *               - total: Total points transferred for the month
     */
    public function pointRequestsMonth(string $cardId, string $type = 'received', ?string $date = null): array
    {
        $timeZone = $this->getUserTimeZone();
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Get the number of days in the month and initialize the points array
        $daysInMonth = $date->daysInMonth;
        $pointsPerDay = array_fill_keys(range(1, $daysInMonth), 0);

        // Determine the appropriate date function based on database type
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%d", created_at)' : 'DAY(created_at)';

        $event = $type === 'received' ? 'points_request_received' : 'points_request_sent';

        // Query analytics for the month
        $analytics = Analytic::where('event', $event)
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [
                $date->startOfMonth()->toDateTimeString(),
                $date->endOfMonth()->toDateTimeString(),
            ])
            ->selectRaw($dateFunction.' as day, SUM(points) as points')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Calculate totals
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $pointsPerDay[intval($day)] = abs($analytic->points);
            $total += abs($analytic->points);
        }

        // Generate day labels (01 through last day of month)
        $days = array_map(function ($day) {
            return str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        }, array_keys($pointsPerDay));

        // Create formatted month and year label
        $label = mb_ucfirst($date->translatedFormat('F')).' '.$date->translatedFormat('Y');

        return [
            'units' => $days,
            'points' => array_values($pointsPerDay),
            'label' => $label,
            'total' => $total,
        ];
    }

    /**
     * Get the number of points requested/transferred through point requests for each month of a year.
     *
     * This method analyzes point request transactions and groups them by month,
     * providing monthly totals for a specific year.
     *
     * @param  string  $cardId  The card identifier to analyze
     * @param  string  $type  Either 'received' or 'sent' to specify the direction of point transfers
     * @param  string|null  $date  Any date within the year to analyze (Y-m-d format). If null, uses current year
     * @return array An array containing:
     *               - units: Array of month names (January through December)
     *               - points: Array of point totals for each month
     *               - label: Formatted year label for display
     *               - total: Total points transferred for the year
     */
    public function pointRequestsYear(string $cardId, string $type = 'received', ?string $date = null): array
    {
        $timeZone = $this->getUserTimeZone();
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize array for all months (1 through 12)
        $pointsPerMonth = array_fill_keys(range(1, 12), 0);

        // Determine the appropriate date function based on database type
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $event = $type === 'received' ? 'points_request_received' : 'points_request_sent';

        // Query analytics for the year
        $analytics = Analytic::where('event', $event)
            ->where('card_id', $cardId)
            ->whereBetween('created_at', [
                $date->startOfYear()->toDateTimeString(),
                $date->endOfYear()->toDateTimeString(),
            ])
            ->selectRaw($dateFunction.' as month, SUM(points) as points')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Calculate totals
        $total = 0;
        foreach ($analytics as $month => $analytic) {
            $pointsPerMonth[intval($month)] = abs($analytic->points);
            $total += abs($analytic->points);
        }

        // Generate month labels using translated month names
        $months = array_map(function ($month) use ($date) {
            return $date->copy()->day(1)->month($month)->translatedFormat('F');
        }, array_keys($pointsPerMonth));

        // Create year label
        $label = $date->format('Y');

        return [
            'units' => $months,
            'points' => array_values($pointsPerMonth),
            'label' => $label,
            'total' => $total,
        ];
    }

    /**
     * Get the voucher views for each hour of a given day or the current day if no day is given.
     *
     * @param  string  $voucherId  The voucher id.
     * @param  string|null  $day  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the views for each hour of the day, the hours of the day, and a label with the current day.
     */
    public function voucherViewsDay(string $voucherId, ?string $day = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no day is given, default to today.
        $day = $day ? Carbon::createFromFormat('Y-m-d', $day, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all hours of the day, from 0 to 23.
        $viewsPerHour = array_fill_keys(range(0, 23), 0);

        // Fetch analytics for the voucher views on the given day.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection == 'sqlite' ? 'strftime("%H", created_at)' : 'DATE_FORMAT(created_at, "%H")';

        $analytics = Analytic::where('event', 'voucher_view')
            ->where('voucher_id', $voucherId)
            ->whereBetween('created_at', [$day->startOfDay()->toDateTimeString(), $day->endOfDay()->toDateTimeString()])
            ->selectRaw($dateFunction.' as hour, COUNT(*) as views')
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $hour => $analytic) {
            $viewsPerHour[intval($hour)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define the hours of the day.
        $hours = array_map(function ($hour) use ($day) {
            return $day->copy()->hour($hour)->minute(0)->second(0)->translatedFormat('g A');
        }, array_keys($viewsPerHour));

        // Define the label for the day.
        $label = $day->translatedFormat('l, M j');

        return ['units' => $hours, 'views' => array_values($viewsPerHour), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the voucher views for each day of a given week or the current week if no date is given.
     *
     * @param  string  $voucherId  The voucher id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the views for each day of the week, the days of the week, and a label with the current week.
     */
    public function voucherViewsWeek(string $voucherId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all days of the week.
        $viewsPerDay = array_fill_keys(range(0, 6), 0);

        // Fetch analytics for the voucher views in the given week.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%w", created_at)' : 'DAYOFWEEK(created_at) - 1';

        $analytics = Analytic::where('event', 'voucher_view')
            ->where('voucher_id', $voucherId)
            ->whereBetween('created_at', [$date->copy()->startOfWeek(Carbon::SUNDAY)->toDateTimeString(), $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6)->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as views')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            // Both SQLite and MySQL now return 0 for Sunday
            $viewsPerDay[intval($day)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define the days of the week
        $days = array_map(function ($dayOffset) use ($date) {
            return $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays($dayOffset)->translatedFormat('D');
        }, array_keys($viewsPerDay));

        // Define the label for the week.
        $startOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfWeek = $date->copy()->startOfWeek(Carbon::SUNDAY)->addDays(6);
        $label = trans('common.week').' '.$date->weekOfYear.', '.$date->year.' <span class="format-date-range text-sm ml-2" data-date-from="'.$startOfWeek->format('Y-m-d\TH:i:sP').'" data-date-to="'.$endOfWeek->format('Y-m-d\TH:i:sP').'">• '.$startOfWeek->format('M j').' - '.$endOfWeek->format('M j').'</span>';

        return ['units' => $days, 'views' => array_values($viewsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the voucher views for each day of a given month or the current month if no date is given.
     *
     * @param  string  $voucherId  The voucher id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the views for each day of the month, the days of the month, and a label with the current month.
     */
    public function voucherViewsMonth(string $voucherId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Determine the number of days in the month.
        $daysInMonth = $date->daysInMonth;

        // Initialize views for all days of the month.
        $viewsPerDay = array_fill_keys(range(1, $daysInMonth), 0);

        // Fetch analytics for the voucher views in the given month.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%d", created_at)' : 'DAY(created_at)';

        $analytics = Analytic::where('event', 'voucher_view')
            ->where('voucher_id', $voucherId)
            ->whereBetween('created_at', [$date->startOfMonth()->toDateTimeString(), $date->endOfMonth()->toDateTimeString()])
            ->selectRaw($dateFunction.' as day, COUNT(*) as views')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $day => $analytic) {
            $viewsPerDay[intval($day)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define the days of the month.
        $days = array_keys($viewsPerDay);

        // Define the label for the month.
        $label = $date->translatedFormat('F Y');

        return ['units' => $days, 'views' => array_values($viewsPerDay), 'label' => $label, 'total' => $total];
    }

    /**
     * Get the voucher views for each month of a given year or the current year if no date is given.
     *
     * @param  string  $voucherId  The voucher id.
     * @param  string|null  $date  The date (Y-m-d). If null, defaults to today's date.
     * @return array An array containing the views for each month of the year, the months of the year, and a label with the current year.
     */
    public function voucherViewsYear(string $voucherId, ?string $date = null): array
    {
        // Get the user's time zone
        $timeZone = $this->getUserTimeZone();

        // If no date is given, default to today.
        $date = $date ? Carbon::createFromFormat('Y-m-d', $date, $timeZone) : Carbon::now($timeZone);

        // Initialize views for all months of the year.
        $viewsPerMonth = array_fill_keys(range(1, 12), 0);

        // Fetch analytics for the voucher views in the given year.
        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $dateFunction = $connection === 'sqlite' ? 'strftime("%m", created_at)' : 'MONTH(created_at)';

        $analytics = Analytic::where('event', 'voucher_view')
            ->where('voucher_id', $voucherId)
            ->whereBetween('created_at', [$date->startOfYear()->toDateTimeString(), $date->endOfYear()->toDateTimeString()])
            ->selectRaw($dateFunction.' as month, COUNT(*) as views')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Replace the initialized views with actual values.
        $total = 0;
        foreach ($analytics as $month => $analytic) {
            $viewsPerMonth[intval($month)] = $analytic->views;
            $total += $analytic->views;
        }

        // Define months of the year.
        $months = array_map(function ($month) use ($date) {
            return $date->copy()->day(1)->month($month)->translatedFormat('F');
        }, array_keys($viewsPerMonth));

        // Define the label for the year.
        $label = $date->format('Y');

        return ['units' => $months, 'views' => array_values($viewsPerMonth), 'label' => $label, 'total' => $total];
    }
}
