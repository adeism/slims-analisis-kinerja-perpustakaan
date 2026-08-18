<?php
/**
 * Analisis Kinerja Perpustakaan Indonesia (PAKPI) - Dashboard Utama
 * 
 * Standar SNI ISO 2789:2013 & ISO 11620:2014
 */

defined('INDEX_AUTH') OR die('Direct access not allowed');

global $dbs, $sysconf;

// SLiMS Admin Session
require_once SB . 'admin/default/session.inc.php';

// Auth & Privilege Validation
if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
    die('<div class="alert alert-danger m-3">' . __('You are not authorized to view this section') . '</div>');
}

$can_read  = utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('reporting', 'w');
if (!$can_read) {
    die('<div class="alert alert-danger m-3">' . __('You do not have permission to access this module!') . '</div>');
}

require_once __DIR__ . '/helper.php';

// Filter Parameters
$current_year        = (int)date('Y');
$tahun               = isset($_GET['tahun']) && is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : $current_year;
$tab                 = $_GET['tab'] ?? 'summary';
$include_renewal     = isset($_GET['include_renewal']) && $_GET['include_renewal'] == '1';
$only_active_members = isset($_GET['only_active_members']) && $_GET['only_active_members'] == '1';
$table_only          = isset($_GET['table_only']) && $_GET['table_only'] == '1';

$settings = pakpiLoadSettings();

// Handle Save Settings Action
$msg_success = '';
$msg_error   = '';

if (isset($_POST['save_settings'])) {
    if (!$can_write) {
        $msg_error = 'Anda tidak memiliki hak akses untuk mengubah pengaturan!';
    } elseif (!pakpiValidateCsrf()) {
        $msg_error = 'Token keamanan (CSRF) tidak valid!';
    } else {
        $signers = [];
        if (!empty($_POST['signers']) && is_array($_POST['signers'])) {
            foreach ($_POST['signers'] as $s) {
                $lbl = trim($s['label'] ?? '');
                $jab = trim($s['jabatan'] ?? '');
                $nam = trim($s['nama'] ?? '');
                $nip = trim($s['nip'] ?? '');
                if ($jab !== '' || $nam !== '') {
                    $signers[] = [
                        'label'   => $lbl ?: 'Mengetahui,',
                        'jabatan' => $jab,
                        'nama'    => $nam,
                        'nip'     => $nip
                    ];
                }
            }
        }
        if (empty($signers)) {
            $signers[] = [
                'label'   => 'Mengetahui,',
                'jabatan' => 'Kepala Perpustakaan',
                'nama'    => '',
                'nip'     => ''
            ];
        }

        $newSettings = [
            'instansi' => trim($_POST['instansi'] ?? ''),
            'unit'     => trim($_POST['unit'] ?? ''),
            'alamat'   => trim($_POST['alamat'] ?? ''),
            'kota'     => trim($_POST['kota'] ?? 'Jakarta'),
            'signers'  => $signers
        ];

        if (pakpiSaveSettings($newSettings)) {
            $msg_success = 'Pengaturan kop laporan dan penandatangan berhasil disimpan!';
            $settings = $newSettings;
        } else {
            $msg_error = 'Gagal menyimpan pengaturan ke settings.json!';
        }
    }
}

// Handle Standalone Print / PDF View
if (isset($_GET['action']) && $_GET['action'] === 'print_view') {
    require __DIR__ . '/inc/print_report.inc.php';
    exit;
}

// Calculate Indicator Data
$dataB211 = pakpiGetB211($dbs, $tahun, $include_renewal);
$dataB212 = pakpiGetB212($dbs, $tahun, $include_renewal, $only_active_members);
$dataB213 = pakpiGetB213($dbs, $tahun);
$dataB221 = pakpiGetB221($dbs, $tahun, $only_active_members);
$insights = pakpiGenerateInsights($dataB211, $dataB212, $dataB213, $dataB221);

// ── Handle EXCEL Export (.xls) ──────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $meta = [
        'Instansi'       => $settings['instansi'] ?? 'Perpustakaan',
        'Unit'           => $settings['unit'] ?? 'UPT Perpustakaan',
        'Tahun Acuan'    => $tahun,
        'Tanggal Ekspor' => date('d F Y H:i:s'),
        'Standar Acuan'  => 'SNI ISO 2789:2013 & ISO 11620:2014'
    ];

    if ($tab === 'multiyear') {
        $trendData = pakpiGetMultiYearTrend($dbs, $tahun, 5);
        $rows = [];
        foreach ($trendData as $y => $td) {
            $rows[] = [
                $y,
                $td['perputaran_eks'],
                $td['perputaran_judul'],
                $td['pinjaman_kapita'],
                $td['total_pinjaman'],
                $td['pct_koleksi_tdk'] . '%',
                $td['pct_pemanfaatan'] . '%',
                $td['kunjungan_kapita'],
                $td['total_kunjungan'],
                $td['populasi']
            ];
        }
        $tables = [[
            'title'   => 'Tabel Matriks Kinerja Multi-Tahun (' . ($tahun - 4) . ' - ' . $tahun . ')',
            'headers' => ['Tahun', 'Perputaran (thd Eks)', 'Perputaran (thd Judul)', 'Pinjaman per Kapita', 'Total Pinjaman', 'Koleksi Belum Dipinjam (%)', 'Pemanfaatan Koleksi (%)', 'Kunjungan per Kapita', 'Total Kunjungan', 'Populasi Anggota'],
            'rows'    => $rows
        ]];
        pakpiExportExcel('Tren_Kinerja_MultiTahun_' . ($tahun - 4) . '-' . $tahun . '.xls', 'LAPORAN TREN KINERJA MULTI-TAHUN PERPUSTAKAAN', $tables, $meta, $settings['signers'] ?? []);
    } elseif ($tab === 'monthly') {
        $monthly = pakpiGetMonthlyTrend($dbs, $tahun);
        $rows = [];
        foreach ($monthly as $m) {
            $rows[] = [$m['month_code'], $m['month_name'], $m['loans'], $m['visits']];
        }
        $tables = [[
            'title'   => 'Distribusi Aktivitas Bulanan (Tahun ' . $tahun . ')',
            'headers' => ['Kode Bulan', 'Nama Bulan', 'Total Peminjaman Buku', 'Total Kunjungan Pemustaka'],
            'rows'    => $rows
        ]];
        pakpiExportExcel('Distribusi_Bulanan_' . $tahun . '.xls', 'LAPORAN DISTRIBUSI AKTIVITAS BULANAN PERPUSTAKAAN', $tables, $meta, $settings['signers'] ?? []);
    } elseif ($tab === 'insights') {
        $rows = [];
        $no = 1;
        foreach ($insights as $ins) {
            $rows[] = [$no++, $ins['title'], $ins['message']];
        }
        $tables = [[
            'title'   => 'Evaluasi Mutu & Rekomendasi Manajerial (Tahun ' . $tahun . ')',
            'headers' => ['No', 'Poin Evaluasi Kinerja', 'Rekomendasi Kebijakan'],
            'rows'    => $rows
        ]];
        pakpiExportExcel('Evaluasi_Mutu_PAKPI_' . $tahun . '.xls', 'LAPORAN EVALUASI & REKOMENDASI MUTU KINERJA', $tables, $meta, $settings['signers'] ?? []);
    } else {
        // Tab summary - Complete 4 Indicators Excel
        $tables = [];

        // 1. Executive Summary Table
        $tables[] = [
            'title'   => 'RINGKASAN EKSEKUTIF - 4 INDIKATOR KINERJA UTAMA',
            'headers' => ['Kode Indikator', 'Nama Indikator ISO', 'Nilai Capaian', 'Satuan'],
            'rows'    => [
                ['B.2.1.1', 'Perputaran Koleksi (Collection Turnover Rate)', $dataB211[0]['nilai_thd_eksemplar'] ?? 0, 'kali per eksemplar/tahun'],
                ['B.2.1.2', 'Pinjaman Per Kapita (Loans per Capita)', $dataB212[0]['nilai'] ?? 0, 'buku per anggota/tahun'],
                ['B.2.1.3', 'Pemanfaatan Koleksi (Collection Utilization)', $dataB213['pct_digunakan'] . '%', 'koleksi telah dimanfaatkan'],
                ['B.2.2.1', 'Kunjungan Perpustakaan Per Kapita (Library Visits)', $dataB221['nilai'], 'kali kunjungan per anggota/tahun'],
            ]
        ];

        // 2. B.2.1.1 Table
        $rowsB211 = [];
        foreach ($dataB211 as $r) {
            $rowsB211[] = [$r['indikator'], $r['total'], $r['total_eksemplar'], $r['nilai_thd_eksemplar'], $r['total_judul'], $r['nilai_thd_judul']];
        }
        $tables[] = [
            'title'   => '1. B.2.1.1 - Perputaran Koleksi (Collection Turnover Rate)',
            'headers' => ['Indikator / Transaksi', 'Total Transaksi', 'Total Eksemplar', 'Nilai thd Eksemplar', 'Total Judul', 'Nilai thd Judul'],
            'rows'    => $rowsB211
        ];

        // 3. B.2.1.2 Table
        $rowsB212 = [];
        foreach ($dataB212 as $r) {
            $rowsB212[] = [$r['indikator'], $r['total_pinjaman'], $r['total_populasi'], $r['nilai'], 'Rata-rata ' . $r['nilai'] . ' buku dipinjam per anggota'];
        }
        $tables[] = [
            'title'   => '2. B.2.1.2 - Pinjaman Per Kapita (Loans per Capita)',
            'headers' => ['Indikator Transaksi', 'Total Peminjaman', 'Total Populasi Anggota', 'Nilai Capaian', 'Keterangan'],
            'rows'    => $rowsB212
        ];

        // 4. B.2.1.3 Table
        $tables[] = [
            'title'   => '3. B.2.1.3 - Persentase Koleksi Tidak Digunakan (Dormant Collection)',
            'headers' => ['Status Koleksi Bahan Pustaka', 'Jumlah Eksemplar', 'Total Seluruh Eksemplar', 'Persentase (%)'],
            'rows'    => [
                ['Belum Pernah Dipinjam Tahun Ini', $dataB213['total_tidak'], $dataB213['total_eksemplar'], $dataB213['persentase_tidak'] . '%'],
                ['Telah Dimanfaatkan (Peminjaman Aktif)', $dataB213['total_digunakan'], $dataB213['total_eksemplar'], $dataB213['pct_digunakan'] . '%']
            ]
        ];

        // 5. B.2.2.1 Table
        $tables[] = [
            'title'   => '4. B.2.2.1 - Kunjungan Perpustakaan Per Kapita (Library Visits per Capita)',
            'headers' => ['Indikator', 'Total Kehadiran Kunjungan', 'Total Populasi Anggota', 'Nilai Capaian', 'Keterangan'],
            'rows'    => [
                ['Kunjungan Fisik Pemustaka', $dataB221['total_kunjungan'], $dataB221['total_populasi'], $dataB221['nilai'], 'Rata-rata setiap anggota berkunjung ' . $dataB221['nilai'] . ' kali/tahun']
            ]
        ];

        pakpiExportExcel('Analisis_Kinerja_Perpustakaan_' . $tahun . '.xls', 'LAPORAN ANALISIS KINERJA PERPUSTAKAAN (PAKPI)', $tables, $meta, $settings['signers'] ?? []);
    }
}

// ── Handle CSV Export ───────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    if ($tab === 'multiyear') {
        $trendData = pakpiGetMultiYearTrend($dbs, $tahun, 5);
        $headers = ['Tahun', 'Perputaran (thd Eks)', 'Perputaran (thd Judul)', 'Pinjaman per Kapita', 'Total Pinjaman', 'Koleksi Belum Dipinjam (%)', 'Pemanfaatan Koleksi (%)', 'Kunjungan per Kapita', 'Total Kunjungan', 'Populasi'];
        $rows = [];
        foreach ($trendData as $y => $td) {
            $rows[] = [
                $y,
                $td['perputaran_eks'],
                $td['perputaran_judul'],
                $td['pinjaman_kapita'],
                $td['total_pinjaman'],
                $td['pct_koleksi_tdk'] . '%',
                $td['pct_pemanfaatan'] . '%',
                $td['kunjungan_kapita'],
                $td['total_kunjungan'],
                $td['populasi']
            ];
        }
        pakpiExportCsv('Tren_Kinerja_MultiTahun_' . ($tahun - 4) . '-' . $tahun . '.csv', $headers, $rows);
    } elseif ($tab === 'monthly') {
        $monthly = pakpiGetMonthlyTrend($dbs, $tahun);
        $headers = ['Kode Bulan', 'Nama Bulan', 'Total Peminjaman Buku', 'Total Kunjungan Pemustaka'];
        $rows = [];
        foreach ($monthly as $m) {
            $rows[] = [$m['month_code'], $m['month_name'], $m['loans'], $m['visits']];
        }
        pakpiExportCsv('Distribusi_Bulanan_' . $tahun . '.csv', $headers, $rows);
    } elseif ($tab === 'insights') {
        $headers = ['No', 'Poin Evaluasi', 'Rekomendasi Kebijakan'];
        $rows = [];
        $no = 1;
        foreach ($insights as $ins) {
            $rows[] = [$no++, $ins['title'], $ins['message']];
        }
        pakpiExportCsv('Evaluasi_Mutu_PAKPI_' . $tahun . '.csv', $headers, $rows);
    } else {
        $headers = ['Kode Indikator', 'Nama Indikator', 'Rincian / Sub Indikator', 'Total Transaksi', 'Total Populasi/Eksemplar', 'Nilai Capaian', 'Satuan'];
        $rows = [];

        foreach ($dataB211 as $r) {
            $rows[] = ['B.2.1.1', 'Perputaran Koleksi', $r['indikator'] . ' thd Eksemplar', $r['total'], $r['total_eksemplar'], $r['nilai_thd_eksemplar'], 'kali/eksemplar'];
            $rows[] = ['B.2.1.1', 'Perputaran Koleksi', $r['indikator'] . ' thd Judul', $r['total'], $r['total_judul'], $r['nilai_thd_judul'], 'kali/judul'];
        }

        foreach ($dataB212 as $r) {
            $rows[] = ['B.2.1.2', 'Pinjaman Per Kapita', $r['indikator'], $r['total_pinjaman'], $r['total_populasi'], $r['nilai'], 'buku/anggota'];
        }

        $rows[] = ['B.2.1.3', 'Persentase Koleksi Tidak Digunakan', 'Koleksi Belum Dipinjam', $dataB213['total_tidak'], $dataB213['total_eksemplar'], $dataB213['persentase_tidak'], '%'];
        $rows[] = ['B.2.1.3', 'Persentase Koleksi Tidak Digunakan', 'Koleksi Telah Dipinjam (Pemanfaatan)', $dataB213['total_digunakan'], $dataB213['total_eksemplar'], $dataB213['pct_digunakan'], '%'];

        $rows[] = ['B.2.2.1', 'Kunjungan Perpustakaan Per Kapita', $dataB221['indikator'], $dataB221['total_kunjungan'], $dataB221['total_populasi'], $dataB221['nilai'], 'kali/anggota'];

        pakpiExportCsv('Analisis_Kinerja_Perpustakaan_' . $tahun . '.csv', $headers, $rows);
    }
}
?>

<style>
.pakpi-card {
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    margin-bottom: 24px;
    overflow: hidden;
}
.pakpi-card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.pakpi-card-body {
    padding: 20px;
}
.pakpi-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.pakpi-kpi-card {
    background: #ffffff;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 18px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: transform 0.2s, box-shadow 0.2s;
}
.pakpi-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.pakpi-kpi-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 8px;
}
.pakpi-kpi-value {
    font-size: 32px;
    font-weight: 800;
    line-height: 1.1;
    color: #0f172a;
    margin-bottom: 4px;
}
.pakpi-kpi-unit {
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
}
.pakpi-desc {
    background: #f0fdf4;
    border-left: 4px solid #10b981;
    padding: 12px 16px;
    font-size: 13px;
    color: #1e293b;
    border-radius: 4px;
    margin-bottom: 18px;
    line-height: 1.5;
}
.pakpi-nav-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 20px;
    padding-left: 0;
    list-style: none;
    flex-wrap: wrap;
}
.pakpi-nav-item a {
    display: inline-block;
    padding: 10px 18px;
    font-weight: 600;
    font-size: 14px;
    color: #64748b;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}
.pakpi-nav-item a:hover {
    color: #2563eb;
}
.pakpi-nav-item.active a {
    color: #2563eb;
    border-bottom-color: #2563eb;
}

/* Rich Visual Cards & Charts */
.chart-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 18px;
}
@media (max-width: 992px) {
    .chart-wrapper {
        grid-template-columns: 1fr;
    }
}
.chart-box {
    background: #ffffff;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.chart-box h4 {
    color: #1e293b;
    margin: 0 0 15px 0;
    font-size: 15px;
    font-weight: 700;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.comparison-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 12px;
}
.comparison-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px 12px;
    text-align: center;
}
.comparison-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.comparison-value {
    font-size: 28px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
}
.bar-chart {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 5px 0;
}
.bar-item {
    display: flex;
    align-items: center;
    gap: 12px;
}
.bar-label {
    min-width: 95px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}
.bar-container {
    flex: 1;
    background: #f1f5f9;
    border-radius: 6px;
    height: 28px;
    position: relative;
    overflow: hidden;
}
.bar-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.8s ease-out;
}
.bar-value {
    min-width: 85px;
    text-align: right;
    font-weight: 700;
    font-size: 13px;
    color: #334155;
}
.signer-row {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 14px;
    margin-bottom: 12px;
}
@media print {
    .non-printable, .menuBox, .pakpi-filter-box, .btn, .pakpi-nav-tabs {
        display: none !important;
    }
}
</style>

<div class="menuBox mb-3 non-printable">
    <div class="menuBoxInner reportIcon">
        <div class="per_title">
            <h2>📊 <?= __('Analisis Kinerja Perpustakaan Indonesia (PAKPI)') ?></h2>
        </div>
        <div class="sub_section">
            <div class="text-muted small">
                <?= __('Standar Internasional SNI ISO 2789:2013 &amp; ISO 11620:2014 untuk Evaluasi dan Borang Akreditasi Perpustakaan.') ?>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-0">
    <!-- Messages -->
    <?php if ($msg_success): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <strong>✅ <?= __('Berhasil:') ?></strong> <?= htmlspecialchars($msg_success, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <strong>❌ <?= __('Perhatian:') ?></strong> <?= htmlspecialchars($msg_error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- View Navigation Tabs -->
    <ul class="pakpi-nav-tabs non-printable">
        <li class="pakpi-nav-item <?= $tab === 'summary' ? 'active' : '' ?>">
            <a href="<?= pakpiAdminUrl(['tab' => 'summary', 'tahun' => $tahun]) ?>">
                📊 <?= __('Metrik Tahunan (Dashboard)') ?>
            </a>
        </li>
        <li class="pakpi-nav-item <?= $tab === 'multiyear' ? 'active' : '' ?>">
            <a href="<?= pakpiAdminUrl(['tab' => 'multiyear', 'tahun' => $tahun]) ?>">
                📈 <?= __('Tren Multi-Tahun (3–5 Tahun)') ?>
            </a>
        </li>
        <li class="pakpi-nav-item <?= $tab === 'monthly' ? 'active' : '' ?>">
            <a href="<?= pakpiAdminUrl(['tab' => 'monthly', 'tahun' => $tahun]) ?>">
                📅 <?= __('Distribusi Musiman (Bulanan)') ?>
            </a>
        </li>
        <li class="pakpi-nav-item <?= $tab === 'insights' ? 'active' : '' ?>">
            <a href="<?= pakpiAdminUrl(['tab' => 'insights', 'tahun' => $tahun]) ?>">
                💡 <?= __('Evaluasi &amp; Rekomendasi Mutu') ?>
            </a>
        </li>
        <li class="pakpi-nav-item <?= $tab === 'settings' ? 'active' : '' ?>">
            <a href="<?= pakpiAdminUrl(['tab' => 'settings', 'tahun' => $tahun]) ?>">
                ⚙️ <?= __('Pengaturan Laporan &amp; Penandatangan') ?>
            </a>
        </li>
    </ul>

    <?php if ($tab !== 'settings'): ?>
        <!-- Filter Bar -->
        <div class="pakpi-card pakpi-filter-box non-printable">
            <div class="pakpi-card-body p-3">
                <form method="get" action="<?= pakpiAdminUrl() ?>" class="inline-form submitViaAJAX d-flex align-items-center flex-wrap" style="gap: 12px;">
                    <input type="hidden" name="mod" value="<?= htmlspecialchars($_GET['mod'] ?? 'reporting', ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>" />

                    <div class="d-flex align-items-center">
                        <label class="font-weight-bold mb-0 mr-2 text-dark">📅 <?= __('Tahun Acuan') ?>:</label>
                        <select name="tahun" class="form-control form-select form-control-sm" style="width: 105px;">
                            <?php for ($y = $current_year; $y >= 2015; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <?php if ($tab === 'summary'): ?>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="include_renewal" value="1" id="chkRenewal" <?= $include_renewal ? 'checked' : '' ?>>
                            <label class="form-check-label text-dark small font-weight-bold" for="chkRenewal">
                                <?= __('Termasuk Perpanjangan') ?>
                            </label>
                        </div>

                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="only_active_members" value="1" id="chkActive" <?= $only_active_members ? 'checked' : '' ?>>
                            <label class="form-check-label text-dark small font-weight-bold" for="chkActive">
                                <?= __('Hanya Anggota Aktif') ?>
                            </label>
                        </div>

                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="table_only" value="1" id="chkTableOnly" <?= $table_only ? 'checked' : '' ?>>
                            <label class="form-check-label text-dark small font-weight-bold" for="chkTableOnly">
                                <?= __('Tabel Saja') ?>
                            </label>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-sm px-3 py-1 font-weight-bold">
                        🔍 <?= __('Tampilkan') ?>
                    </button>

                    <!-- Export Excel (.xls) -->
                    <a href="<?= pakpiAdminUrl(['action' => 'export_excel', 'tab' => $tab, 'tahun' => $tahun, 'include_renewal' => $include_renewal ? 1 : 0, 'only_active_members' => $only_active_members ? 1 : 0]) ?>" class="btn btn-success btn-sm px-3 py-1 font-weight-bold notAJAX" target="_blank" title="Unduh Spreadsheet Format Microsoft Excel">
                        📗 <?= __('Ekspor ke Excel') ?>
                    </a>

                    <!-- Export CSV -->
                    <a href="<?= pakpiAdminUrl(['action' => 'export_csv', 'tab' => $tab, 'tahun' => $tahun, 'include_renewal' => $include_renewal ? 1 : 0, 'only_active_members' => $only_active_members ? 1 : 0]) ?>" class="btn btn-outline-success btn-sm px-3 py-1 font-weight-bold notAJAX" target="_blank" title="Unduh Format CSV">
                        📊 <?= __('Ekspor CSV') ?>
                    </a>

                    <!-- Print & PDF View -->
                    <a href="<?= pakpiAdminUrl(['action' => 'print_view', 'tahun' => $tahun, 'include_renewal' => $include_renewal ? 1 : 0, 'only_active_members' => $only_active_members ? 1 : 0]) ?>" target="_blank" class="btn btn-secondary btn-sm px-3 py-1 font-weight-bold notAJAX" title="Cetak Borang Resmi / Simpan sebagai PDF">
                        🖨️ <?= __('Cetak / Simpan ke PDF') ?>
                    </a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 1: DASHBOARD METRIK TAHUNAN                                      -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <?php if ($tab === 'summary'): ?>
        <!-- Master Executive KPI Scorecards -->
        <div class="pakpi-kpi-grid">
            <div class="pakpi-kpi-card" style="border-top: 4px solid #3b82f6;">
                <div class="pakpi-kpi-title">B.2.1.1 Perputaran Koleksi</div>
                <div class="pakpi-kpi-value" style="color: #2563eb;">
                    <?= $dataB211[0]['nilai_thd_eksemplar'] ?? 0 ?>
                </div>
                <div class="pakpi-kpi-unit">kali per eksemplar / tahun</div>
            </div>

            <div class="pakpi-kpi-card" style="border-top: 4px solid #10b981;">
                <div class="pakpi-kpi-title">B.2.1.2 Pinjaman Per Kapita</div>
                <div class="pakpi-kpi-value" style="color: #059669;">
                    <?= $dataB212[0]['nilai'] ?? 0 ?>
                </div>
                <div class="pakpi-kpi-unit">buku per anggota / tahun</div>
            </div>

            <div class="pakpi-kpi-card" style="border-top: 4px solid #f59e0b;">
                <div class="pakpi-kpi-title">B.2.1.3 Pemanfaatan Koleksi</div>
                <div class="pakpi-kpi-value" style="color: #d97706;">
                    <?= $dataB213['pct_digunakan'] ?>%
                </div>
                <div class="pakpi-kpi-unit"><?= $dataB213['persentase_tidak'] ?>% koleksi belum dipinjam</div>
            </div>

            <div class="pakpi-kpi-card" style="border-top: 4px solid #8b5cf6;">
                <div class="pakpi-kpi-title">B.2.2.1 Kunjungan Per Kapita</div>
                <div class="pakpi-kpi-value" style="color: #7c3aed;">
                    <?= $dataB221['nilai'] ?>
                </div>
                <div class="pakpi-kpi-unit">kali kunjungan per anggota / tahun</div>
            </div>
        </div>

        <!-- ── Indikator 1: B.2.1.1 Perputaran Koleksi ── -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">
                    📚 B.2.1.1 - <?= __('Perputaran Koleksi (Collection Turnover Rate)') ?>
                </h5>
                <span class="badge badge-primary bg-primary text-white px-2 py-1">Tahun <?= $tahun ?></span>
            </div>
            <div class="pakpi-card-body">
                <div class="pakpi-desc">
                    <strong>Definisi SNI ISO 2789:2013 (Klausul B.2.1.1):</strong> Jumlah total peminjaman dalam koleksi selama 1 tahun dibagi dengan jumlah total eksemplar/judul. Menilai tingkat intensitas dan perputaran pemanfaatan fisik buku.
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th><?= __('Indikator / Transaksi') ?></th>
                                <th class="text-right"><?= __('Total Transaksi') ?></th>
                                <th class="text-right"><?= __('Total Eksemplar') ?></th>
                                <th class="text-center font-weight-bold"><?= __('Nilai thd Eksemplar') ?></th>
                                <th class="text-right"><?= __('Total Judul') ?></th>
                                <th class="text-center font-weight-bold"><?= __('Nilai thd Judul') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dataB211 as $r): ?>
                                <tr>
                                    <td class="font-weight-bold text-dark"><?= htmlspecialchars($r['indikator'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-right"><?= number_format($r['total']) ?></td>
                                    <td class="text-right"><?= number_format($r['total_eksemplar']) ?></td>
                                    <td class="text-center font-weight-bold text-primary"><?= $r['nilai_thd_eksemplar'] ?></td>
                                    <td class="text-right"><?= number_format($r['total_judul']) ?></td>
                                    <td class="text-center font-weight-bold text-success"><?= $r['nilai_thd_judul'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!$table_only && !empty($dataB211)): 
                    $maxTotal = max(array_column($dataB211, 'total')) ?: 1;
                    $eksemplar = $dataB211[0]['total_eksemplar'];
                    $judul     = $dataB211[0]['total_judul'];
                    $rasio     = $judul > 0 ? round($eksemplar / $judul, 2) : 0;
                ?>
                    <div class="chart-wrapper">
                        <!-- Bar Chart Perbandingan -->
                        <div class="chart-box">
                            <h4>📊 Perputaran Koleksi - Distribusi Transaksi</h4>
                            <div class="bar-chart">
                                <?php foreach ($dataB211 as $item): 
                                    $pct = round(($item['total'] / $maxTotal) * 100);
                                ?>
                                    <div class="bar-item">
                                        <div class="bar-label"><?= htmlspecialchars($item['indikator'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="bar-container">
                                            <div class="bar-fill bg-primary" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                        <div class="bar-value text-primary"><?= number_format($item['total']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Grid Statistik Koleksi & Rasio -->
                        <div class="chart-box">
                            <h4>📚 Statistik Eksemplar &amp; Judul</h4>
                            <div class="comparison-grid">
                                <div class="comparison-item" style="background: #eff6ff; border: 1px solid #bfdbfe;">
                                    <div class="comparison-label">Total Eksemplar</div>
                                    <div class="comparison-value text-primary"><?= number_format($eksemplar) ?></div>
                                    <div class="small text-muted mt-1">Fisik koleksi</div>
                                </div>
                                <div class="comparison-item" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                    <div class="comparison-label">Total Judul</div>
                                    <div class="comparison-value text-success"><?= number_format($judul) ?></div>
                                    <div class="small text-muted mt-1">Koleksi unik</div>
                                </div>
                                <div class="comparison-item" style="background: #fffbeb; border: 1px solid #fde68a;">
                                    <div class="comparison-label">Rasio Eks/Judul</div>
                                    <div class="comparison-value text-warning"><?= $rasio ?></div>
                                    <div class="small text-muted mt-1">Rata-rata eksemplar</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Indikator 2: B.2.1.2 Pinjaman Per Kapita ── -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">
                    👥 B.2.1.2 - <?= __('Pinjaman Per Kapita (Loans per Capita)') ?>
                </h5>
                <span class="badge badge-success bg-success text-white px-2 py-1">Tahun <?= $tahun ?></span>
            </div>
            <div class="pakpi-card-body">
                <div class="pakpi-desc" style="background: #eff6ff; border-left-color: #3b82f6;">
                    <strong>Definisi SNI ISO 2789:2013 (Klausul B.2.1.2):</strong> Jumlah total peminjaman dalam setahun dibagi dengan jumlah populasi anggota yang dilayani. Menilai rata-rata konsumsi bacaan per pemustaka.
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th><?= __('Indikator') ?></th>
                                <th class="text-right"><?= __('Total Pinjaman') ?></th>
                                <th class="text-right"><?= __('Total Populasi Anggota') ?></th>
                                <th class="text-center font-weight-bold"><?= __('Nilai Capaian') ?></th>
                                <th><?= __('Keterangan') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dataB212 as $r): ?>
                                <tr>
                                    <td class="font-weight-bold text-dark"><?= htmlspecialchars($r['indikator'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-right"><?= number_format($r['total_pinjaman']) ?></td>
                                    <td class="text-right"><?= number_format($r['total_populasi']) ?></td>
                                    <td class="text-center font-weight-bold text-primary" style="font-size: 16px;"><?= $r['nilai'] ?></td>
                                    <td class="small text-muted">Rata-rata <strong><?= $r['nilai'] ?></strong> buku dipinjam per anggota</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!$table_only && !empty($dataB212)): 
                    $totPinj = $dataB212[0]['total_pinjaman'];
                    $totPop  = $dataB212[0]['total_populasi'];
                    $maxVal  = max($totPinj, $totPop) ?: 1;
                    $pinjPct = round(($totPinj / $maxVal) * 100);
                    $popPct  = round(($totPop / $maxVal) * 100);
                ?>
                    <div class="chart-wrapper">
                        <!-- Perbandingan Pinjaman vs Populasi -->
                        <div class="chart-box">
                            <h4>👥 Pinjaman vs Populasi Anggota</h4>
                            <div class="comparison-grid mb-3">
                                <div class="comparison-item">
                                    <div class="comparison-label">Total Pinjaman</div>
                                    <div class="comparison-value text-primary"><?= number_format($totPinj) ?></div>
                                </div>
                                <div class="comparison-item">
                                    <div class="comparison-label">Total Populasi</div>
                                    <div class="comparison-value text-secondary"><?= number_format($totPop) ?></div>
                                </div>
                            </div>
                            <div class="bar-chart">
                                <div class="bar-item">
                                    <div class="bar-label">Pinjaman</div>
                                    <div class="bar-container">
                                        <div class="bar-fill bg-primary" style="width: <?= $pinjPct ?>%;"></div>
                                    </div>
                                    <div class="bar-value text-primary"><?= number_format($totPinj) ?></div>
                                </div>
                                <div class="bar-item">
                                    <div class="bar-label">Populasi</div>
                                    <div class="bar-container">
                                        <div class="bar-fill bg-secondary" style="width: <?= $popPct ?>%;"></div>
                                    </div>
                                    <div class="bar-value text-secondary"><?= number_format($totPop) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Big Metric Card Per Kapita -->
                        <div class="chart-box d-flex flex-column justify-content-center text-center">
                            <h4>📈 Nilai Pinjaman Per Kapita</h4>
                            <div class="p-3 my-auto rounded" style="background: #eff6ff; border: 2px solid #bfdbfe;">
                                <div class="text-uppercase small font-weight-bold text-muted mb-2">Rata-rata Pinjaman Tahunan</div>
                                <div class="display-4 font-weight-bold text-primary mb-2" style="font-size: 54px; line-height: 1;">
                                    <?= $dataB212[0]['nilai'] ?>
                                </div>
                                <div>
                                    <span class="badge badge-primary bg-primary text-white px-3 py-2" style="font-size: 13px;">Buku / Anggota / Tahun</span>
                                </div>
                                <div class="small text-muted mt-3">
                                    Setiap anggota rata-rata meminjam <strong><?= $dataB212[0]['nilai'] ?> buku</strong> selama tahun <?= $tahun ?>.
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Indikator 3: B.2.1.3 Persentase Koleksi Tidak Digunakan ── -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">
                    💤 B.2.1.3 - <?= __('Persentase Koleksi Tidak Digunakan (Percentage of Dormant Collection)') ?>
                </h5>
                <span class="badge badge-warning bg-warning text-dark px-2 py-1">Tahun <?= $tahun ?></span>
            </div>
            <div class="pakpi-card-body">
                <div class="pakpi-desc" style="background: #fffbeb; border-left-color: #f59e0b;">
                    <strong>Definisi SNI ISO 2789:2013 (Klausul B.2.1.3):</strong> Persentase dokumen/eksemplar yang tidak pernah dipinjam selama periode tahun tersebut. Menggambarkan efektivitas pengadaan dan relevansi koleksi dengan pemustaka.
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th><?= __('Status Koleksi') ?></th>
                                <th class="text-right"><?= __('Jumlah Eksemplar') ?></th>
                                <th class="text-right"><?= __('Total Koleksi') ?></th>
                                <th class="text-center font-weight-bold"><?= __('Persentase') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-weight-bold text-danger">⚠️ <?= __('Belum Pernah Dipinjam Tahun Ini') ?></td>
                                <td class="text-right"><?= number_format($dataB213['total_tidak']) ?></td>
                                <td class="text-right"><?= number_format($dataB213['total_eksemplar']) ?></td>
                                <td class="text-center font-weight-bold text-danger"><?= $dataB213['persentase_tidak'] ?> %</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-success">✅ <?= __('Telah Dimanfaatkan (Peminjaman Aktif)') ?></td>
                                <td class="text-right"><?= number_format($dataB213['total_digunakan']) ?></td>
                                <td class="text-right"><?= number_format($dataB213['total_eksemplar']) ?></td>
                                <td class="text-center font-weight-bold text-success"><?= $dataB213['pct_digunakan'] ?> %</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if (!$table_only): ?>
                    <div class="chart-wrapper">
                        <!-- Giant Utilization Metric Card -->
                        <div class="chart-box text-center d-flex flex-column justify-content-center">
                            <h4>📊 Tingkat Pemanfaatan Koleksi</h4>
                            <div class="p-3 my-auto rounded" style="background: #f0fdf4; border: 2px solid #bbf7d0;">
                                <div class="text-uppercase small font-weight-bold text-muted mb-2">Persentase Koleksi Aktif</div>
                                <div class="display-4 font-weight-bold text-success mb-2" style="font-size: 56px; line-height: 1;">
                                    <?= $dataB213['pct_digunakan'] ?>%
                                </div>
                                <div>
                                    <span class="badge badge-success bg-success text-white px-3 py-2" style="font-size: 13px;">Koleksi Dimanfaatkan</span>
                                </div>
                                <div class="small text-muted mt-3">
                                    <strong><?= number_format($dataB213['total_digunakan']) ?></strong> dari total <strong><?= number_format($dataB213['total_eksemplar']) ?></strong> eksemplar telah dipinjam.
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Grid Breakdown -->
                        <div class="chart-box">
                            <h4>📈 Rincian Status Eksemplar</h4>
                            <div class="comparison-grid">
                                <div class="comparison-item" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                                    <div class="comparison-label">Eksemplar Aktif</div>
                                    <div class="comparison-value text-success"><?= number_format($dataB213['total_digunakan']) ?></div>
                                    <div class="small text-muted mt-1"><?= $dataB213['pct_digunakan'] ?>% dari koleksi</div>
                                </div>
                                <div class="comparison-item" style="background: #fef2f2; border: 1px solid #fecaca;">
                                    <div class="comparison-label">Koleksi Tidur</div>
                                    <div class="comparison-value text-danger"><?= number_format($dataB213['total_tidak']) ?></div>
                                    <div class="small text-muted mt-1"><?= $dataB213['persentase_tidak'] ?>% belum dipinjam</div>
                                </div>
                                <div class="comparison-item" style="background: #eff6ff; border: 1px solid #bfdbfe;">
                                    <div class="comparison-label">Total Koleksi</div>
                                    <div class="comparison-value text-primary"><?= number_format($dataB213['total_eksemplar']) ?></div>
                                    <div class="small text-muted mt-1">Populasi buku</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Indikator 4: B.2.2.1 Kunjungan Perpustakaan Per Kapita ── -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">
                    🚪 B.2.2.1 - <?= __('Kunjungan Perpustakaan Per Kapita (Library Visits per Capita)') ?>
                </h5>
                <span class="badge badge-info bg-info text-white px-2 py-1">Tahun <?= $tahun ?></span>
            </div>
            <div class="pakpi-card-body">
                <div class="pakpi-desc" style="background: #f5f3ff; border-left-color: #8b5cf6;">
                    <strong>Definisi SNI ISO 2789:2013 (Klausul B.2.2.1):</strong> Jumlah total kehadiran/kunjungan ke perpustakaan dalam setahun dibagi dengan jumlah anggota. Menilai daya tarik ruang dan layanan perpustakaan.
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th><?= __('Indikator') ?></th>
                                <th class="text-right"><?= __('Total Kunjungan') ?></th>
                                <th class="text-right"><?= __('Total Populasi Anggota') ?></th>
                                <th class="text-center font-weight-bold"><?= __('Nilai Capaian') ?></th>
                                <th><?= __('Keterangan') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-weight-bold text-dark"><?= htmlspecialchars($dataB221['indikator'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-right"><?= number_format($dataB221['total_kunjungan']) ?></td>
                                <td class="text-right"><?= number_format($dataB221['total_populasi']) ?></td>
                                <td class="text-center font-weight-bold text-info" style="font-size: 16px;"><?= $dataB221['nilai'] ?></td>
                                <td class="small text-muted">Rata-rata setiap anggota berkunjung <strong><?= $dataB221['nilai'] ?> kali</strong> dalam setahun</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php if (!$table_only): 
                    $totKunj = $dataB221['total_kunjungan'];
                    $totPopV = $dataB221['total_populasi'];
                    $maxKunj = max($totKunj, $totPopV) ?: 1;
                    $kunjPct = round(($totKunj / $maxKunj) * 100);
                    $popVPct = round(($totPopV / $maxKunj) * 100);
                ?>
                    <div class="chart-wrapper">
                        <!-- Perbandingan Kunjungan vs Populasi -->
                        <div class="chart-box">
                            <h4>🚪 Kunjungan vs Populasi Anggota</h4>
                            <div class="comparison-grid mb-3">
                                <div class="comparison-item">
                                    <div class="comparison-label">Total Kunjungan</div>
                                    <div class="comparison-value text-info"><?= number_format($totKunj) ?></div>
                                </div>
                                <div class="comparison-item">
                                    <div class="comparison-label">Total Populasi</div>
                                    <div class="comparison-value text-secondary"><?= number_format($totPopV) ?></div>
                                </div>
                            </div>
                            <div class="bar-chart">
                                <div class="bar-item">
                                    <div class="bar-label">Kunjungan</div>
                                    <div class="bar-container">
                                        <div class="bar-fill bg-info" style="width: <?= $kunjPct ?>%;"></div>
                                    </div>
                                    <div class="bar-value text-info"><?= number_format($totKunj) ?></div>
                                </div>
                                <div class="bar-item">
                                    <div class="bar-label">Populasi</div>
                                    <div class="bar-container">
                                        <div class="bar-fill bg-secondary" style="width: <?= $popVPct ?>%;"></div>
                                    </div>
                                    <div class="bar-value text-secondary"><?= number_format($totPopV) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Giant Metric Card Kunjungan Per Kapita -->
                        <div class="chart-box text-center d-flex flex-column justify-content-center">
                            <h4>📊 Rasio Kunjungan Per Kapita</h4>
                            <div class="p-3 my-auto rounded" style="background: #f5f3ff; border: 2px solid #ddd6fe;">
                                <div class="text-uppercase small font-weight-bold text-muted mb-2">Frekuensi Kunjungan Fisik</div>
                                <div class="display-4 font-weight-bold text-primary mb-2" style="font-size: 56px; line-height: 1; color: #7c3aed !important;">
                                    <?= $dataB221['nilai'] ?>
                                </div>
                                <div>
                                    <span class="badge badge-info bg-info text-white px-3 py-2" style="font-size: 13px;">Kali / Anggota / Tahun</span>
                                </div>
                                <div class="small text-muted mt-3">
                                    Rata-rata setiap anggota hadir <strong><?= $dataB221['nilai'] ?> kali</strong> ke perpustakaan dalam setahun.
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 2: TREN MULTI-TAHUN (3-5 TAHUN)                                  -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <?php elseif ($tab === 'multiyear'): 
        $multiYear = pakpiGetMultiYearTrend($dbs, $tahun, 5);
    ?>
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">
                    📈 <?= __('Perbandingan Kinerja Multi-Tahun (Periode ') . ($tahun - 4) . ' - ' . $tahun . ')' ?>
                </h5>
                <span class="badge badge-primary bg-primary text-white px-2 py-1">Standar Borang Akreditasi</span>
            </div>
            <div class="pakpi-card-body">
                <div class="pakpi-desc">
                    Tabel matriks komparasi kinerja 5 tahun terakhir untuk pelaporan borang Akreditasi Perpustakaan Nasional RI &amp; instrumen akreditasi program studi.
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th class="text-center"><?= __('Tahun') ?></th>
                                <th class="text-center font-weight-bold"><?= __('B.2.1.1 Perputaran (Eks)') ?></th>
                                <th class="text-center font-weight-bold"><?= __('B.2.1.2 Pinjaman/Kapita') ?></th>
                                <th class="text-right"><?= __('Total Pinjaman') ?></th>
                                <th class="text-center font-weight-bold"><?= __('B.2.1.3 Pemanfaatan (%)') ?></th>
                                <th class="text-center font-weight-bold"><?= __('B.2.2.1 Kunjungan/Kapita') ?></th>
                                <th class="text-right"><?= __('Total Kunjungan') ?></th>
                                <th class="text-right"><?= __('Populasi Anggota') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($multiYear as $y => $d): ?>
                                <tr>
                                    <td class="text-center font-weight-bold text-dark" style="font-size: 15px;"><?= $y ?></td>
                                    <td class="text-center font-weight-bold text-primary"><?= $d['perputaran_eks'] ?> kali</td>
                                    <td class="text-center font-weight-bold text-success"><?= $d['pinjaman_kapita'] ?> buku</td>
                                    <td class="text-right"><?= number_format($d['total_pinjaman']) ?></td>
                                    <td class="text-center font-weight-bold text-warning"><?= $d['pct_pemanfaatan'] ?> %</td>
                                    <td class="text-center font-weight-bold text-info"><?= $d['kunjungan_kapita'] ?> kali</td>
                                    <td class="text-right"><?= number_format($d['total_kunjungan']) ?></td>
                                    <td class="text-right"><?= number_format($d['populasi']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 3: DISTRIBUSI BULANAN (MUSIMAN)                                  -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <?php elseif ($tab === 'monthly'): 
        $monthly = pakpiGetMonthlyTrend($dbs, $tahun);
        $maxLoan = max(array_column($monthly, 'loans')) ?: 1;
        $maxVis  = max(array_column($monthly, 'visits')) ?: 1;
    ?>
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">
                    📅 <?= __('Distribusi Aktivitas Bulanan (Tahun ') . $tahun . ')' ?>
                </h5>
                <span class="badge badge-success bg-success text-white px-2 py-1">Pola Musiman</span>
            </div>
            <div class="pakpi-card-body">
                <div class="pakpi-desc">
                    Menganalisis fluktuasi peminjaman dan kehadiran fisik perpustakaan dari bulan Januari hingga Desember untuk evaluasi waktu sibuk (*peak seasons*).
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 15%"><?= __('Bulan') ?></th>
                                <th style="width: 40%"><?= __('Peminjaman Buku (Distribusi)') ?></th>
                                <th style="width: 45%"><?= __('Kunjungan Pemustaka (Distribusi)') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly as $m): 
                                $pctL = round(($m['loans'] / $maxLoan) * 100);
                                $pctV = round(($m['visits'] / $maxVis) * 100);
                            ?>
                                <tr>
                                    <td class="font-weight-bold text-dark"><?= $m['month_name'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="pakpi-bar-container mr-2" style="background: #f1f5f9; border-radius: 6px; height: 26px; position: relative; overflow: hidden; flex: 1;">
                                                <div class="pakpi-bar-fill bg-primary" style="width: <?= $pctL ?>%; height: 100%;"></div>
                                            </div>
                                            <span class="small font-weight-bold text-right" style="min-width: 60px;"><?= number_format($m['loans']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="pakpi-bar-container mr-2" style="background: #f1f5f9; border-radius: 6px; height: 26px; position: relative; overflow: hidden; flex: 1;">
                                                <div class="pakpi-bar-fill bg-success" style="width: <?= $pctV ?>%; height: 100%;"></div>
                                            </div>
                                            <span class="small font-weight-bold text-right" style="min-width: 60px;"><?= number_format($m['visits']) ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 4: EVALUASI & REKOMENDASI MUTU                                   -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <?php elseif ($tab === 'insights'): ?>
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">
                    💡 <?= __('Evaluasi Capaian &amp; Rekomendasi Kebijakan (Actionable Insights)') ?>
                </h5>
                <span class="badge badge-warning bg-warning text-dark px-2 py-1">Tahun <?= $tahun ?></span>
            </div>
            <div class="pakpi-card-body">
                <div class="pakpi-desc">
                    Narasi evaluasi otomatis berdasarkan standar SNI ISO 2789:2013 dan Pedoman Analisis Kinerja Perpustakaan Indonesia untuk mendukung pengambilan keputusan strategis.
                </div>

                <div class="row">
                    <?php foreach ($insights as $ins): 
                        $borderCol = $ins['type'] === 'success' ? '#10b981' : ($ins['type'] === 'warning' ? '#f59e0b' : '#3b82f6');
                        $bgCol     = $ins['type'] === 'success' ? '#f0fdf4' : ($ins['type'] === 'warning' ? '#fffbeb' : '#eff6ff');
                    ?>
                        <div class="col-md-12 mb-3">
                            <div class="p-3 rounded border" style="background: <?= $bgCol ?>; border-left: 6px solid <?= $borderCol ?> !important;">
                                <h6 class="font-weight-bold mb-2 text-dark">
                                    <?= $ins['icon'] ?> <?= htmlspecialchars($ins['title'], ENT_QUOTES, 'UTF-8') ?>
                                </h6>
                                <p class="mb-0 text-dark small" style="line-height: 1.6;">
                                    <?= htmlspecialchars($ins['message'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <!-- ════════════════════════════════════════════════════════════════════ -->
    <!-- TAB 5: PENGATURAN LAPORAN & PENANDATANGAN                            -->
    <!-- ════════════════════════════════════════════════════════════════════ -->
    <?php elseif ($tab === 'settings'): ?>
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">
                    ⚙️ <?= __('Pengaturan Kop Surat &amp; Penandatangan Laporan Cetak') ?>
                </h5>
                <span class="badge badge-secondary bg-secondary text-white px-2 py-1">Kustomisasi Dokumen</span>
            </div>
            <div class="pakpi-card-body">
                <div class="pakpi-desc">
                    Sesuaikan identitas instansi, alamat kop surat, serta daftar pejabat penandatangan laporan (bisa disesuaikan 1 s.d. 4 orang penandatangan: Kepala Perpustakaan, Pustakawan, Wadir/Warek, Dekan, Kepala Sekolah, dll.).
                </div>

                <form method="post" action="<?= pakpiAdminUrl(['tab' => 'settings']) ?>" class="submitViaAJAX">
                    <input type="hidden" name="csrf_token" value="<?= pakpiGetCsrfToken() ?>">
                    <input type="hidden" name="save_settings" value="1">

                    <!-- Kop Surat Identitas -->
                    <h6 class="font-weight-bold text-primary mb-3 border-bottom pb-2">🏢 Identitas Lembaga &amp; Kop Laporan</h6>
                    <div class="row mb-3">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark mb-1">Nama Instansi / Lembaga / Yayasan / Kementerian:</label>
                            <input type="text" name="instansi" class="form-control" value="<?= htmlspecialchars($settings['instansi'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: UNIVERSITAS INDONESIA / KEMENTERIAN PENDIDIKAN" required>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark mb-1">Nama Unit / Gedung Perpustakaan:</label>
                            <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($settings['unit'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: UPT PERPUSTAKAAN DAN ARSIP" required>
                        </div>
                        <div class="col-md-8 form-group mb-3">
                            <label class="font-weight-bold text-dark mb-1">Alamat Lengkap &amp; Kontak Resmi:</label>
                            <input type="text" name="alamat" class="form-control" value="<?= htmlspecialchars($settings['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Jl. Salemba Raya No. 4, Jakarta Pusat - Telp: (021) 1234567 | perpustakaan.ac.id">
                        </div>
                        <div class="col-md-4 form-group mb-3">
                            <label class="font-weight-bold text-dark mb-1">Kota Tempat Pembuatan Laporan:</label>
                            <input type="text" name="kota" class="form-control" value="<?= htmlspecialchars($settings['kota'] ?? 'Jakarta', ENT_QUOTES, 'UTF-8') ?>" placeholder="Contoh: Jakarta / Yogyakarta / Bandung" required>
                        </div>
                    </div>

                    <!-- Daftar Pejabat Penandatangan -->
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h6 class="font-weight-bold text-primary mb-0">✍️ Pejabat Penandatangan Dokumen</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm font-weight-bold" onclick="addSignerRow()">
                            ➕ Tambah Penandatangan
                        </button>
                    </div>

                    <div id="signersContainer">
                        <?php 
                        $signers = $settings['signers'] ?? [];
                        foreach ($signers as $idx => $s): 
                        ?>
                            <div class="signer-row" id="signerRow_<?= $idx ?>">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge badge-secondary bg-secondary text-white">Penandatangan #<span class="signer-index"><?= $idx + 1 ?></span></span>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="removeSignerRow(this)">Hapus</button>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 form-group mb-2">
                                        <label class="small font-weight-bold text-dark">Label Hubungan:</label>
                                        <input type="text" name="signers[<?= $idx ?>][label]" class="form-control form-control-sm" value="<?= htmlspecialchars($s['label'] ?? 'Mengetahui,', ENT_QUOTES, 'UTF-8') ?>" placeholder="Mengetahui / Dibuat Oleh">
                                    </div>
                                    <div class="col-md-3 form-group mb-2">
                                        <label class="small font-weight-bold text-dark">Jabatan Resmi:</label>
                                        <input type="text" name="signers[<?= $idx ?>][jabatan]" class="form-control form-control-sm" value="<?= htmlspecialchars($s['jabatan'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Kepala Perpustakaan" required>
                                    </div>
                                    <div class="col-md-3 form-group mb-2">
                                        <label class="small font-weight-bold text-dark">Nama Lengkap &amp; Gelar:</label>
                                        <input type="text" name="signers[<?= $idx ?>][nama]" class="form-control form-control-sm" value="<?= htmlspecialchars($s['nama'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Dra. Siti Aminah, M.Hum." required>
                                    </div>
                                    <div class="col-md-3 form-group mb-2">
                                        <label class="small font-weight-bold text-dark">NIP / NIDN / NIK:</label>
                                        <input type="text" name="signers[<?= $idx ?>][nip]" class="form-control form-control-sm" value="<?= htmlspecialchars($s['nip'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="19750101 200003 2 001">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4 pt-2">
                        <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm">
                            💾 <?= __('Simpan Pengaturan') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function addSignerRow() {
            var container = document.getElementById('signersContainer');
            var count = container.getElementsByClassName('signer-row').length;
            var newIndex = count;
            
            var div = document.createElement('div');
            div.className = 'signer-row';
            div.id = 'signerRow_' + newIndex;
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge badge-secondary bg-secondary text-white">Penandatangan #<span class="signer-index">${newIndex + 1}</span></span>
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="removeSignerRow(this)">Hapus</button>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group mb-2">
                        <label class="small font-weight-bold text-dark">Label Hubungan:</label>
                        <input type="text" name="signers[${newIndex}][label]" class="form-control form-control-sm" value="Mengetahui," placeholder="Mengetahui / Dibuat Oleh">
                    </div>
                    <div class="col-md-3 form-group mb-2">
                        <label class="small font-weight-bold text-dark">Jabatan Resmi:</label>
                        <input type="text" name="signers[${newIndex}][jabatan]" class="form-control form-control-sm" placeholder="Jabatan Pejabat" required>
                    </div>
                    <div class="col-md-3 form-group mb-2">
                        <label class="small font-weight-bold text-dark">Nama Lengkap &amp; Gelar:</label>
                        <input type="text" name="signers[${newIndex}][nama]" class="form-control form-control-sm" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="col-md-3 form-group mb-2">
                        <label class="small font-weight-bold text-dark">NIP / NIDN / NIK:</label>
                        <input type="text" name="signers[${newIndex}][nip]" class="form-control form-control-sm" placeholder="NIP / NIDN">
                    </div>
                </div>
            `;
            container.appendChild(div);
        }

        function removeSignerRow(btn) {
            var container = document.getElementById('signersContainer');
            var rows = container.getElementsByClassName('signer-row');
            if (rows.length <= 1) {
                alert('Minimal harus ada 1 orang penandatangan!');
                return;
            }
            btn.closest('.signer-row').remove();
            
            // Reindex
            var remaining = container.getElementsByClassName('signer-row');
            for (var i = 0; i < remaining.length; i++) {
                remaining[i].querySelector('.signer-index').innerText = (i + 1);
            }
        }
        </script>
    <?php endif; ?>
</div>
