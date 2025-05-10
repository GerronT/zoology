<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class ClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Classification::truncate();

        $classifications = [
            ['name' => 'Domain', 'description' => ''],
            ['name' => 'Kingdom', 'description' => ''],
            ['name' => 'Phylum', 'description' => ''],
            ['name' => 'Class', 'description' => ''],
            ['name' => 'Order', 'description' => ''],
            ['name' => 'Family', 'description' => ''],
            ['name' => 'Genus', 'description' => ''],
            ['name' => 'Species', 'description' => ''],
        ];

        foreach ($classifications as $classification) {
            $latest_classification = Classification::latest('id')->first();

            $inserted_classification = Classification::create([
                'name' => $classification['name'],
                'description' => $classification['description'],
                'preceded_by_id' => $latest_classification ? $latest_classification->id : null
            ]);

            if ($latest_classification) {
                $latest_classification->succeeded_by_id = $inserted_classification->id;
                $latest_classification->save();
            }
        }
    }
}
