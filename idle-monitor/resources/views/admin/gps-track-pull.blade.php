@extends('admin.layouts.app')

@section('title', 'GPS Track Pull')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-map-marked-alt"></i> GPS Track Pull - Tarik Data GPS Track</h2>
            <p class="text-muted">Tarik data GPS Track dari API VSS untuk tanggal tertentu</p>
        </div>
    </div>



    <!-- Data Pull Form -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Form Penarikan Data GPS Track</h5>
                </div>
                <div class="card-body">
                    <form id="gpsTrackPullForm" data-execute-url="{{ route('admin.gps-track-pull.execute') }}" data-stats-url="{{ route('admin.gps-track-pull.statistics') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date" required value="{{ date('Y-m-d', strtotime('-1 day')) }}">
                            <small class="text-muted">Format: YYYY-MM-DD (contoh: 2026-06-12)</small>
                        </div>

                        <div class="mb-3">
                            <label for="device_filter" class="form-label">Filter Device (Optional)</label>
                            <input type="text" class="form-control" id="device_filter" name="device_filter" value="all" placeholder="all atau 75482223,73189119">
                            <small class="text-muted">Kosongkan atau ketik "all" untuk semua device, atau masukkan device ID dipisah koma</small>
                        </div>

                        <div class="mb-3">
                            <label for="limit" class="form-label">Limit Device (Testing)</label>
                            <input type="number" class="form-control" id="limit" name="limit" value="0" min="0" max="397">
                            <small class="text-muted">0 = semua device (397), 10 = hanya 10 device pertama (untuk testing)</small>
                        </div>

                        <div class="mb-3">
                            <label for="concurrency" class="form-label">Concurrency (Paralel Batch)</label>
                            <select class="form-select" id="concurrency" name="concurrency">
                                <option value="1">1 - Sequential (Paling Aman)</option>
                                <option value="3" selected>3 - Cepat & Aman (Direkomendasikan) ✅</option>
                                <option value="5">5 - Sangat Cepat</option>
                                <option value="20">20 - Ekstrim (Hanya untuk Server Produksi)</option>
                            </select>
                            <small class="text-muted">Gunakan 3 agar server lokal tidak hang. Pilihan 20 akan membuat browser stack jika menggunakan artisan serve.</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Perhatian:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Sistem akan menarik data secara batch berdasarkan pilihan concurrency.</li>
                                <li><strong>Waktu pull: ~1-3 menit</strong> untuk semua 397 device.</li>
                                <li>Data disimpan ke tabel <code>gps_tracks_raw</code></li>
                                <li><span class="badge bg-warning text-dark">PENTING</span> Jangan refresh atau close browser saat proses berjalan!</li>
                            </ul>
                        </div>

                        <div class="alert alert-success">
                            <i class="fas fa-lightbulb"></i> <strong>Tips:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Hari kerja (weekday):</strong> Expect 40-60 device dengan data</li>
                                <li><strong>Weekend/holiday:</strong> Expect 10-20 device dengan data</li>
                                <li><strong>Best data day:</strong> June 9 = 61,523 records dari 54 devices</li>
                                <li>Gunakan <strong>Limit 10</strong> untuk testing sebelum pull full</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="pullButton">
                            <i class="fas fa-download"></i> Tarik Data GPS Sekarang
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
                    <h5 class="mb-0"><i class="fas fa-list-alt"></i> Progress & Log</h5>
                </div>
                <div class="card-body">
                    <!-- Progress Container -->
                    <div id="progressContainer" style="display: none;">
                        <div class="mb-4">
                            <h6 class="mb-2">
                                <i class="fas fa-spinner fa-spin"></i> 
                                <span id="progressStatusText">Memulai penarikan GPS data...</span>
                            </h6>
                            <div class="progress" style="height: 30px; border-radius: 10px;">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     role="progressbar" style="width: 0%">
                                    <span id="progressPercentage" class="fw-bold">0%</span>
                                </div>
                            </div>
                            <div class="mt-2 text-center">
                                <small id="progressDetails" class="text-muted">Menghubungi VSS API...</small>
                            </div>
                            <div class="mt-3 text-center">
                                <button type="button" id="cancelPullBtn" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times-circle"></i> Batal Tarik Data
                                </button>
                            </div>
                        </div>

                        <!-- Real-time Stats -->
                        <div class="row mb-3" id="realtimeStats" style="display: none;">
                            <div class="col-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted d-block">Devices</small>
                                        <h5 class="mb-0" id="devicesProcessed">0</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted d-block">With Data</small>
                                        <h5 class="mb-0" id="devicesWithData">0</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted d-block">Records</small>
                                        <h5 class="mb-0" id="recordsSaved">0</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Log Container -->
                    <div id="logContainer" style="max-height: 450px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <div class="alert alert-info mb-0" style="background: white;">
                            <i class="fas fa-info-circle"></i> <strong>Siap untuk menarik data GPS</strong>
                            <p class="mb-0 mt-2 small">Pilih tanggal dan klik tombol "Tarik Data GPS Sekarang" untuk memulai.</p>
                            <hr>
                            <p class="mb-0 small"><strong>System Info:</strong></p>
                            <ul class="mb-0 small">
                                <li>Total devices di database: 397</li>
                                <li>Pull method: Per-device loop (VSS API requirement)</li>
                                <li>Estimated time: 2-3 minutes for all devices</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/gps-track-pull.js') }}"></script>
@endpush
