<?php

namespace App\Http\Controllers;

use App\Models\DeviceGroup;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DeviceGroupController extends Controller
{
    /**
     * Show device groups list page
     */
    public function index()
    {
        return view('admin.device-group.index');
    }

    /**
     * Get device groups data for DataTable (AJAX)
     */
    public function data()
    {
        $groups = DeviceGroup::withCount('devices')->get();

        return DataTables::of($groups)
            ->addColumn('actions', function ($group) {
                return '
                    <a href="' . route('device-group.edit', $group->id) . '" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $group->id . '">
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
        return view('admin.device-group.form');
    }

    /**
     * Store new device group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_code' => 'required|string|unique:device_groups,group_code|max:50',
            'group_name' => 'required|string|max:255',
        ]);

        DeviceGroup::create($validated);

        return redirect()->route('device-group.index')
            ->with('success', 'Device group created successfully!');
    }

    /**
     * Show edit form
     */
    public function edit(DeviceGroup $deviceGroup)
    {
        return view('admin.device-group.form', ['group' => $deviceGroup]);
    }

    /**
     * Update device group
     */
    public function update(Request $request, DeviceGroup $deviceGroup)
    {
        $validated = $request->validate([
            'group_code' => 'required|string|unique:device_groups,group_code,' . $deviceGroup->id . '|max:50',
            'group_name' => 'required|string|max:255',
        ]);

        $deviceGroup->update($validated);

        return redirect()->route('device-group.index')
            ->with('success', 'Device group updated successfully!');
    }

    /**
     * Delete device group
     */
    public function destroy(DeviceGroup $deviceGroup)
    {
        // Check if group has devices
        if ($deviceGroup->devices()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete group with devices',
            ], 422);
        }

        $deviceGroup->delete();

        return response()->json(['message' => 'Device group deleted successfully!']);
    }
}
