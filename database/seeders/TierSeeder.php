<?php

declare(strict_types=1);

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Purpose:
 * Seeds default tier structures for demo clubs only.
 * Creates Bronze (default), Silver, Gold, and Platinum tiers with
 * appropriate thresholds and multipliers.
 *
 * Note: Tiers are optional for clubs. This seeder is only for demo data.
 * Real clubs should configure tiers manually via the partner dashboard.
 */

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Partner;
use App\Models\Tier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TierSeeder extends Seeder
{
    /**
     * Default tier configurations for demo clubs.
     * Includes translations for: English, Arabic, German, Spanish, French, Italian, Portuguese.
     */
    private const DEFAULT_TIERS = [
        [
            'name' => 'Bronze',
            'display_name' => [
                'en_US' => 'Bronze Member',
                'ar_SA' => 'عضو برونزي',
                'de_DE' => 'Bronze-Mitglied',
                'es_ES' => 'Miembro Bronce',
                'fr_FR' => 'Membre Bronze',
                'it_IT' => 'Membro Bronzo',
                'pt_BR' => 'Membro Bronze',
            ],
            'description' => [
                'en_US' => 'Welcome to our loyalty program!',
                'ar_SA' => 'مرحباً بك في برنامج الولاء!',
                'de_DE' => 'Willkommen in unserem Treueprogramm!',
                'es_ES' => '¡Bienvenido a nuestro programa de fidelidad!',
                'fr_FR' => 'Bienvenue dans notre programme de fidélité !',
                'it_IT' => 'Benvenuto nel nostro programma fedeltà!',
                'pt_BR' => 'Bem-vindo ao nosso programa de fidelidade!',
            ],
            'icon' => '🥉',
            'color' => '#CD7F32',
            'level' => 0,
            'points_threshold' => 0,
            'points_multiplier' => 1.00,
            'is_default' => true,
            'is_undeletable' => true,
        ],
        [
            'name' => 'Silver',
            'display_name' => [
                'en_US' => 'Silver Member',
                'ar_SA' => 'عضو فضي',
                'de_DE' => 'Silber-Mitglied',
                'es_ES' => 'Miembro Plata',
                'fr_FR' => 'Membre Argent',
                'it_IT' => 'Membro Argento',
                'pt_BR' => 'Membro Prata',
            ],
            'description' => [
                'en_US' => 'Earn 1.25x points on every purchase!',
                'ar_SA' => 'اكسب 1.25 ضعف النقاط على كل عملية شراء!',
                'de_DE' => 'Erhalten Sie 1,25x Punkte bei jedem Einkauf!',
                'es_ES' => '¡Gana 1,25x puntos en cada compra!',
                'fr_FR' => 'Gagnez 1,25x points sur chaque achat !',
                'it_IT' => 'Guadagna 1,25x punti su ogni acquisto!',
                'pt_BR' => 'Ganhe 1,25x pontos em cada compra!',
            ],
            'icon' => '🥈',
            'color' => '#64748B',
            'level' => 1,
            'points_threshold' => 1000,
            'points_multiplier' => 1.25,
            'is_default' => false,
            'is_undeletable' => false,
        ],
        [
            'name' => 'Gold',
            'display_name' => [
                'en_US' => 'Gold Member',
                'ar_SA' => 'عضو ذهبي',
                'de_DE' => 'Gold-Mitglied',
                'es_ES' => 'Miembro Oro',
                'fr_FR' => 'Membre Or',
                'it_IT' => 'Membro Oro',
                'pt_BR' => 'Membro Ouro',
            ],
            'description' => [
                'en_US' => 'Earn 1.5x points on every purchase!',
                'ar_SA' => 'اكسب 1.5 ضعف النقاط على كل عملية شراء!',
                'de_DE' => 'Erhalten Sie 1,5x Punkte bei jedem Einkauf!',
                'es_ES' => '¡Gana 1,5x puntos en cada compra!',
                'fr_FR' => 'Gagnez 1,5x points sur chaque achat !',
                'it_IT' => 'Guadagna 1,5x punti su ogni acquisto!',
                'pt_BR' => 'Ganhe 1,5x pontos em cada compra!',
            ],
            'icon' => '🥇',
            'color' => '#FFD700',
            'level' => 2,
            'points_threshold' => 5000,
            'points_multiplier' => 1.50,
            'is_default' => false,
            'is_undeletable' => false,
        ],
        [
            'name' => 'Platinum',
            'display_name' => [
                'en_US' => 'Platinum Member',
                'ar_SA' => 'عضو بلاتيني',
                'de_DE' => 'Platin-Mitglied',
                'es_ES' => 'Miembro Platino',
                'fr_FR' => 'Membre Platine',
                'it_IT' => 'Membro Platino',
                'pt_BR' => 'Membro Platina',
            ],
            'description' => [
                'en_US' => 'Our most valued members! Earn 2x points!',
                'ar_SA' => 'أعضاؤنا الأكثر قيمة! اكسب ضعف النقاط!',
                'de_DE' => 'Unsere wertvollsten Mitglieder! 2x Punkte sammeln!',
                'es_ES' => '¡Nuestros miembros más valiosos! ¡Gana 2x puntos!',
                'fr_FR' => 'Nos membres les plus précieux ! Gagnez 2x points !',
                'it_IT' => 'I nostri membri più preziosi! Guadagna 2x punti!',
                'pt_BR' => 'Nossos membros mais valiosos! Ganhe 2x pontos!',
            ],
            'icon' => '💎',
            'color' => '#8B5CF6',
            'level' => 3,
            'points_threshold' => 15000,
            'points_multiplier' => 2.00,
            'is_default' => false,
            'is_undeletable' => false,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first partner (demo partner)
        $partner = Partner::first();

        if (! $partner) {
            $this->command->warn('No partners found. Skipping tier seeding.');

            return;
        }

        // Get clubs owned by this partner
        $clubs = Club::where('created_by', $partner->id)->get();

        if ($clubs->isEmpty()) {
            $this->command->warn("No clubs found for partner {$partner->name}. Skipping tier seeding.");

            return;
        }

        foreach ($clubs as $club) {
            // Check if club already has tiers
            if ($club->tiers()->exists()) {
                $this->command->info("Skipping club {$club->name} - already has tiers");

                continue;
            }

            $this->command->info("Creating default tiers for club: {$club->name}");
            $this->createDefaultTiers($club, $partner->id);
        }

        $this->command->info('Tier seeding completed!');
    }

    /**
     * Create default tiers for a club.
     *
     * @return array<Tier>
     */
    private function createDefaultTiers(Club $club, ?string $createdBy = null): array
    {
        $createdTiers = [];

        DB::transaction(function () use ($club, $createdBy, &$createdTiers) {
            foreach (self::DEFAULT_TIERS as $tierData) {
                $tier = Tier::create([
                    'club_id' => $club->id,
                    'name' => $tierData['name'],
                    'display_name' => $tierData['display_name'],
                    'description' => $tierData['description'],
                    'icon' => $tierData['icon'],
                    'color' => $tierData['color'],
                    'level' => $tierData['level'],
                    'points_threshold' => $tierData['points_threshold'],
                    'points_multiplier' => $tierData['points_multiplier'],
                    'is_default' => $tierData['is_default'],
                    'is_undeletable' => $tierData['is_undeletable'],
                    'is_active' => true,
                    'created_by' => $createdBy,
                ]);

                $createdTiers[] = $tier;
            }
        });

        return $createdTiers;
    }
}
