<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Level::truncate();

        $levels = [
            ['name' => 'Magn', 'alt_name' => 'Mega'],
            ['name' => 'Super', 'alt_name' => ''],
            ['name' => 'Grand', 'alt_name' => 'Capax'],
            ['name' => 'Mir', 'alt_name' => 'Hyper'],
            ['name' => 'Base', 'alt_name' => ''],
            ['name' => 'Sub', 'alt_name' => ''],
            ['name' => 'Infra', 'alt_name' => ''],
            ['name' => 'Parv', 'alt_name' => ''],
        ];

        foreach ($levels as $level) {
            $latest_level = Level::latest('id')->first();

            $inserted_level = Level::create([
                'name' => $level['name'],
                'alt_name' => $level['alt_name'],
                'preceded_by_id' => $latest_level ? $latest_level->id : null
            ]);

            if ($latest_level) {
                $latest_level->succeeded_by_id = $inserted_level->id;
                $latest_level->save();
            }
        }
    }
}
