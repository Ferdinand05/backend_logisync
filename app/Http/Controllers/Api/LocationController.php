<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $cached = Cache::get('locations:all');

        if ($cached !== null) {
            return response()->json(json_decode($cached, true));
        }

        $locations = Location::query()->orderBy('name')->get();

        $json = LocationResource::collection($locations)->response()->getContent();

        Cache::put('locations:all', $json, now()->addMinutes(15));

        return response()->json(json_decode($json, true));
    }
}
