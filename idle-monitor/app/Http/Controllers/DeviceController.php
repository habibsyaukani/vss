<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceGroup;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DeviceController extends Controller
{
    /**
     * Show devices list page
     */
    public function index()
    {
        $groups = DeviceGroup::all();
        return view('admin.device.index', compact('groups'));
    }

    /**
     * Get devices data for DataTable (AJAX)
     */
    public function data(Request $request)
    {
        $query = Device::query();

        // Filter by group_name (DT - GPE, BUS - GPE, etc)
        if ($request->filled('group_name') && $request->group_name !== 'all') {
            $query->where('group_name', $request->group_name);
        }

        // Filter by series (DT BARU FMX 400, HD 465, etc)
        if ($request->filled('series') && $request->series !== 'all') {
            $query->where('series', $request->series);
        }

        if ($request->filled('location') && $request->location !== 'all') {
            $query->where('lokasi', $request->location);
        }

        // Filter by status (active/inactive)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Legacy filter by group_id (keep for backward compatibility)
        if ($request->group_id) {
            $query->where('group_id', $request->group_id);
        }

        return DataTables::eloquent($query)
            ->addColumn('unit_code', function ($device) {
                return $device->unit_code ?? '<span class="text-muted">(NULL)</span>';
            })
            ->addColumn('location', function ($device) {
                return $device->lokasi ?? '<span class="text-muted">(NULL)</span>';
            })
            ->addColumn('series', function ($device) {
                return $device->series ?? '<span class="text-muted">(NULL)</span>';
            })
            ->editColumn('group_name', function ($device) {
                return $device->group_name ?? '<span class="text-muted">(NULL)</span>';
            })
            ->editColumn('plate_no', function ($device) {
                return $device->plate_no ?? '<span class="text-muted">(NULL)</span>';
            })
            ->editColumn('imei', function ($device) {
                return $device->imei ?? '<span class="text-muted">(NULL)</span>';
            })
            ->editColumn('sim_number', function ($device) {
                return $device->sim_number ?? '<span class="text-muted">(NULL)</span>';
            })
            ->addColumn('status_badge', function ($device) {
                $class = $device->status === 'active' ? 'bg-success' : 'bg-secondary';
                $status = ucfirst($device->status ?? 'inactive');
                return '<span class="badge ' . $class . '">' . $status . '</span>';
            })
            ->addColumn('last_sync_formatted', function ($device) {
                if (!$device->last_sync_at) {
                    return '<span class="text-muted">Never</span>';
                }
                return \Carbon\Carbon::parse($device->last_sync_at)->format('Y-m-d H:i:s');
            })
            ->addColumn('created_at_formatted', function ($device) {
                return $device->created_at ? $device->created_at->format('Y-m-d H:i:s') : '';
            })
            ->addColumn('updated_at_formatted', function ($device) {
                return $device->updated_at ? $device->updated_at->format('Y-m-d H:i:s') : '';
            })
            ->editColumn('group_id', function ($device) {
                return $device->group_id ?? '<span class="text-muted">(NULL)</span>';
            })
            ->addColumn('actions', function ($device) {
                return '
                    <a href="' . route('admin.device.edit', $device->id) . '" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $device->id . '" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                ';
            })
            ->rawColumns(['unit_code', 'location', 'series', 'group_name', 'plate_no', 'imei', 'sim_number', 'status_badge', 'last_sync_formatted', 'group_id', 'actions'])
            ->make(true);
    }

    /**
     * Show create device form
     */
    public function create()
    {
        $groups = DeviceGroup::all();
        return view('admin.device.form', compact('groups'));
    }

    /**
     * Store new device
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string|unique:devices,device_id',
            'device_name' => 'required|string|max:255',
            'unit_code' => 'nullable|string|max:100',
            'lokasi' => 'nullable|string|max:100',
            'series' => 'nullable|string|max:100',
            'group_id' => 'required|exists:device_groups,id',
            'group_name' => 'required|string|max:255',
            'plate_no' => 'nullable|string|max:100',
            'imei' => 'nullable|string|max:50',
            'sim_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        if (isset($validated['lokasi']) && empty($validated['location'])) {
            $validated['location'] = $validated['lokasi'];
        } elseif (isset($validated['location']) && empty($validated['lokasi'])) {
            $validated['lokasi'] = $validated['location'];
        }

        Device::create($validated);

        return redirect()->route('admin.device.index')
            ->with('success', 'Device created successfully!');
    }

    /**
     * Show edit device form
     */
    public function edit(Device $device)
    {
        $groups = DeviceGroup::all();
        return view('admin.device.form', compact('device', 'groups'));
    }

    /**
     * Update device
     */
    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'device_id' => 'required|string|unique:devices,device_id,' . $device->id,
            'device_name' => 'required|string|max:255',
            'unit_code' => 'nullable|string|max:100',
            'lokasi' => 'nullable|string|max:100',
            'series' => 'nullable|string|max:100',
            'group_id' => 'required|exists:device_groups,id',
            'group_name' => 'required|string|max:255',
            'plate_no' => 'nullable|string|max:100',
            'imei' => 'nullable|string|max:50',
            'sim_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        if (isset($validated['lokasi']) && empty($validated['location'])) {
            $validated['location'] = $validated['lokasi'];
        } elseif (isset($validated['location']) && empty($validated['lokasi'])) {
            $validated['lokasi'] = $validated['location'];
        }

        $device->update($validated);

        return redirect()->route('admin.device.index')
            ->with('success', 'Device updated successfully!');
    }

    /**
     * Delete device
     */
    public function destroy(Device $device)
    {
        $device->delete();
        return response()->json(['message' => 'Device deleted successfully!']);
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        return view('admin.device.import');
    }

    /**
     * Handle CSV import
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('file');
        
        // Detect delimiter
        $content = file_get_contents($file->getRealPath());
        $delimiter = strpos($content, ';') !== false ? ';' : ',';
        
        $handle = fopen($file->getRealPath(), 'r');

        $row = 0;
        $imported = 0;
        $errors = [];
        $header = [];

        while (($line = fgetcsv($handle, 10000, $delimiter)) !== false) {
            $row++;
            
            // Skip empty rows
            if (empty(array_filter($line))) {
                continue;
            }

            if ($row == 1) {
                // Parse header, convert to lowercase and replace spaces with underscores
                foreach ($line as $col) {
                    $colName = strtolower(trim(str_replace(' ', '_', $col)));
                    // Handle legacy or alternative header formats
                    if ($colName == 'location') $colName = 'lokasi';
                    if ($colName == 'unit') $colName = 'unit_code';
                    $header[] = $colName;
                }
                continue;
            }

            // Map line data to associative array based on header
            $data = [];
            foreach ($header as $index => $colName) {
                if (isset($line[$index])) {
                    $data[$colName] = trim($line[$index]);
                }
            }

            // Require device_name or device_id to identify the device
            if (empty($data['device_name']) && empty($data['device_id'])) {
                 $errors[] = "Row $row: Missing device_name or device_id";
                 continue;
            }

            try {
                // Find device by device_id or device_name
                $device = null;
                if (!empty($data['device_id'])) {
                    $device = Device::where('device_id', $data['device_id'])->first();
                } 
                if (!$device && !empty($data['device_name'])) {
                    $device = Device::where('device_name', $data['device_name'])->first();
                }

                $allowedFields = ['device_name', 'device_id', 'group_name', 'unit_code', 'lokasi', 'series', 'plate_no', 'imei', 'sim_number', 'status'];
                
                if ($device) {
                    // Update existing device
                    $updateData = [];
                    foreach ($allowedFields as $field) {
                        if (array_key_exists($field, $data)) {
                            $updateData[$field] = $data[$field] === '' ? null : $data[$field];
                        }
                    }
                    if (!empty($updateData)) {
                        $device->update($updateData);
                        $imported++;
                    }
                } else {
                    // Create new device if we have required fields
                    if (!empty($data['device_id']) && !empty($data['device_name'])) {
                        $device = new Device();
                        $device->status = 'active'; // Default
                        
                        foreach ($allowedFields as $field) {
                            if (array_key_exists($field, $data)) {
                                $device->{$field} = $data[$field] === '' ? null : $data[$field];
                            }
                        }
                        $device->save();
                        $imported++;
                    } else {
                        $errors[] = "Row $row: Cannot create new device. Missing required fields (device_id, device_name)";
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Row $row: " . $e->getMessage();
            }
        }

        fclose($handle);

        return response()->json([
            'imported' => $imported,
            'errors' => $errors,
            'message' => "$imported devices processed successfully!",
        ]);
    }
}
