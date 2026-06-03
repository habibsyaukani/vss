<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdleAlarm;
use Illuminate\Http\Request;

class IdleAlarmController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 100);
        $deviceId = $request->query('device_id');
        $status = $request->query('status');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = IdleAlarm::query();

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        if ($status) {
            $query->where('alarm_status', $status);
        }

        if ($startDate) {
            $query->whereDate('starting_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('starting_time', '<=', $endDate);
        }

        $alarms = $query->latest('starting_time')->paginate($perPage, ['*'], 'page', $page);

        return response()->json($alarms);
    }

    public function show($id)
    {
        $alarm = IdleAlarm::findOrFail($id);
        return response()->json($alarm);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guid' => 'required|unique:idle_alarms',
            'device_id' => 'required',
            'device_name' => 'required',
            'alarm_type' => 'required',
            'alarm_status' => 'required',
            'starting_time' => 'required|date',
        ]);

        $alarm = IdleAlarm::create($validated);
        return response()->json($alarm, 201);
    }

    public function update(Request $request, $id)
    {
        $alarm = IdleAlarm::findOrFail($id);
        $alarm->update($request->all());
        return response()->json($alarm);
    }

    public function destroy($id)
    {
        $alarm = IdleAlarm::findOrFail($id);
        $alarm->delete();
        return response()->json(null, 204);
    }
}
