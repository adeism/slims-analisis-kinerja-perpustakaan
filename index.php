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

// Handle CSV Export
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
    } else {
        $dataB211 = pakpiGetB211($dbs, $tahun, $include_renewal);
        $dataB212 = pakpiGetB212($dbs, $tahun, $include_renewal, $only_active_members);
        $dataB213 = pakpiGetB213($dbs, $tahun);
        $dataB221 = pakpiGetB221($dbs, $tahun, $only_active_members);

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

// Calculate Indicator Data
$settings = pakpiLoadSettings();
$dataB211 = pakpiGetB211($dbs, $tahun, $include_renewal);
$dataB212 = pakpiGetB212($dbs, $tahun, $include_renewal, $only_active_members);
$dataB213 = pakpiGetB213($dbs, $tahun);
$dataB221 = pakpiGetB221($dbs, $tahun, $only_active_members);
$insights = pakpiGenerateInsights($dataB211, $dataB212, $dataB213, $dataB221);
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
                <form method="get" action="<?= pakpiAdminUrl() ?>" class="inline-form submitViaAJAX d-flex align-items-center flex-wrap" style="gap: 15px;">
                    <input type="hidden" name="mod" value="<?= htmlspecialchars($_GET['mod'] ?? 'reporting', ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>" />

                    <div class="d-flex align-items-center">
                        <label class="font-weight-bold mb-0 mr-2 text-dark">📅 <?= __('Tahun Acuan') ?>:</label>
                        <select name="tahun" class="form-control form-select form-control-sm" style="width: 110px;">
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

                    <a href="<?= pakpiAdminUrl(['action' => 'export_csv', 'tab' => $tab, 'tahun' => $tahun, 'include_renewal' => $include_renewal ? 1 : 0, 'only_active_members' => $only_active_members ? 1 : 0]) ?>" class="btn btn-success btn-sm px-3 py-1 font-weight-bold notAJAX" target="_blank">
                        📊 <?= __('Ekspor ke CSV') ?>
                    </a>

                    <a href="<?= pakpiAdminUrl(['action' => 'print_view', 'tahun' => $tahun, 'include_renewal' => $include_renewal ? 1 : 0, 'only_active_members' => $only_active_members ? 1 : 0]) ?>" target="_blank" class="btn btn-secondary btn-sm px-3 py-1 font-weight-bold notAJAX">
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

        <!-- Indikator 1: B.2.1.1 Perputaran Koleksi -->
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
                    $eksemplar = $dataB211[0]['total_eksemplar'];
                    $judul     = $dataB211[0]['total_judul'];
                    $rasio     = $judul > 0 ? round($eksemplar / $judul, 2) : 0;
                ?>
                    <div class="row mt-3">
                        <div class="col-md-4 mb-2">
                            <div class="p-3 bg-light rounded text-center border">
                                <div class="text-muted small font-weight-bold"><?= __('TOTAL EKSEMPLAR') ?></div>
                                <div class="h3 font-weight-bold text-primary mb-0 mt-1"><?= number_format($eksemplar) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="p-3 bg-light rounded text-center border">
                                <div class="text-muted small font-weight-bold"><?= __('TOTAL JUDUL') ?></div>
                                <div class="h3 font-weight-bold text-success mb-0 mt-1"><?= number_format($judul) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="p-3 bg-light rounded text-center border">
                                <div class="text-muted small font-weight-bold"><?= __('RASIO EKSEMPLAR / JUDUL') ?></div>
                                <div class="h3 font-weight-bold text-warning mb-0 mt-1"><?= $rasio ?> <span class="small text-muted font-weight-normal">eks/judul</span></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Indikator 2: B.2.1.2 Pinjaman Per Kapita -->
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
            </div>
        </div>

        <!-- Indikator 3: B.2.1.3 Persentase Koleksi Tidak Digunakan -->
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
            </div>
        </div>

        <!-- Indikator 4: B.2.2.1 Kunjungan Perpustakaan Per Kapita -->
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

                <div class="table-responsive mb-0">
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
