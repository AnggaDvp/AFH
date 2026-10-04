<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;

class RegionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Region::query()
                ->orderBy('id')
                ->get(['id', 'code', 'name', 'type'])
        );
    }

    public function districts(Region $region): JsonResponse
    {
        return response()->json(
            $region->districts()
                ->orderBy('name')
                ->get(['id', 'region_id', 'code', 'name'])
        );
    }

    public function villages(District $district): JsonResponse
    {
        return response()->json($this->villagesForDistrict($district));
    }

    public function villagesInRegion(Region $region, District $district): JsonResponse
    {
        abort_unless($district->region_id === $region->id, 404);

        return response()->json($this->villagesForDistrict($district));
    }

    private function villagesForDistrict(District $district): Collection
    {
        return $district->villages()
            ->orderBy('name')
            ->get(['id', 'district_id', 'code', 'name', 'type', 'postal_code']);
    }
}
