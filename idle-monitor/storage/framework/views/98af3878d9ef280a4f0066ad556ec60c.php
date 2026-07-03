

<?php $__env->startSection('title', 'Data Pull'); ?>

<?php $__env->startSection('content'); ?>
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
                    <form id="dataPullForm" data-execute-url="<?php echo e(route('admin.data-pull.execute')); ?>" data-stats-url="<?php echo e(route('admin.data-pull.statistics')); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label for="from_date" class="form-label">Dari Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="from_date" name="from_date" required>
                            <small class="text-muted">Format: YYYY-MM-DD (contoh: 2026-06-01)</small>
                        </div>

                        <div class="mb-3">
                            <label for="to_date" class="form-label">Sampai Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="to_date" name="to_date" required>
                            <small class="text-muted">Format: YYYY-MM-DD (contoh: 2026-06-08)</small>
                        </div>

                        <div class="mb-3">
                            <label for="pages" class="form-label">Jumlah Pages</label>
                            <input type="number" class="form-control" id="pages" name="pages" value="100" min="1" max="200">
                            <small class="text-muted">Default: 100 (1 page = 200 records)</small>
                        </div>

                        <div class="mb-3">
                            <label for="concurrency" class="form-label">Concurrency (Paralel)</label>
                            <select class="form-select" id="concurrency" name="concurrency">
                                <option value="1" selected>1 - Sequential (Safe & Lambat)</option>
                                <option value="2">2 - Fast</option>
                                <option value="5">5 - Very Fast (Rawan Kena Limit Howen)</option>
                            </select>
                            <small class="text-muted">Gunakan 1 (Safe) agar tidak diblokir/limit oleh server Howen.</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Perhatian:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Proses penarikan data memakan waktu tergantung jumlah hari</li>
                                <li><strong>1 hari = ~10-20 ribu records = ~1-3 menit</strong></li>
                                <li><strong>Rentang besar (7+ hari) bisa mencapai 10-15 menit</strong></li>
                                <li>Data akan diproses otomatis setelah pull selesai</li>
                                <li>Tunggu hingga proses selesai, jangan tutup halaman ini</li>
                                <li><span class="badge bg-warning text-dark">PENTING</span> Jangan refresh atau close browser saat proses berjalan!</li>
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
                    <h5 class="mb-0"><i class="fas fa-list-alt"></i> Progress & Log</h5>
                </div>
                <div class="card-body">
                    <!-- Progress Container -->
                    <div id="progressContainer" style="display: none;">
                        <div class="mb-4">
                            <h6 class="mb-2">
                                <i class="fas fa-spinner fa-spin"></i> 
                                <span id="progressStatusText">Memulai penarikan data...</span>
                            </h6>
                            <div class="progress" style="height: 30px; border-radius: 10px;">
                                <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     role="progressbar" style="width: 0%">
                                    <span id="progressPercentage" class="fw-bold">0%</span>
                                </div>
                            </div>
                            <div class="mt-2 text-center">
                                <small id="progressDetails" class="text-muted">Menghubungi API Howen...</small>
                            </div>
                            <div class="mt-3 text-center">
                                <button type="button" id="cancelPullBtn" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times-circle"></i> Batal Tarik Data
                                </button>
                            </div>
                        </div>

                        <!-- Real-time Stats -->
                        <div class="row mb-3" id="realtimeStats" style="display: none;">
                            <div class="col-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted d-block">Records Fetched</small>
                                        <h5 class="mb-0" id="recordsFetched">0</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2 text-center">
                                        <small class="text-muted d-block">Idle Alarms</small>
                                        <h5 class="mb-0" id="idleAlarmsProcessed">0</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Log Container -->
                    <div id="logContainer" style="max-height: 450px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <div class="alert alert-info mb-0" style="background: white;">
                            <i class="fas fa-info-circle"></i> <strong>Siap untuk menarik data</strong>
                            <p class="mb-0 mt-2 small">Isi form dan klik tombol "Tarik Data Sekarang" untuk memulai.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('js/data-pull.js')); ?>"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views/admin/data-pull.blade.php ENDPATH**/ ?>