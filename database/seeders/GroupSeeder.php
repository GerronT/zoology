<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Group::truncate();

        $groups = [
            ['name' => 'Eukaryota', 'description' => '', 'classification_id' => 1, 'level_id' => 5],
            ['name' => 'Animalia', 'description' => '', 'classification_id' => 2, 'level_id' => 5],
            ['name' => 'Chordata', 'description' => '', 'classification_id' => 3, 'level_id' => 5],
            ['name' => 'Mammalia', 'description' => '', 'classification_id' => 4, 'level_id' => 5],
        ];

        $group_parent_id = null;
        foreach ($groups as $group) {
            $insertedOrFetchedGroup = Group::firstOrCreate(
                ['name' => $group['name'], 'classification_id' => $group['classification_id'], 'level_id' => $group['level_id']],
                ['description' => $group['description']]
            );

            if ($group_parent_id && $insertedOrFetchedGroup->wasRecentlyCreated) {
                $insertedOrFetchedGroup->parent_group_id = $group_parent_id;
                $insertedOrFetchedGroup->save();
            }

            $group_parent_id = $insertedOrFetchedGroup->id;
        }
    }
}
