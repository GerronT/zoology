<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Group;
use Illuminate\Database\Seeder;

class AnimalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Animal::truncate();

        $animals = [
            [
                'name' => 'Lion',
                'alt_name' => null,
                'description' => 'The lion (Panthera leo) is a large cat of the genus Panthera, native to Africa and India. It has a muscular, broad-chested body; a short, rounded head; round ears; and a dark, hairy tuft at the tip of its tail. It is sexually dimorphic; adult male lions are larger than females and have a prominent mane. It is a social species, forming groups called prides. A lion\'s pride consists of a few adult males, related females, and cubs. Groups of female lions usually hunt together, preying mostly on medium-sized and large ungulates. The lion is an apex and keystone predator; although some lions scavenge when opportunities occur and have been known to hunt humans, lions typically do not actively seek out and prey on humans.',
                'groupings' => [
                    ['name' => 'Carnivora', 'description' => '', 'classification_id' => 5, 'level_id' => 5, 'parent_group_id' => 4], // 4 - Mammalia
                    ['name' => 'Feliformia', 'description' => '', 'classification_id' => 5, 'level_id' => 6],
                    ['name' => 'Felidae', 'description' => '', 'classification_id' => 6, 'level_id' => 5],
                    ['name' => 'Pantherinae', 'description' => '', 'classification_id' => 6, 'level_id' => 6],
                    ['name' => 'Panthera', 'description' => '', 'classification_id' => 7, 'level_id' => 5],
                    ['name' => 'P.leo', 'description' => '', 'classification_id' => 8, 'level_id' => 5],
                ]
            ],
            [
                'name' => 'Tiger',
                'alt_name' => null,
                'description' => 'The tiger (Panthera tigris) is a large cat and a member of the genus Panthera native to Asia. It has a powerful, muscular body with a large head and paws, a long tail and orange fur with black, mostly vertical stripes. It is traditionally classified into nine recent subspecies, though some recognise only two subspecies, mainland Asian tigers and the island tigers of the Sunda Islands.',
                'groupings' => [
                    ['name' => 'P.tigris', 'description' => '', 'classification_id' => 8, 'level_id' => 5, 'parent_group_id' => 9], // 9 - Panthera
                ]
            ]
        ];
        
        foreach ($animals as $animal) {
            $group_parent_id = null;
            foreach ($animal['groupings'] as $key => $group) {
                // initial group_parent_id (if set) can only be set for the first group
                $group_parent_id = $key == 0 && isset($group['parent_group_id']) && $group['parent_group_id'] ? $group['parent_group_id'] : $group_parent_id;

                $insertedOrFetchedGroup = Group::firstOrCreate(
                    ['name' => $group['name'], 'classification_id' => $group['classification_id'], 'level_id' => $group['level_id']],
                    ['description' => $group['description']
                ]);

                if ($group_parent_id && $insertedOrFetchedGroup->wasRecentlyCreated) {
                    $insertedOrFetchedGroup->parent_group_id = $group_parent_id;
                    $insertedOrFetchedGroup->save();
                }

                $group_parent_id = $insertedOrFetchedGroup->id;
            }

            Animal::create([
                'name' => $animal['name'],
                'alt_name' => $animal['alt_name'],
                'description' => $animal['description'],
                'group_id' => $group_parent_id
            ]);
        }
    }
}
