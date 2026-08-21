<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use Yajra\DataTables\Facades\DataTables;

class ImportLogController extends Controller
{
    /**
     * Show import logs list page
     */
    public function index()
    {
        return view('admin.import-log.index');
    }

    /**
     * Get import logs data for DataTable (AJAX)
     * Optimized: Use query() instead of get() for better performance with large datasets
     */
    public function data()
    {
        // ✅ OPTIMIZED: Use query builder for server-side pagination
        $query = ImportLog::query()->orderBy('created_at', 'desc');

        return DataTables::eloquent($query)
            ->addColumn('status_badge', function ($log) {
                if ($log->status === 'completed') {
                    $class = 'bg-success';
                } elseif ($log->status === 'failed') {
                    $class = 'bg-danger';
                } else {
                    $class = 'bg-warning';
                }
                return '<span class="badge ' . $class . '">' . ucfirst($log->status) . '</span>';
            })
            ->addColumn('started_at_formatted', function ($log) {
                return $log->started_at ? $log->started_at->format('Y-m-d H:i:s') : '-';
            })
            ->addColumn('finished_at_formatted', function ($log) {
                return $log->finished_at ? $log->finished_at->format('Y-m-d H:i:s') : '-';
            })
            ->addColumn('duration', function ($log) {
                if ($log->started_at && $log->finished_at) {
                    $duration = $log->finished_at->diffInSeconds($log->started_at);
                    return $duration . 's';
                }
                return '-';
            })
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    /**
     * Get latest logs (for auto-refresh)
     */
    public function latest()
    {
        $logs = ImportLog::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($logs);
    }
}
