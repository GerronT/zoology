<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RankingService;

class RankingController extends Controller
{
    public function index(RankingService $rankingService)
    {
        return response()->json([
            'classificationRanks' => $rankingService->getClassificationRanks(),
            'levelRanks' => $rankingService->getLevelRanks(),
        ]);
    }
}
