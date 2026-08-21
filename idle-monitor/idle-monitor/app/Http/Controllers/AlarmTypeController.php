<?php

namespace App\Http\Controllers;

use App\Models\AlarmType;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AlarmTypeController extends Controller
{
    /**
     * Show alarm types list page
     */
    public function index()
    {
        return view('admin.alarm-type.index');
    }

    /**
     * Get alarm types data for DataTable (AJAX)
     */
    public function data()
    {
        $alarmTypes = AlarmType::query();

        return DataTables::of($alarmTypes)
            ->addColumn('actions', function ($type) {
                return '
                    <a href="' . route('alarm-type.edit', $type->id) . '" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $type->id . '">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.alarm-type.form');
    }

    /**
     * Store new alarm type
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'alarm_code' => 'required|integer|unique:alarm_types,alarm_code',
            'alarm_name' => 'required|string|max:255',
        ]);

        AlarmType::create($validated);

        return redirect()->route('alarm-type.index')
            ->with('success', 'Alarm type created successfully!');
    }

    /**
     * Show edit form
     */
    public function edit(AlarmType $alarmType)
    {
        return view('admin.alarm-type.form', ['alarmType' => $alarmType]);
    }

    /**
     * Update alarm type
     */
    public function update(Request $request, AlarmType $alarmType)
    {
        $validated = $request->validate([
            'alarm_code' => 'required|integer|unique:alarm_types,alarm_code,' . $alarmType->id,
            'alarm_name' => 'required|string|max:255',
        ]);

        $alarmType->update($validated);

        return redirect()->route('alarm-type.index')
            ->with('success', 'Alarm type updated successfully!');
    }

    /**
     * Delete alarm type
     */
    public function destroy(AlarmType $alarmType)
    {
        $alarmType->delete();
        return response()->json(['message' => 'Alarm type deleted successfully!']);
    }
}
