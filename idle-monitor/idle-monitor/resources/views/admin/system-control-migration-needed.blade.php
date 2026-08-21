@extends('admin.layouts.app')

@section('title', 'Migration Required')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-warning">
                <div class="card-header bg-warning text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Migration Required
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-database"></i> Database Table Missing</h5>
                        <p class="mb-0">
                            The <code>system_settings</code> table does not exist. 
                            Please run the migration to create this table.
                        </p>
                    </div>

                    <h6 class="mt-4">Option 1: Run Migration Manually</h6>
                    <p>Open terminal/command prompt and run:</p>
                    <div class="bg-dark text-white p-3 rounded">
                        <code>php artisan migrate --force</code>
                    </div>

                    <h6 class="mt-4">Option 2: Use Batch File (Windows)</h6>
                    <p>Double-click this file in your project root:</p>
                    <div class="bg-dark text-white p-3 rounded">
                        <code>FIX_SYSTEM_CONTROL.bat</code>
                    </div>

                    <div class="mt-4">
                        <button onclick="location.reload()" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Refresh Page After Migration
                        </button>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>

                    <hr class="my-4">

                    <h6>What will be created:</h6>
                    <ul>
                        <li>Table: <code>system_settings</code></li>
                        <li>Default Settings:
                            <ul>
                                <li><code>cleanup_enabled</code> = true</li>
                                <li><code>cleanup_retention_days</code> = 30</li>
                                <li><code>cleanup_schedule</code> = monthly</li>
                                <li><code>cleanup_last_run</code> = null</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
