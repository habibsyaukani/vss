<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdleAlarm;
use Illuminate\Http\Request;

class IdleAlarmController extends Controller
{
    /**
     * GET /api/idle-alarms
     * List idle alarms dengan pagination dan filtering
     * Query params: page, per_page, device_id, group_name, start_date, end_date, min_duration
     */
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 50);
        $perPage = min($perPage, 500); // Max 500 per page
        
        $deviceId = $request->query('device_id');
        $groupName = $request->query('group_name');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $minDuration = $request->query('min_duration', 0); // Minimum duration in minutes

        $query = IdleAlarm::where('alarm_status', 'CLOSED') // Only show completed alarms
            ->where('end_speed', '>', 0); // Double check: vehicle moved (safety)

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        if ($groupName) {
            // Join with devices table to filter by group
            $query->whereIn('device_id', function($q) use ($groupName) {
                $q->select('device_id')->from('devices')->where('group_name', $groupName);
            });
        }

        if ($startDate) {
            $query->whereDate('starting_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('starting_time', '<=', $endDate);
        }

        if ($minDuration > 0) {
            $query->where('duration_minutes', '>=', $minDuration);
        }

        $alarms = $query->orderByDesc('starting_time')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $alarms->total(),
                'per_page' => $alarms->perPage(),
                'current_page' => $alarms->currentPage(),
                'last_page' => $alarms->lastPage(),
                'alarms' => $alarms->items(),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/idle-alarms/{id}
     * Detail idle alarm dengan informasi lengkap
     */
    public function show($id)
    {
        $alarm = IdleAlarm::findOrFail($id);

        // Double check: only show CLOSED alarms to user
        if ($alarm->alarm_status !== 'CLOSED' || $alarm->end_speed <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Alarm record not available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $alarm,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * PUT /api/idle-alarms/{id}
     * Update alarm status (acknowledge/close)
     * Only allow status updates, not data modification
     */
    public function update(Request $request, $id)
    {
        $alarm = IdleAlarm::findOrFail($id);

        // Only allow updating status field for safety
        $validated = $request->validate([
            'status_note' => 'nullable|string|max:255',
        ]);

        // Note: status is auto-set to CLOSED from ProcessIdleAlarmJob
        // Users can only add notes, not change status
        if (isset($validated['status_note'])) {
            $alarm->update(['status_note' => $validated['status_note']]);
        }

        return response()->json([
            'success' => true,
            'data' => $alarm,
            'message' => 'Alarm updated',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * DELETE /api/idle-alarms/{id}
     * Delete alarm (for testing only, hidden in production)
     */
    public function destroy($id)
    {
        $alarm = IdleAlarm::findOrFail($id);
        $alarm->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Alarm deleted',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/idle-alarms/device/{deviceId}
     * Get all alarms for specific device
     */
    public function byDevice($deviceId, Request $request)
    {
        $limit = $request->query('limit', 50);
        $limit = min($limit, 500);

        $alarms = IdleAlarm::where('device_id', $deviceId)
            ->where('alarm_status', 'CLOSED')
            ->where('end_speed', '>', 0)
            ->orderByDesc('starting_time')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $deviceId,
                'total' => $alarms->count(),
                'alarms' => $alarms,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/idle-alarms/group/{groupName}
     * Get all alarms for specific group
     */
    public function byGroup($groupName, Request $request)
    {
        $limit = $request->query('limit', 100);
        $limit = min($limit, 500);

        $alarms = IdleAlarm::whereIn('device_id', function($q) use ($groupName) {
                $q->select('device_id')->from('devices')->where('group_name', $groupName);
            })
            ->where('alarm_status', 'CLOSED')
            ->where('end_speed', '>', 0)
            ->orderByDesc('starting_time')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'group_name' => $groupName,
                'total' => $alarms->count(),
                'alarms' => $alarms,
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
