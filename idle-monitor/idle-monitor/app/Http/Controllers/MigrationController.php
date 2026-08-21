<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class MigrationController extends Controller
{
    /**
     * Show migration page
     */
    public function index()
    {
        $tableExists = Schema::hasTable('data_pull_batches');
        
        return view('admin.run-migration', [
            'table_exists' => $tableExists,
        ]);
    }

    /**
     * Run migration
     */
    public function runMigration(Request $request)
    {
        try {
            // Check if table already exists
            if (Schema::hasTable('data_pull_batches')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Table already exists!',
                    'output' => 'data_pull_batches table is already in database.',
                ]);
            }

            // Run migration
            Artisan::call('migrate', [
                '--force' => true,
                '--path' => 'database/migrations/2026_07_16_100000_create_data_pull_batches_table.php',
            ]);

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Migration completed successfully!',
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
                'output' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
