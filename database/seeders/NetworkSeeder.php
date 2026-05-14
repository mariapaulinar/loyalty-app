<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Network;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class NetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rootAdminId = Admin::take(1)->first()->id;
        $demoItems = [
            ['name' => 'Fashion'],
            ['name' => 'Travel'],
            ['name' => 'Food'],
            ['name' => 'Sports'],
            ['name' => 'Tech'],
        ];

        Network::create([
            'name' => 'Default',
            'currency' => config('default.currency'),
            'is_active' => true,
            'is_undeletable' => true,
            'is_primary' => true,
            'created_at' => Carbon::now('UTC'),
            'created_by' => $rootAdminId,
        ]);

        if (config('default.app_demo')) {
            foreach ($demoItems as $item) {
                $network = Network::create([
                    'name' => $item['name'],
                    'currency' => config('default.currency'),
                    'is_primary' => false,
                    'is_active' => true,
                    'is_undeletable' => env('APP_IS_UNEDITABLE', true),
                    'is_uneditable' => env('APP_IS_UNEDITABLE', true),
                    'created_at' => Carbon::now('UTC'),
                    'created_by' => Admin::inRandomOrder()->first()->id,
                ]);

                // Attach one or more admin managers
                $managerCount = Admin::where('role', 2)->count();
                if ($managerCount > 2) {
                    $managerCount = $managerCount - 2;
                }
                if ($managerCount > 4) {
                    $managerCount = 4;
                }
                $randomCount = rand(1, $managerCount);
                $managerIds = Admin::where('role', 2)->inRandomOrder()->limit($randomCount)->pluck('id')->toArray();

                // Sync the partner IDs to the network
                $network->admins()->sync($managerIds);
            }

            // Associate network with partners
            $partners = Partner::all();

            foreach ($partners as $partner) {
                // Associate the partner with random network
                $network = Network::inRandomOrder()->first();
                $partner->network()->associate($network);
                $partner->save();
            }
        }
    }
}
