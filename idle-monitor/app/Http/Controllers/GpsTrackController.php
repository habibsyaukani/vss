<?php

namespace App\Http\Controllers;

use App\Services\GpsTrackSyncService;
use App\Services\VssAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GpsTrackController extends Controller
{
    public function __construct(
        private GpsTrackSyncService $syncService,
        private VssAuthService      $authService,
    ) {}

    /**
     * Preview data per page langsung dari VSS (tanpa simpan ke DB).
     * GET /api/gps-tracks/preview?device_id=73200940&begin=...&end=...&page=1
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'device_id'  => 'required|string',
            'begin_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time'   => 'required|date_format:Y-m-d H:i:s',
            'page'       => 'sometimes|integer|min:1',
        ]);

        $token = $this->authService->getToken();

        $result = $this->syncService->previewPage(
            token:     $token,
            deviceId:  $request->device_id,
            beginTime: $request->begin_time,
            endTime:   $request->end_time,
            page:      (int) $request->get('page', 1),
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json($result);
    }

    /**
     * Sync semua data ke DB (loop semua page otomatis).
     * POST /api/gps-tracks/sync
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'device_id'  => 'required|string',
            'begin_time' => 'required|date_format:Y-m-d H:i:s',
            'end_time'   => 'required|date_format:Y-m-d H:i:s',
        ]);

        $token = $this->authService->getToken();

        $stats = $this->syncService->syncDevice(
            token:     $token,
            deviceId:  $request->device_id,
            beginTime: $request->begin_time,
            endTime:   $request->end_time,
        );

        return response()->json([
            'success' => true,
            'stats'   => $stats,
        ]);
    }
}
