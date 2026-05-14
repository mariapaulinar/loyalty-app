<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Seeds demo clubs with business-specific names derived from
 * the partner's business_name. Falls back to 'General' if
 * no business_name is set (non-demo installations).
 */

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = Partner::all();

        foreach ($partners as $partner) {
            // Use the partner's business_name for a realistic club label,
            // falling back to 'General' for partners without a business profile.
            $clubName = $partner->business_name ?: 'General';

            Club::create([
                'name' => $clubName,
                'is_active' => true,
                'is_undeletable' => env('APP_IS_UNEDITABLE', true),
                'is_uneditable' => env('APP_IS_UNEDITABLE', true),
                'created_at' => Carbon::now('UTC'),
                'created_by' => $partner->id,
            ]);
        }
    }
}
