<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BranchResource;
use App\Http\Resources\Api\V1\HolidayResource;
use App\Models\Branch;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GeneralController extends Controller
{
    /**
     * Get general application information (public, no auth required).
     */
    public function appInfo(): JsonResponse
    {
        return response()->json(['data' => [
            'app_name' => config('app.name'),
            'version' => '1.0.0',
            'minimum_version' => '1.0.0',
            'maintenance_mode' => app()->isDownForMaintenance(),
        ]]);
    }

    /**
     * List all active branches.
     */
    public function branches(): AnonymousResourceCollection
    {
        return BranchResource::collection(Branch::active()->get());
    }

    /**
     * List holidays for a given year (defaults to current year).
     */
    public function holidays(Request $request): AnonymousResourceCollection
    {
        /** @var string $year */
        $year = $request->query('year', (string) now()->year);

        $holidays = Holiday::whereYear('date', $year)
            ->orderBy('date')
            ->get();

        return HolidayResource::collection($holidays);
    }
}
