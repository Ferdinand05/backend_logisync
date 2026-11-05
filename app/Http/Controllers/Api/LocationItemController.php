<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationDetailResource;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationItemController extends Controller
{
    public function show(Request $request, Location $location): LocationDetailResource
    {
        abort_unless($request->user()->role === 'gudang', 403);

        $location->load([
            'items' => fn ($query) => $query->orderBy('name'),
            'items.category',
        ]);

        return new LocationDetailResource($location);
    }
}
