<?php

namespace App\Services;

use App\Models\Classification;
use App\Models\Level;

class RankingService
{
    private $classificationRanks;
    private $levelRanks;

    public function __construct()
    {
        $this->classificationRanks = $this->buildRankedList(Classification::all());
        $this->levelRanks = $this->buildRankedList(Level::all());
    }

    public function buildRankedList($linkedList)
    {
        $keyedByIds = $linkedList->keyBy('id');

        // Find the root node (preceded_by_id === null)
        $root = $linkedList->firstWhere('preceded_by_id', null);

        $map = [];
        $current = $root;
        $rank = 1;

        while ($current) {
            $map[$current->id] = $rank++;
            $current = $keyedByIds->get($current->succeeded_by_id);
        }

        return $map;
    }


    public function getClassificationRanks()
    {
        return $this->classificationRanks;
    }

    public function getLevelRanks()
    {
        return $this->levelRanks;
    }
}

