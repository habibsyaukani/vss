<?php
$data = json_decode(file_get_contents(__DIR__ . '/db_schema.json'), true);

$tableDescriptions = [
    'alarm_raw' => 'Menyimpan log alarm mentah (raw data) yang ditarik dari API Howen/VSS, termasuk sinyal waktu awal/akhir, koordinat GPS, dan detail sensor.',
    'alarm_types' => 'Katalog master jenis alarm (misal: Over Speed, Fatigue Driving, Idling, Disconnection) beserta kode alarm resminya.',
    'api_tokens' => 'Menyimpan token autentikasi API temporal untuk komunikasi dengan server VSS Howen external.',
    'data_pull_batches' => 'Mencatat riwayat batch pengambilan data historis (backfill) GPS & Alarm, melacak progres per unit kendaraan.',
    'device_groups' => 'Grup pengelompokan armada / unit kendaraan berdasarkan lokasi operasional atau grup bisnis.',
    'devices' => 'Master data perangkat telemetry/VSS yang terpasang pada unit kendaraan armada (termasuk IMEI, No SIM, Seri, Lokasi, dan Plate No).',
    'export_jobs' => 'Log antrean ekspor laporan ke format Excel/CSV secara asynchronous.',
    'failed_jobs' => 'Log kegagalan tugas antrean (Laravel Queue Jobs) untuk keperluan debugging dan perbaikan sistem.',
    'gps_tracks' => 'Data trek koordinat GPS terfilter dan terproses yang digunakan untuk menghitung kecepatan, status idle, dan rute perjalanan.',
    'gps_tracks_raw' => 'Data mentah (raw telemetri) posisi GPS kendaraan dari API sebelum diproses dan dihitung indikator kinerjanya.',
    'healing_logs' => 'Mencatat aktivitas pemulihan otomatis data (data auto-healing & backfill repair) untuk menjaga integritas durasi idle.',
    'idle_alarms' => 'Data alarm khusus kriteria Idling/Idle (Mesin menyala namun kendaraan berhenti) yang telah dikalkulasi durasinya.',
    'import_logs' => 'Mencatat riwayat pengimporan data massal dari berkas CSV/Excel ke dalam sistem.',
    'jobs' => 'Tabel antrean utama Laravel Queue (Job Processing Queue) untuk memproses sync dan kalkulasi di latar belakang.',
    'password_reset_tokens' => 'Menyimpan token verifikasi untuk mekanisme reset kata sandi pengguna.',
    'personal_access_tokens' => 'Token akses individu berbasis Laravel Sanctum untuk API autentikasi seluler/REST API.',
    'system_settings' => 'Pengaturan dan konfigurasi global sistem (misal: rentang pembersihan data, durasi ambang batas idle, API endpoint).',
    'users' => 'Data pengguna sistem (Pengelola Fleet, Administrator) lengkap dengan hak akses (role) dan token sesi aktif.'
];

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumentasi Struktur Database VSS Monitor</title>
    <style>
        @page {
            size: A4;
            margin: 18mm 15mm 18mm 15mm;
            @bottom-right {
                content: "Halaman " counter(page);
            }
        }
        
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            line-height: 1.5;
            font-size: 11pt;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Cover Page */
        .cover-page {
            page-break-after: always;
            height: 90vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-bottom: 4px solid #2563eb;
            padding: 40px 20px;
        }

        .cover-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }

        .cover-title {
            font-size: 28pt;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }

        .cover-subtitle {
            font-size: 16pt;
            color: #475569;
            margin: 0 0 40px 0;
            font-weight: 400;
        }

        .meta-box {
            background-color: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 16px 20px;
            border-radius: 4px;
            margin-top: 40px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 140px 1fr;
            row-gap: 8px;
            font-size: 10.5pt;
        }

        .meta-label {
            font-weight: 600;
            color: #64748b;
        }

        .meta-value {
            color: #0f172a;
            font-weight: 500;
        }

        /* Table of Contents & Overview */
        .page {
            page-break-after: always;
            padding-top: 10px;
        }

        .section-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 20px;
            margin-top: 25px;
        }

        .section-header h2 {
            font-size: 16pt;
            color: #0f172a;
            margin: 0;
            font-weight: 700;
        }

        /* Executive Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
        }

        .card-number {
            font-size: 20pt;
            font-weight: 700;
            color: #2563eb;
        }

        .card-label {
            font-size: 9.5pt;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Data Tables Overview */
        .overview-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 10pt;
        }

        .overview-table th {
            background-color: #0f172a;
            color: #ffffff;
            text-align: left;
            padding: 8px 12px;
            font-size: 9.5pt;
        }

        .overview-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .overview-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Detailed Table Specs */
        .table-block {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .table-header-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 10px 14px;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title {
            font-size: 13pt;
            font-weight: 700;
            font-family: monospace;
        }

        .table-badge {
            background: #3b82f6;
            color: #ffffff;
            font-size: 8.5pt;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .table-desc {
            background: #f1f5f9;
            padding: 8px 14px;
            font-size: 9.5pt;
            color: #334155;
            border-left: 3px solid #3b82f6;
            margin-bottom: 10px;
            font-style: italic;
        }

        .schema-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9pt;
        }

        .schema-table th {
            background-color: #e2e8f0;
            color: #1e293b;
            text-align: left;
            padding: 6px 10px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
        }

        .schema-table td {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .schema-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .col-name {
            font-family: monospace;
            font-weight: 700;
            color: #0f172a;
        }

        .col-type {
            font-family: monospace;
            color: #0284c7;
        }

        /* Key Badges */
        .key-badge {
            display: inline-block;
            font-size: 7.5pt;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .key-pri { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .key-uni { background: #fef3c7; color: #92400e; border: 1px solid #fde047; }
        .key-mul { background: #e0f2fe; color: #075985; border: 1px solid #7dd3fc; }

        .null-yes { color: #64748b; }
        .null-no { color: #dc2626; font-weight: 600; }

        .index-box {
            background-color: #fafafa;
            border: 1px dashed #cbd5e1;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 8.5pt;
            margin-bottom: 15px;
        }

        .index-title {
            font-weight: 700;
            color: #475569;
            margin-bottom: 4px;
        }

        .index-list {
            margin: 0;
            padding-left: 18px;
            color: #334155;
            font-family: monospace;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>

    <!-- Cover Page -->
    <div class="cover-page">
        <div class="cover-badge">Dokumentasi Teknis</div>
        <h1 class="cover-title">Kamus Data & Struktur Database</h1>
        <div class="cover-subtitle">Dash VSS Monitor System (Vehicle Smart Surveillance)</div>
        
        <div class="meta-box">
            <div class="meta-grid">
                <div class="meta-label">Nama Database:</div>
                <div class="meta-value">vss</div>
                
                <div class="meta-label">Mesin Database:</div>
                <div class="meta-value">MySQL / InnoDB (utf8mb4_unicode_ci)</div>

                <div class="meta-label">Total Tabel:</div>
                <div class="meta-value"><?= count($data) ?> Tabel</div>

                <div class="meta-label">Sistem Aplikasi:</div>
                <div class="meta-value">Dash VSS Telemetry & Idle Monitoring Platform</div>

                <div class="meta-label">Tanggal Rilis:</div>
                <div class="meta-value"><?= date('d F Y') ?></div>
            </div>
        </div>
    </div>

    <!-- Overview Section -->
    <div class="page">
        <div class="section-header">
            <h2>Ringkasan Sistem & Arsitektur Database</h2>
        </div>

        <div class="summary-cards">
            <div class="card">
                <div class="card-number"><?= count($data) ?></div>
                <div class="card-label">Tabel Aktif</div>
            </div>
            <div class="card">
                <div class="card-number">
                    <?php
                    $totalCols = 0;
                    foreach ($data as $t) { $totalCols += count($t['columns']); }
                    echo $totalCols;
                    ?>
                </div>
                <div class="card-label">Total Atribut/Kolom</div>
            </div>
            <div class="card">
                <div class="card-number">
                    <?php
                    $totalRows = 0;
                    foreach ($data as $t) { $totalRows += $t['rows_count']; }
                    echo number_format($totalRows);
                    ?>
                </div>
                <div class="card-label">Total Rekaman Data</div>
            </div>
        </div>

        <h3>Daftar Tabel Database</h3>
        <table class="overview-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Nama Tabel</th>
                    <th style="width: 12%;">Jumlah Kolom</th>
                    <th style="width: 15%;">Jumlah Data</th>
                    <th style="width: 43%;">Deskripsi Singkat</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($data as $tableName => $tableInfo): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td style="font-family: monospace; font-weight: bold; color: #2563eb;"><?= htmlspecialchars($tableName) ?></td>
                    <td><?= count($tableInfo['columns']) ?> Kolom</td>
                    <td><?= number_format($tableInfo['rows_count']) ?> baris</td>
                    <td><?= htmlspecialchars($tableDescriptions[$tableName] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Detailed Table Specifications -->
    <div class="page">
        <div class="section-header">
            <h2>Spesifikasi Detail Tabel (Kamus Data)</h2>
        </div>

        <?php foreach ($data as $tableName => $tableInfo): ?>
        <div class="table-block">
            <div class="table-header-bar">
                <span class="table-title">Tabel: <?= htmlspecialchars($tableName) ?></span>
                <span class="table-badge"><?= number_format($tableInfo['rows_count']) ?> Baris Data</span>
            </div>
            <div class="table-desc">
                <strong>Fungsi:</strong> <?= htmlspecialchars($tableDescriptions[$tableName] ?? 'Tabel pendukung sistem.') ?>
            </div>

            <table class="schema-table">
                <thead>
                    <tr>
                        <th style="width: 22%;">Nama Kolom</th>
                        <th style="width: 20%;">Tipe Data</th>
                        <th style="width: 10%;">Null</th>
                        <th style="width: 10%;">Kunci</th>
                        <th style="width: 18%;">Default</th>
                        <th style="width: 20%;">Atribut Lain</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableInfo['columns'] as $col): ?>
                    <tr>
                        <td class="col-name"><?= htmlspecialchars($col['name']) ?></td>
                        <td class="col-type"><?= htmlspecialchars($col['type']) ?></td>
                        <td>
                            <?php if ($col['null'] === 'YES'): ?>
                                <span class="null-yes">YES</span>
                            <?php else: ?>
                                <span class="null-no">NO</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($col['key'] === 'PRI'): ?>
                                <span class="key-badge key-pri">PRIMARY</span>
                            <?php elseif ($col['key'] === 'UNI'): ?>
                                <span class="key-badge key-uni">UNIQUE</span>
                            <?php elseif ($col['key'] === 'MUL'): ?>
                                <span class="key-badge key-mul">INDEX</span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-size: 8.5pt; color: #475569;">
                            <?= $col['default'] !== null ? htmlspecialchars($col['default']) : '<i>NULL</i>' ?>
                        </td>
                        <td style="font-size: 8.5pt; color: #64748b;">
                            <?= htmlspecialchars($col['extra'] ?: '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($tableInfo['indexes'])): ?>
            <div class="index-box">
                <div class="index-title">Indeks & Batasan Unik:</div>
                <ul class="index-list">
                    <?php 
                    $groupedIndexes = [];
                    foreach ($tableInfo['indexes'] as $idx) {
                        $groupedIndexes[$idx['name']][] = $idx['column'];
                    }
                    foreach ($groupedIndexes as $idxName => $cols): 
                    ?>
                        <li><strong><?= htmlspecialchars($idxName) ?></strong> (<?= implode(', ', $cols) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

</body>
</html>
<?php
$htmlContent = ob_get_clean();
file_put_contents(__DIR__ . '/struktur_database.html', $htmlContent);
echo "Berhasil menghasilkan struktur_database.html (" . strlen($htmlContent) . " bytes)\n";
