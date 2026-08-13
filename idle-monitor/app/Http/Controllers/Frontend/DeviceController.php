<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceGroup;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DeviceController extends Controller
{
    /**
     * Show devices list (read-only for fleet manager)
     */
    public function index()
    {
        $groups = DeviceGroup::all();
        return view('frontend.device.index', compact('groups'));
    }

    /**
     * Get devices data for DataTable (AJAX) - Read-only
     */
    public function data(Request $request)
    {
        $query = Device::where('status', 'active'); // Only show active devices

        // Filter by group
        if ($request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        return DataTables::of($query)
            ->addColumn('group_badge', function ($device) {
                return '<span class="badge bg-info">' . ($device->group_name ?? 'N/A') . '</span>';
            })
            ->addColumn('last_sync_formatted', function ($device) {
                return $device->last_sync_at ? $device->last_sync_at->format('Y-m-d H:i') : 'Never';
            })
            ->addColumn('actions', function ($device) {
                return '
                    <a href="' . route('frontend.device.show', $device->id) . '" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                ';
            })
            ->rawColumns(['group_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show device detail (read-only)
     */
    public function show(Device $device)
    {
        // Get idle alarms for this device (last 30 days)
        $idleAlarms = \App\Models\AlarmRaw::where('device_id', $device->device_id)
            ->where('alarm_type', 32)
            ->where('alarm_state', 0)
            ->whereNotNull('end_time')
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();
            
        // Map raw object properties to simulate IdleAlarm structure for the view
        $idleAlarms->transform(function ($alarm) {
            $alarm->starting_time = $alarm->start_time;
            $alarm->ending_time = $alarm->end_time;
            $alarm->duration_minutes = $alarm->start_time && $alarm->end_time ? ceil($alarm->end_time->diffInSeconds($alarm->start_time) / 60) : 0;
            $alarm->alarm_status = 'ALARM_END';
            return $alarm;
        });

        return view('frontend.device.show', compact('device', 'idleAlarms'));
    }
}
