@extends('admin.layouts.app')

@section('title', 'Data Pull')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-download"></i> Data Pull - Tarik Data Idle Alarm</h2>
            <p class="text-muted">Tarik data idle alarm dari API Howen untuk rentang tanggal tertentu</p>
        </div>
    </div>



    <!-- Data Pull Form -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Form Penarikan Data</h5>
                </div>
                <div class="card-body">
                    <form id="dataPullForm" data-execute-url="{{ route('admin.data-pull.execute') }}" data-stats-url="{{ route('admin.data-pull.statistics') }}" data-progress-url="{{ route('admin.data-pull.progress', ':sessionId') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="date" class="form-label">Pilih Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date" required>
                            <small class="text-muted">Pilih 1 tanggal untuk ditarik. Backend akan otomatis membagi menjadi 8 batch (3 jam per batch).</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Sistem Batch Otomatis:</strong>
                            <ul class="mb-0 mt-2">
                                <li>1 hari = <strong>8 batch</strong> (00:00-02:59, 03:00-05:59, ... 21:00-23:59)</li>
                                <li>Setiap batch berjalan <strong>sekuensial</strong> (tidak parallel)</li>
                                <li>Browser <strong>tidak timeout</strong> - proses di background queue</li>
                                <li><strong>Progress real-time</strong> ditampilkan di sebelah kanan</li>
                                <li>Anda bisa <strong>tutup tab</strong>, proses tetap jalan</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="pullButton">
                            <i class="fas fa-download"></i> Tarik Data Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Log -->
        <div class="col-md-6">


            <!-- Progress & Log -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Progress & Log</h5>
                </div>
                <div class="card-body">
                    <!-- Initial State -->
                    <div id="initialState">
                        <div class="alert alert-info mb-0" style="background: white;">
                            <i class="fas fa-info-circle"></i> <strong>Siap untuk menarik data</strong>
                            <p class="mb-0 mt-2 small">Pilih tanggal dan klik tombol "Tarik Data Sekarang" untuk memulai.</p>
                        </div>
                    </div>

                    <!-- Progress Container (hidden initially) -->
                    <div id="progressContainer" style="display: none;">
                        <!-- Session Info -->
                        <div class="alert alert-primary mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-calendar-day"></i> 
                                    <strong>Tanggal:</strong> <span id="sessionDate">-</span>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Session: <span id="sessionIdDisplay">-</span></small>
                                </div>
                            </div>
                        </div>

                        <!-- Overall Progress Bar -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-bold">Progress Keseluruhan</span>
                                <span id="overallProgress" class="text-muted">0 / 8 batch</span>
                            </div>
                            <div class="progress" style="height: 30px; border-radius: 10px;">
                                <div id="overallProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     role="progressbar" style="width: 0%">
                                    <span id="overallPercentage" class="fw-bold">0%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Row -->
                        <div class="row mb-3">
                            <div class="col-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted d-block">Total Records</small>
                                        <h5 class="mb-0" id="totalRecords">0</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted d-block">Elapsed</small>
                                        <h6 class="mb-0" id="elapsedTime">-</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted d-block">ETA</small>
                                        <h6 class="mb-0" id="etaTime">-</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Batch List -->
                        <div id="batchList" style="max-height: 400px; overflow-y: auto;">
                            <h6 class="mb-2 text-muted">Rincian Batch:</h6>
                            <div id="batchItems">
                                <!-- Batch items will be inserted here by JS -->
                            </div>
                        </div>

                        <!-- Auto Refresh Notice -->
                        <div class="alert alert-info mt-3 mb-0">
                            <small>
                                <i class="fas fa-sync-alt fa-spin"></i> 
                                Auto-refresh setiap 3 detik. Anda bisa tutup tab ini, proses tetap berjalan.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/data-pull.js') }}"></script>
@endpush
