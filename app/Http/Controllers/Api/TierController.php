<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\TierConfig;
use Illuminate\Http\JsonResponse;

class TierController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'tier' => TierConfig::getTier(),
            'name' => TierConfig::getCurrentFeatures()['name'],
            'description' => TierConfig::getCurrentFeatures()['description'],
            'features' => TierConfig::getCurrentFeatures()['modules'],
            'settings' => TierConfig::getCurrentFeatures()['settings'],
        ]);
    }

    public function checkFeature(string $feature): JsonResponse
    {
        return response()->json([
            'feature' => $feature,
            'enabled' => TierConfig::hasFeature($feature),
        ]);
    }
}
