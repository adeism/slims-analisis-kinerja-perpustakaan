<?php
/**
 * Eksplorasi Analisis Kinerja Perpustakaan Indonesia (PAKPI)
 * 
 * Query eksplorasi mendalam untuk seluruh 4 indikator PAKPI
 */

defined('INDEX_AUTH') OR die('Direct access not allowed');

global $dbs, $sysconf;

// SLiMS Admin Session
require_once SB . 'admin/default/session.inc.php';

// Auth & Privilege Validation
if (!isset($_SESSION['uid']) || empty($_SESSION['uid'])) {
    die('<div class="alert alert-danger m-3">' . __('You are not authorized to view this section') . '</div>');
}

$can_read = utility::havePrivilege('reporting', 'r');
if (!$can_read) {
    die('<div class="alert alert-danger m-3">' . __('You do not have permission to access this module!') . '</div>');
}

require_once __DIR__ . '/helper.php';

// Parameters
$current_year = (int)date('Y');
$tahun        = isset($_GET['tahun']) && is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : $current_year;
$indikator    = $_GET['indikator'] ?? 'b211';
$tahun_pattern = $tahun . '-%';

// ── Export Exploration CSV ──────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'export_eksplorasi_csv') {
    $filename = 'Eksplorasi_' . strtoupper($indikator) . '_' . $tahun . '.csv';
    $headers = [];
    $rows = [];

    if ($indikator === 'b211') {
        $headers = ['Peringkat', 'Judul Buku', 'ID Biblio', 'Total Peminjaman (Tahun ' . $tahun . ')'];
        $stmt = $dbs->prepare("SELECT b.title, b.biblio_id, COUNT(1) AS total FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE l.loan_date LIKE ? GROUP BY b.biblio_id ORDER BY total DESC LIMIT 100");
        $stmt->bind_param('s', $tahun_pattern);
        $stmt->execute();
        $res = $stmt->get_result();
        $rank = 1;
        while ($r = $res->fetch_assoc()) {
            $rows[] = [$rank++, $r['title'], $r['biblio_id'], $r['total']];
        }
    } elseif ($indikator === 'b212') {
        $headers = ['Peringkat', 'ID Anggota', 'Nama Anggota', 'Jenis Keanggotaan', 'Total Peminjaman'];
        $stmt = $dbs->prepare("SELECT m.member_id, m.member_name, mt.member_type_name, COUNT(1) AS total FROM member AS m INNER JOIN loan AS l ON m.member_id=l.member_id INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id WHERE l.loan_date LIKE ? GROUP BY m.member_id ORDER BY total DESC LIMIT 100");
        $stmt->bind_param('s', $tahun_pattern);
        $stmt->execute();
        $res = $stmt->get_result();
        $rank = 1;
        while ($r = $res->fetch_assoc()) {
            $rows[] = [$rank++, $r['member_id'], $r['member_name'], $r['member_type_name'], $r['total']];
        }
    } elseif ($indikator === 'b213') {
        $headers = ['Peringkat', 'Kode Eksemplar', 'Judul Buku', 'Tahun Pengadaan', 'Status'];
        $next_year = ($tahun + 1) . '-01-01';
        $stmt = $dbs->prepare("SELECT i.item_code, b.title, b.input_date FROM item AS i INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE i.item_code NOT IN (SELECT DISTINCT l.item_code FROM loan AS l WHERE l.loan_date LIKE ?) AND b.input_date < ? ORDER BY b.input_date ASC LIMIT 100");
        $stmt->bind_param('ss', $tahun_pattern, $next_year);
        $stmt->execute();
        $res = $stmt->get_result();
        $rank = 1;
        while ($r = $res->fetch_assoc()) {
            $rows[] = [$rank++, $r['item_code'], $r['title'], $r['input_date'], 'Belum Pernah Dipinjam'];
        }
    } elseif ($indikator === 'b221') {
        $headers = ['Peringkat', 'ID Anggota / Pengunjung', 'Nama Pengunjung', 'Total Kunjungan (Tahun ' . $tahun . ')'];
        $stmt = $dbs->prepare("SELECT vc.member_id, vc.member_name, COUNT(1) AS total FROM visitor_count AS vc WHERE vc.checkin_date LIKE ? GROUP BY vc.member_id, vc.member_name ORDER BY total DESC LIMIT 100");
        $stmt->bind_param('s', $tahun_pattern);
        $stmt->execute();
        $res = $stmt->get_result();
        $rank = 1;
        while ($r = $res->fetch_assoc()) {
            $rows[] = [$rank++, $r['member_id'] ?: '-', $r['member_name'] ?: 'Tamu/Pengunjung Umum', $r['total']];
        }
    }

    pakpiExportCsv($filename, $headers, $rows);
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
.pakpi-rank-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-align: center;
    min-width: 32px;
}
.pakpi-rank-top {
    background: #f59e0b;
    color: #ffffff;
}
.pakpi-rank-normal {
    background: #e2e8f0;
    color: #475569;
}
</style>

<div class="menuBox mb-3 non-printable">
    <div class="menuBoxInner reportIcon">
        <div class="per_title">
            <h2>🔍 <?= __('Eksplorasi Data Analisis Kinerja Perpustakaan') ?></h2>
        </div>
        <div class="sub_section">
            <div class="text-muted small">
                <?= __('Pemeriksaan data analitik mendalam per indikator (Peringkat peminjaman, topik populer, pengunjung aktif, dan koleksi tidur).') ?>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-0">
    <!-- Filter Control -->
    <div class="pakpi-card non-printable">
        <div class="pakpi-card-body p-3">
            <form method="get" action="<?= pakpiAdminUrl() ?>" class="submitViaAJAX d-flex align-items-center flex-wrap" style="gap: 15px;">
                <input type="hidden" name="mod" value="<?= htmlspecialchars($_GET['mod'] ?? 'reporting', ENT_QUOTES, 'UTF-8') ?>" />
                <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" />

                <div class="d-flex align-items-center">
                    <label class="font-weight-bold mb-0 mr-2 text-dark">📅 <?= __('Tahun') ?>:</label>
                    <select name="tahun" class="form-control form-select form-control-sm" style="width: 110px;">
                        <?php for ($y = $current_year; $y >= 2015; $y--): ?>
                            <option value="<?= $y ?>" <?= $y === $tahun ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="d-flex align-items-center">
                    <label class="font-weight-bold mb-0 mr-2 text-dark">📊 <?= __('Indikator') ?>:</label>
                    <select name="indikator" class="form-control form-select form-control-sm" style="min-width: 280px;">
                        <option value="b211" <?= $indikator === 'b211' ? 'selected' : '' ?>>B.2.1.1 Perputaran Koleksi (Buku &amp; Subyek Terpopuler)</option>
                        <option value="b212" <?= $indikator === 'b212' ? 'selected' : '' ?>>B.2.1.2 Pinjaman Per Kapita (Anggota &amp; Jenis Teraktif)</option>
                        <option value="b213" <?= $indikator === 'b213' ? 'selected' : '' ?>>B.2.1.3 Persentase Koleksi Tidak Digunakan (Koleksi Tidur)</option>
                        <option value="b221" <?= $indikator === 'b221' ? 'selected' : '' ?>>B.2.2.1 Kunjungan Per Kapita (Pengunjung Teraktif)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-sm px-3 py-1 font-weight-bold">
                    🚀 <?= __('Tampilkan Eksplorasi') ?>
                </button>

                <a href="<?= pakpiAdminUrl(['action' => 'export_eksplorasi_csv', 'tahun' => $tahun, 'indikator' => $indikator]) ?>" class="btn btn-success btn-sm px-3 py-1 font-weight-bold notAJAX" target="_blank">
                    📊 <?= __('Ekspor Data ke CSV') ?>
                </a>

                <button type="button" class="btn btn-secondary btn-sm px-3 py-1 font-weight-bold" onclick="window.print()">
                    🖨️ <?= __('Cetak') ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ── EKSPLORASI B.2.1.1 ── -->
    <?php if ($indikator === 'b211'): ?>
        <!-- Top 30 Buku Paling Banyak Dipinjam -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">📚 <?= __('Top 30 Judul Buku Paling Banyak Dipinjam (Tahun ') . $tahun . ')' ?></h5>
            </div>
            <div class="pakpi-card-body p-0">
                <?php
                $stmt = $dbs->prepare("SELECT b.title, b.biblio_id, COUNT(1) AS total FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE l.loan_date LIKE ? GROUP BY b.biblio_id ORDER BY total DESC LIMIT 30");
                $stmt->bind_param('s', $tahun_pattern);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 8%" class="text-center">#</th>
                                    <th><?= __('Judul Buku / Bibliografi') ?></th>
                                    <th style="width: 15%"><?= __('ID Biblio') ?></th>
                                    <th style="width: 20%" class="text-right"><?= __('Frekuensi Peminjaman') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while ($r = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="pakpi-rank-badge <?= $rank <= 3 ? 'pakpi-rank-top' : 'pakpi-rank-normal' ?>">#<?= $rank ?></span>
                                        </td>
                                        <td class="font-weight-bold text-dark"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><code><?= htmlspecialchars($r['biblio_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td class="text-right font-weight-bold text-primary"><?= number_format($r['total']) ?> kali</td>
                                    </tr>
                                <?php $rank++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted"><?= __('Tidak ada data peminjaman buku pada tahun ') . $tahun ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top 30 Subyek / Topik Terpopuler -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">📑 <?= __('Top 30 Subyek / Topik Paling Banyak Dipinjam (Tahun ') . $tahun . ')' ?></h5>
            </div>
            <div class="pakpi-card-body p-0">
                <?php
                $stmt = $dbs->prepare("SELECT t.topic, t.topic_id, COUNT(1) AS total FROM mst_topic AS t INNER JOIN biblio_topic AS bt ON t.topic_id=bt.topic_id INNER JOIN biblio AS b ON bt.biblio_id=b.biblio_id INNER JOIN item AS i ON b.biblio_id=i.biblio_id INNER JOIN loan AS l ON i.item_code=l.item_code WHERE l.loan_date LIKE ? GROUP BY t.topic_id ORDER BY total DESC LIMIT 30");
                $stmt->bind_param('s', $tahun_pattern);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 8%" class="text-center">#</th>
                                    <th><?= __('Nama Subyek / Topik') ?></th>
                                    <th style="width: 15%"><?= __('ID Topik') ?></th>
                                    <th style="width: 20%" class="text-right"><?= __('Total Peminjaman') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while ($r = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="pakpi-rank-badge <?= $rank <= 3 ? 'pakpi-rank-top' : 'pakpi-rank-normal' ?>">#<?= $rank ?></span>
                                        </td>
                                        <td class="font-weight-bold text-dark"><?= htmlspecialchars($r['topic'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><code><?= htmlspecialchars($r['topic_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td class="text-right font-weight-bold text-success"><?= number_format($r['total']) ?> kali</td>
                                    </tr>
                                <?php $rank++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted"><?= __('Tidak ada data subyek peminjaman pada tahun ') . $tahun ?></div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ── EKSPLORASI B.2.1.2 ── -->
    <?php elseif ($indikator === 'b212'): ?>
        <!-- Top 30 Anggota Paling Banyak Meminjam -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">👥 <?= __('Top 30 Anggota Pemustaka Paling Aktif Meminjam (Tahun ') . $tahun . ')' ?></h5>
            </div>
            <div class="pakpi-card-body p-0">
                <?php
                $stmt = $dbs->prepare("SELECT m.member_id, m.member_name, mt.member_type_name, COUNT(1) AS total FROM member AS m INNER JOIN loan AS l ON m.member_id=l.member_id INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id WHERE l.loan_date LIKE ? GROUP BY m.member_id ORDER BY total DESC LIMIT 30");
                $stmt->bind_param('s', $tahun_pattern);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 8%" class="text-center">#</th>
                                    <th style="width: 20%"><?= __('ID Anggota') ?></th>
                                    <th><?= __('Nama Anggota') ?></th>
                                    <th style="width: 20%"><?= __('Tipe Keanggotaan') ?></th>
                                    <th style="width: 15%" class="text-right"><?= __('Total Pinjaman') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while ($r = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="pakpi-rank-badge <?= $rank <= 3 ? 'pakpi-rank-top' : 'pakpi-rank-normal' ?>">#<?= $rank ?></span>
                                        </td>
                                        <td><code><?= htmlspecialchars($r['member_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td class="font-weight-bold text-dark"><?= htmlspecialchars($r['member_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge badge-secondary bg-secondary text-white"><?= htmlspecialchars($r['member_type_name'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-right font-weight-bold text-primary"><?= number_format($r['total']) ?> buku</td>
                                    </tr>
                                <?php $rank++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted"><?= __('Tidak ada data peminjaman anggota pada tahun ') . $tahun ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Peminjaman per Jenis Keanggotaan -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">🏷️ <?= __('Statistik Peminjaman per Tipe Keanggotaan (Tahun ') . $tahun . ')' ?></h5>
            </div>
            <div class="pakpi-card-body p-0">
                <?php
                $stmt = $dbs->prepare("SELECT mt.member_type_name, mt.member_type_id, COUNT(1) AS total FROM mst_member_type AS mt INNER JOIN member AS m ON mt.member_type_id=m.member_type_id INNER JOIN loan AS l ON m.member_id=l.member_id INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE l.loan_date LIKE ? GROUP BY mt.member_type_id ORDER BY total DESC");
                $stmt->bind_param('s', $tahun_pattern);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 8%" class="text-center">#</th>
                                    <th><?= __('Tipe Keanggotaan') ?></th>
                                    <th style="width: 15%"><?= __('ID Tipe') ?></th>
                                    <th style="width: 20%" class="text-right"><?= __('Total Peminjaman') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while ($r = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center"><span class="pakpi-rank-badge pakpi-rank-normal">#<?= $rank ?></span></td>
                                        <td class="font-weight-bold text-dark"><?= htmlspecialchars($r['member_type_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><code><?= htmlspecialchars($r['member_type_id'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td class="text-right font-weight-bold text-success"><?= number_format($r['total']) ?> buku</td>
                                    </tr>
                                <?php $rank++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ── EKSPLORASI B.2.1.3 ── -->
    <?php elseif ($indikator === 'b213'): ?>
        <!-- Top 30 Koleksi Tidur / Tidak Pernah Dipinjam -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">💤 <?= __('Top 30 Judul Koleksi Tidur / Belum Pernah Dipinjam (Tahun ') . $tahun . ')' ?></h5>
            </div>
            <div class="pakpi-card-body p-0">
                <?php
                $next_year = ($tahun + 1) . '-01-01';
                $stmt = $dbs->prepare("SELECT i.item_code, b.title, b.input_date FROM item AS i INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE i.item_code NOT IN (SELECT DISTINCT l.item_code FROM loan AS l WHERE l.loan_date LIKE ?) AND b.input_date < ? ORDER BY b.input_date ASC LIMIT 30");
                $stmt->bind_param('ss', $tahun_pattern, $next_year);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 8%" class="text-center">#</th>
                                    <th style="width: 20%"><?= __('Kode Eksemplar') ?></th>
                                    <th><?= __('Judul Buku') ?></th>
                                    <th style="width: 20%"><?= __('Tanggal Masuk Koleksi') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while ($r = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center"><span class="pakpi-rank-badge pakpi-rank-normal">#<?= $rank ?></span></td>
                                        <td><code><?= htmlspecialchars($r['item_code'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td class="font-weight-bold text-dark"><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($r['input_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php $rank++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted"><?= __('Seluruh koleksi aktif dipinjam pada tahun ') . $tahun ?></div>
                <?php endif; ?>
            </div>
        </div>

    <!-- ── EKSPLORASI B.2.2.1 ── -->
    <?php elseif ($indikator === 'b221'): ?>
        <!-- Top 30 Pengunjung Teraktif -->
        <div class="pakpi-card">
            <div class="pakpi-card-header">
                <h5 class="mb-0 font-weight-bold text-dark">🚪 <?= __('Top 30 Pengunjung Teraktif ke Perpustakaan (Tahun ') . $tahun . ')' ?></h5>
            </div>
            <div class="pakpi-card-body p-0">
                <?php
                $stmt = $dbs->prepare("SELECT vc.member_id, vc.member_name, COUNT(1) AS total FROM visitor_count AS vc WHERE vc.checkin_date LIKE ? GROUP BY vc.member_id, vc.member_name ORDER BY total DESC LIMIT 30");
                $stmt->bind_param('s', $tahun_pattern);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 8%" class="text-center">#</th>
                                    <th style="width: 20%"><?= __('ID Anggota') ?></th>
                                    <th><?= __('Nama Pengunjung') ?></th>
                                    <th style="width: 20%" class="text-right"><?= __('Total Kehadiran') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while ($r = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td class="text-center">
                                            <span class="pakpi-rank-badge <?= $rank <= 3 ? 'pakpi-rank-top' : 'pakpi-rank-normal' ?>">#<?= $rank ?></span>
                                        </td>
                                        <td><code><?= htmlspecialchars($r['member_id'] ?: '-', ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td class="font-weight-bold text-dark"><?= htmlspecialchars($r['member_name'] ?: 'Pengunjung Umum', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-right font-weight-bold text-primary"><?= number_format($r['total']) ?> kali hadir</td>
                                    </tr>
                                <?php $rank++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted"><?= __('Tidak ada data kunjungan pada tahun ') . $tahun ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
