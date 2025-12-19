<?php

/**
 * File: index.php
 * Main interface untuk plugin Analisis Kinerja Perpustakaan
 * Single page dengan filter form terintegrasi
 */

// ===== SECURITY LAYER =====
if (!defined('INDEX_AUTH')) {
    define('INDEX_AUTH', '1');
}
global $dbs, $sysconf;
require SB . 'admin/default/session.inc.php';
require SB . 'admin/default/session_check.inc.php';

// Check privileges
$can_read = utility::havePrivilege('reporting', 'r');
if (!$can_read) {
    die('<div class="errorBox">' . __('You do not have permission!') . '</div>');
}

// Get filter parameters
$tahun = (int)($_GET['tahun'] ?? date('Y'));
$include_renewal = isset($_GET['include_renewal']) && $_GET['include_renewal'] == '1';
$only_active_members = isset($_GET['only_active_members']) && $_GET['only_active_members'] == '1';
$table_only = isset($_GET['table_only']) && $_GET['table_only'] == '1';
$tahun_pattern = $tahun . '-%';

include __DIR__ . '/assets/style.css.php';
?>
<div class="menuBox">
    <div class="menuBoxInner reportIcon">
        <div class="filter-form-wrapper">
            <!-- FILTER FORM -->
            <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="inline-form">
                <input type="hidden" name="mod" value="<?= $_GET['mod'] ?? '' ?>" />
                <input type="hidden" name="id" value="<?= $_GET['id'] ?? '' ?>" />

                <label><?php echo __('Tahun'); ?>:
                    <select name="tahun" class="form-control">
                        <?php
                        $current_year = date('Y');
                        $selected_year = $_GET['tahun'] ?? $current_year;
                        for ($year = $current_year; $year >= 2015; $year--) {
                            $selected = ($year == $selected_year) ? 'selected' : '';
                            echo "<option value='$year' $selected>$year</option>";
                        }
                        ?>
                    </select>
                </label>

                <label>
                    <input type="checkbox" name="include_renewal" value="1"
                        <?= isset($_GET['include_renewal']) ? 'checked' : '' ?>>
                    Termasuk Perpanjangan
                </label>

                <label>
                    <input type="checkbox" name="only_active_members" value="1"
                        <?= isset($_GET['only_active_members']) ? 'checked' : '' ?>>
                    Hanya Anggota Aktif
                </label>

                <label>
                    <input type="checkbox" name="table_only" value="1"
                        <?= isset($_GET['table_only']) ? 'checked' : '' ?>>
                    Hanya Tabel
                </label>

                <button type="submit" class="s-btn btn btn-primary">
                    <?php echo __('Tampilkan Laporan'); ?>
                </button>

                <button type="button" class="s-btn btn btn-default" onclick="toggleInfo()">
                    ℹ️ Info
                </button>
            </form>
        </div>

        <!-- INFO POPUP -->
        <div class="popup-overlay" id="popupOverlay" onclick="toggleInfo()"></div>
        <div class="info-popup" id="infoPopup">
            <button class="popup-close" onclick="toggleInfo()">&times;</button>
            <h3 style="margin-top:0; color: #4A90E2;">📊 Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI)</h3>
            <p>Plugin ini menampilkan 4 indikator kinerja perpustakaan berdasarkan standar <strong>SNI ISO 2789:2013</strong></p>
            <ul style="line-height: 1.8;">
                <li><strong>B.2.1.1</strong> - Perputaran Koleksi</li>
                <li><strong>B.2.1.2</strong> - Pinjaman Per Kapita</li>
                <li><strong>B.2.1.3</strong> - Persentase Koleksi Tidak Digunakan</li>
                <li><strong>B.2.2.1</strong> - Kunjungan Perpustakaan Per Kapita</li>
            </ul>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #cbd5e1;">
            <h4 style="margin: 10px 0; color: #4A90E2;">📚 Referensi</h4>
            <ul style="line-height: 1.8;">
                <li>Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI) 2021</li>
                <li>SNI ISO 2789:2013 - Informasi dan dokumentasi - Statistik perpustakaan internasional</li>
                <li>Repository: <a href="https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/" target="_blank" style="color: #0369a1; text-decoration: none;">
                        gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia</a></li>
            </ul>
        </div>

        <script>
            function toggleInfo() {
                document.getElementById('infoPopup').classList.toggle('active');
                document.getElementById('popupOverlay').classList.toggle('active');
            }
        </script>

        <div class="container">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">Analisis Kinerja Perpustakaan - Tahun <?= $tahun ?></h2>
                <button class="btn-print non-printable" onclick="window.print()">
                    🖨️ Cetak Halaman Ini
                </button>
            </div>

            <?php if ($include_renewal): ?>
                <div class="info-box non-printable">
                    <div>
                        <strong>Catatan:</strong> Laporan ini <strong>termasuk</strong> data perpanjangan koleksi.
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // Initialize summary data storage
            $summary_data = [];
            ?>

            <?php
            // ===== B.2.1.1 - PERPUTARAN KOLEKSI =====
            echo '<div class="indicator-box">';
            echo '<div class="indicator-title">B.2.1.1 - Perputaran Koleksi</div>';
            echo '<div class="indicator-desc">';
            echo 'Jumlah total pinjaman dalam koleksi yang ditentukan selama periode waktu tertentu, ';
            echo 'dibagi dengan jumlah total dokumen dalam koleksi. Indikator ini menilai tingkat keseluruhan ';
            echo 'penggunaan peminjaman koleksi.';
            echo '</div>';

            if (!$include_renewal) {
                // Query tanpa perpanjangan (MySQL 5.6 compatible)
                $sql_b211 = <<<SQL
SELECT
    'Peminjaman' AS Indikator
    , hp.TotalPinjaman AS Total
    , he.TotalEksemplar AS TotalEksemplar
    , ROUND((hp.TotalPinjaman / he.TotalEksemplar), 2) AS NilaiThdEksemplar
    , hj.TotalJudul AS TotalJudul
    , ROUND((hp.TotalPinjaman / hj.TotalJudul), 2) AS NilaiThdJudul
FROM 
    (SELECT COUNT(1) AS TotalPinjaman
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?) AS hp
CROSS JOIN
    (SELECT COUNT(1) AS TotalEksemplar
     FROM item AS i 
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id) AS he
CROSS JOIN
    (SELECT COUNT(1) AS TotalJudul
     FROM biblio AS b 
     WHERE b.biblio_id IN (
         SELECT DISTINCT i.biblio_id
         FROM item AS i
     )) AS hj
SQL;
            } else {
                // Query dengan perpanjangan (MySQL 5.6 compatible)
                $sql_b211 = <<<SQL
SELECT
    'Peminjaman' AS Indikator
    , hp.TotalPinjaman AS Total
    , he.TotalEksemplar AS TotalEksemplar
    , ROUND((hp.TotalPinjaman / he.TotalEksemplar), 2) AS NilaiThdEksemplar
    , hj.TotalJudul AS TotalJudul
    , ROUND((hp.TotalPinjaman / hj.TotalJudul), 2) AS NilaiThdJudul
FROM 
    (SELECT COUNT(1) AS TotalPinjaman
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?) AS hp
CROSS JOIN
    (SELECT COUNT(1) AS TotalEksemplar
     FROM item AS i 
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id) AS he
CROSS JOIN
    (SELECT COUNT(1) AS TotalJudul
     FROM biblio AS b 
     WHERE b.biblio_id IN (
         SELECT DISTINCT i.biblio_id
         FROM item AS i
     )) AS hj
UNION ALL
SELECT
    'Perpanjangan' AS Indikator
    , hpr.TotalPerpanjangan AS Total
    , he.TotalEksemplar AS TotalEksemplar
    , ROUND((hpr.TotalPerpanjangan / he.TotalEksemplar), 2) AS NilaiThdEksemplar
    , hj.TotalJudul AS TotalJudul
    , ROUND((hpr.TotalPerpanjangan / hj.TotalJudul), 2) AS NilaiThdJudul
FROM 
    (SELECT COUNT(1) AS TotalPerpanjangan
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?
         AND l.renewed > 0) AS hpr
CROSS JOIN
    (SELECT COUNT(1) AS TotalEksemplar
     FROM item AS i 
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id) AS he
CROSS JOIN
    (SELECT COUNT(1) AS TotalJudul
     FROM biblio AS b 
     WHERE b.biblio_id IN (
         SELECT DISTINCT i.biblio_id
         FROM item AS i
     )) AS hj
SQL;
            }

            $stmt_b211 = $dbs->prepare($sql_b211);
            if (!$include_renewal) {
                $stmt_b211->bind_param('s', $tahun_pattern);
            } else {
                $stmt_b211->bind_param('ss', $tahun_pattern, $tahun_pattern);
            }
            $stmt_b211->execute();
            $result_b211 = $stmt_b211->get_result();

            if ($result_b211->num_rows > 0) {
                $data_b211 = [];
                echo '<table>';
                echo '<thead><tr>';
                echo '<th>Indikator</th>';
                echo '<th>Total</th>';
                echo '<th>Total Eksemplar</th>';
                echo '<th>Nilai thd Eksemplar</th>';
                echo '<th>Total Judul</th>';
                echo '<th>Nilai thd Judul</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                while ($row = $result_b211->fetch_assoc()) {
                    $data_b211[] = $row;
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['Indikator']) . '</td>';
                    echo '<td>' . number_format($row['Total'], 0, ',', '.') . '</td>';
                    echo '<td>' . number_format($row['TotalEksemplar'], 0, ',', '.') . '</td>';
                    echo '<td><strong>' . number_format($row['NilaiThdEksemplar'], 2, ',', '.') . '</strong></td>';
                    echo '<td>' . number_format($row['TotalJudul'], 0, ',', '.') . '</td>';
                    echo '<td><strong>' . number_format($row['NilaiThdJudul'], 2, ',', '.') . '</strong></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';

                // Store for summary
                if (!empty($data_b211)) {
                    $summary_data['b211'] = $data_b211[0];
                }

                // CSS Bar Chart visualization (skip if table_only mode)
                if (!empty($data_b211) && !$table_only) {
                    $max_total = max(array_column($data_b211, 'Total'));
                    echo '<div class="chart-wrapper">';
                    echo '<div class="chart-box">';
                    echo '<h4>📊 Perputaran Koleksi - Perbandingan</h4>';
                    echo '<div class="bar-chart">';
                    foreach ($data_b211 as $item) {
                        $percentage = ($item['Total'] / $max_total) * 100;
                        echo '<div class="bar-item">';
                        echo '<div class="bar-label">' . htmlspecialchars($item['Indikator']) . '</div>';
                        echo '<div class="bar-container">';
                        echo '<div class="bar-fill" style="width: ' . $percentage . '%;"></div>';
                        echo '</div>';
                        echo '<div class="bar-value">' . number_format($item['Total'], 0, ',', '.') . '</div>';
                        echo '</div>';
                    }
                    echo '</div></div>';

                    // Info box: Rasio Eksemplar per Judul
                    $eksemplar = $data_b211[0]['TotalEksemplar'];
                    $judul = $data_b211[0]['TotalJudul'];
                    $rasio = $judul > 0 ? floor($eksemplar / $judul) : 0;

                    echo '<div class="chart-box">';
                    echo '<h4>📚 Statistik Koleksi</h4>';
                    echo '<div class="comparison-grid">';
                    echo '<div class="comparison-item">';
                    echo '<div class="comparison-label">Total Eksemplar</div>';
                    echo '<div class="comparison-value" style="color: #4A90E2;">' . number_format($eksemplar, 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '<div class="comparison-item">';
                    echo '<div class="comparison-label">Total Judul</div>';
                    echo '<div class="comparison-value" style="color: #10b981;">' . number_format($judul, 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '<div class="comparison-item">';
                    echo '<div class="comparison-label">Rasio Eksemplar per Judul</div>';
                    echo '<div class="comparison-value" style="color: #f59e0b; font-size: 32px;">' . $rasio . '<span style="font-size: 16px; color: #666;"> eks/judul</span></div>';
                    echo '<div style="margin-top: 8px; font-size: 14px; color: #666; text-align: center;">Setiap judul rata-rata memiliki ' . $rasio . ' eksemplar</div>';
                    echo '</div>';
                    echo '</div></div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="error-box">Tidak ada data untuk tahun ' . $tahun . '</div>';
            }
            echo '</div>';

            // ===== B.2.1.2 - PINJAMAN PER KAPITA =====
            echo '<div class="indicator-box">';
            echo '<div class="indicator-title">B.2.1.2 - Pinjaman Per Kapita</div>';
            echo '<div class="indicator-desc">';
            echo 'Jumlah total pinjaman dalam setahun dibagi dengan populasi yang akan dilayani. ';
            echo 'Indikator ini menilai tingkat penggunaan koleksi perpustakaan oleh populasi yang akan dilayani.';
            echo '</div>';

            $start_date = $tahun . '-01-01';
            $end_date = $tahun . '-12-31';

            if (!$include_renewal) {
                if (!$only_active_members) {
                    // Semua anggota terdaftar (MySQL 5.6 compatible)
                    $sql_b212 = <<<SQL
SELECT 
    'Peminjaman' AS Indikator
    , hp.TotalPinjaman AS TotalPinjaman
    , hpo.TotalPopulasi AS TotalPopulasi
    , ROUND((hp.TotalPinjaman / hpo.TotalPopulasi), 2) AS Nilai
FROM 
    (SELECT COUNT(1) AS TotalPinjaman
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?) AS hp
CROSS JOIN
    (SELECT COUNT(1) AS TotalPopulasi
     FROM member AS m 
     INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
     WHERE m.member_since_date < ?
         AND expire_date > ?) AS hpo
SQL;
                    $stmt_b212 = $dbs->prepare($sql_b212);
                    $prev_year_end = ($tahun - 1) . '-12-31';
                    $stmt_b212->bind_param('sss', $tahun_pattern, $start_date, $prev_year_end);
                } else {
                    // Hanya anggota yang pernah meminjam (MySQL 5.6 compatible)
                    $sql_b212 = <<<SQL
SELECT 
    'Peminjaman' AS Indikator
    , hp.TotalPinjaman AS TotalPinjaman
    , hpo.TotalPopulasi AS TotalPopulasi
    , ROUND((hp.TotalPinjaman / hpo.TotalPopulasi), 2) AS Nilai
FROM 
    (SELECT COUNT(1) AS TotalPinjaman
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?) AS hp
CROSS JOIN
    (SELECT COUNT(DISTINCT m.member_id) AS TotalPopulasi
     FROM member AS m 
     INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
     INNER JOIN loan AS l ON m.member_id=l.member_id
     WHERE m.member_since_date < ?
         AND m.expire_date > ?
         AND l.loan_date LIKE ?) AS hpo
SQL;
                    $stmt_b212 = $dbs->prepare($sql_b212);
                    $prev_year_end = ($tahun - 1) . '-12-31';
                    $stmt_b212->bind_param('ssss', $tahun_pattern, $start_date, $prev_year_end, $tahun_pattern);
                }
            } else {
                if (!$only_active_members) {
                    // Semua anggota terdaftar dengan perpanjangan (MySQL 5.6 compatible)
                    $sql_b212 = <<<SQL
SELECT 
    'Peminjaman' AS Indikator
    , hp.TotalPinjaman AS TotalPinjaman
    , hpo.TotalPopulasi AS TotalPopulasi
    , ROUND((hp.TotalPinjaman / hpo.TotalPopulasi), 2) AS Nilai
FROM 
    (SELECT COUNT(1) AS TotalPinjaman
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?) AS hp
CROSS JOIN
    (SELECT COUNT(1) AS TotalPopulasi
     FROM member AS m 
     INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
     WHERE m.member_since_date < ?
         AND expire_date > ?) AS hpo
UNION ALL
SELECT 
    'Perpanjangan' AS Indikator
    , hpr.TotalPerpanjangan AS TotalPinjaman
    , hpo.TotalPopulasi AS TotalPopulasi
    , ROUND((hpr.TotalPerpanjangan / hpo.TotalPopulasi), 2) AS Nilai
FROM 
    (SELECT COUNT(1) AS TotalPerpanjangan
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?
         AND l.renewed > 0) AS hpr
CROSS JOIN
    (SELECT COUNT(1) AS TotalPopulasi
     FROM member AS m 
     INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
     WHERE m.member_since_date < ?
         AND expire_date > ?) AS hpo
SQL;
                    $stmt_b212 = $dbs->prepare($sql_b212);
                    $prev_year_end = ($tahun - 1) . '-12-31';
                    $stmt_b212->bind_param('sssss', $tahun_pattern, $start_date, $prev_year_end, $tahun_pattern, $start_date, $prev_year_end);
                } else {
                    // Hanya anggota yang pernah meminjam dengan perpanjangan (MySQL 5.6 compatible)
                    $sql_b212 = <<<SQL
SELECT 
    'Peminjaman' AS Indikator
    , hp.TotalPinjaman AS TotalPinjaman
    , hpo.TotalPopulasi AS TotalPopulasi
    , ROUND((hp.TotalPinjaman / hpo.TotalPopulasi), 2) AS Nilai
FROM 
    (SELECT COUNT(1) AS TotalPinjaman
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?) AS hp
CROSS JOIN
    (SELECT COUNT(DISTINCT m.member_id) AS TotalPopulasi
     FROM member AS m 
     INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
     INNER JOIN loan AS l ON m.member_id=l.member_id
     WHERE m.member_since_date < ?
         AND m.expire_date > ?
         AND l.loan_date LIKE ?) AS hpo
UNION ALL
SELECT 
    'Perpanjangan' AS Indikator
    , hpr.TotalPerpanjangan AS TotalPinjaman
    , hpo.TotalPopulasi AS TotalPopulasi
    , ROUND((hpr.TotalPerpanjangan / hpo.TotalPopulasi), 2) AS Nilai
FROM 
    (SELECT COUNT(1) AS TotalPerpanjangan
     FROM loan AS l 
     INNER JOIN item AS i ON l.item_code=i.item_code
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE l.loan_date LIKE ?
         AND l.renewed > 0) AS hpr
CROSS JOIN
    (SELECT COUNT(DISTINCT m.member_id) AS TotalPopulasi
     FROM member AS m 
     INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
     INNER JOIN loan AS l ON m.member_id=l.member_id
     WHERE m.member_since_date < ?
         AND m.expire_date > ?
         AND l.loan_date LIKE ?) AS hpo
SQL;
                    $stmt_b212 = $dbs->prepare($sql_b212);
                    $prev_year_end = ($tahun - 1) . '-12-31';
                    $stmt_b212->bind_param('ssssssss', $tahun_pattern, $start_date, $prev_year_end, $tahun_pattern, $tahun_pattern, $start_date, $prev_year_end, $tahun_pattern);
                }
            }

            $stmt_b212->execute();
            $result_b212 = $stmt_b212->get_result();

            if ($result_b212->num_rows > 0) {
                $data_b212 = [];
                echo '<table>';
                echo '<thead><tr>';
                echo '<th>Indikator</th>';
                echo '<th>Total Pinjaman</th>';
                echo '<th>Total Populasi</th>';
                echo '<th>Nilai</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                while ($row = $result_b212->fetch_assoc()) {
                    $data_b212[] = $row;
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['Indikator']) . '</td>';
                    echo '<td>' . number_format($row['TotalPinjaman'], 0, ',', '.') . '</td>';
                    echo '<td>' . number_format($row['TotalPopulasi'], 0, ',', '.') . '</td>';
                    echo '<td><strong>' . number_format($row['Nilai'], 2, ',', '.') . '</strong></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';

                // Store for summary
                if (!empty($data_b212)) {
                    $summary_data['b212'] = $data_b212[0];
                }

                // CSS visualization (skip if table_only mode)
                if (!empty($data_b212) && !$table_only) {
                    echo '<div class="chart-wrapper">';

                    // Comparison grid
                    echo '<div class="chart-box">';
                    echo '<h4>👥 Pinjaman vs Populasi</h4>';
                    echo '<div class="comparison-grid">';
                    echo '<div class="comparison-item">';
                    echo '<div class="comparison-label">Total Pinjaman</div>';
                    echo '<div class="comparison-value">' . number_format($data_b212[0]['TotalPinjaman'], 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '<div class="comparison-item">';
                    echo '<div class="comparison-label">Total Populasi</div>';
                    echo '<div class="comparison-value">' . number_format($data_b212[0]['TotalPopulasi'], 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '</div>';

                    // Progress bar comparison
                    $max_val = max($data_b212[0]['TotalPinjaman'], $data_b212[0]['TotalPopulasi']);
                    echo '<div class="bar-chart" style="margin-top: 20px;">';
                    $pinjaman_pct = ($data_b212[0]['TotalPinjaman'] / $max_val) * 100;
                    $populasi_pct = ($data_b212[0]['TotalPopulasi'] / $max_val) * 100;
                    echo '<div class="bar-item">';
                    echo '<div class="bar-label">Pinjaman</div>';
                    echo '<div class="bar-container">';
                    echo '<div class="bar-fill" style="width: ' . $pinjaman_pct . '%;"></div>';
                    echo '</div>';
                    echo '<div class="bar-value">' . number_format($data_b212[0]['TotalPinjaman'], 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '<div class="bar-item">';
                    echo '<div class="bar-label">Populasi</div>';
                    echo '<div class="bar-container" style="background: #fef3c7;">';
                    echo '<div class="bar-fill" style="width: ' . $populasi_pct . '%; background: #f59e0b;"></div>';
                    echo '</div>';
                    echo '<div class="bar-value">' . number_format($data_b212[0]['TotalPopulasi'], 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '</div></div>';

                    // Per capita metrics - Card style (no bar chart, neutral design)
                    echo '<div class="chart-box">';
                    echo '<h4>📈 Nilai Per Kapita</h4>';
                    echo '<div class="comparison-grid">';
                    foreach ($data_b212 as $item) {
                        $nilai = ceil($item['Nilai']);

                        // Use neutral blue color scheme for all values
                        $badge_color = '#3b82f6';
                        $bg_color = '#eff6ff';

                        echo '<div class="comparison-item" style="background: ' . $bg_color . '; border: 2px solid ' . $badge_color . '20;">';
                        echo '<div class="comparison-label">' . htmlspecialchars($item['Indikator']) . '</div>';
                        echo '<div class="comparison-value" style="color: ' . $badge_color . '; font-size: 48px; line-height: 1;">' . number_format($nilai, 2, ',', '.') . '</div>';
                        echo '<div style="margin-top: 8px;">';
                        echo '<span style="display: inline-block; padding: 4px 12px; background: ' . $badge_color . '; color: white; border-radius: 12px; font-size: 12px; font-weight: 600;">per kapita</span>';
                        echo '</div>';
                        echo '<div style="margin-top: 10px; font-size: 13px; color: #666; line-height: 1.4;">Rata-rata ' . number_format($nilai, 2, ',', '.') . ' buku dipinjam per anggota dalam setahun</div>';
                        echo '</div>';
                    }
                    echo '</div></div></div>';
                }
            } else {
                echo '<div class="error-box">Tidak ada data untuk tahun ' . $tahun . '</div>';
            }
            echo '</div>';

            // ===== B.2.1.3 - PERSENTASE KOLEKSI TIDAK DIGUNAKAN =====
            echo '<div class="indicator-box">';
            echo '<div class="indicator-title">B.2.1.3 - Persentase Koleksi Tidak Digunakan</div>';
            echo '<div class="indicator-desc">';
            echo 'Persentase dokumen dalam koleksi perpustakaan yang tidak digunakan selama periode yang ditentukan. ';
            echo 'Indikator ini menilai jumlah koleksi yang tidak digunakan dan kesesuaian pengembangan koleksi dengan kebutuhan populasi.';
            echo '</div>';

            $sql_b213 = <<<SQL
SELECT
    'Tidak Dipinjam' AS Indikator
    , htd.TotalTidakDipinjaman AS Total
    , he.TotalEksemplar AS TotalEksemplar
    , ROUND((htd.TotalTidakDipinjaman / he.TotalEksemplar) * 100, 2) AS Persentase
FROM 
    (SELECT COUNT(1) AS TotalTidakDipinjaman
     FROM item AS i 
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
     WHERE i.item_code NOT IN (
         SELECT DISTINCT l.item_code
         FROM loan AS l
         INNER JOIN item AS i2 ON l.item_code=i2.item_code
         INNER JOIN biblio AS b2 ON i2.biblio_id=b2.biblio_id
         WHERE l.loan_date LIKE ?
     )
         AND b.input_date < ?) AS htd
CROSS JOIN
    (SELECT COUNT(1) AS TotalEksemplar
     FROM item AS i 
     INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id) AS he
SQL;

            $stmt_b213 = $dbs->prepare($sql_b213);
            $next_year = ($tahun + 1) . '-01-01';
            $stmt_b213->bind_param('ss', $tahun_pattern, $next_year);
            $stmt_b213->execute();
            $result_b213 = $stmt_b213->get_result();

            if ($result_b213->num_rows > 0) {
                $data_b213 = $result_b213->fetch_assoc();
                echo '<table>';
                echo '<thead><tr>';
                echo '<th>Indikator</th>';
                echo '<th>Total</th>';
                echo '<th>Total Eksemplar</th>';
                echo '<th>Persentase</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($data_b213['Indikator']) . '</td>';
                echo '<td>' . number_format($data_b213['Total'], 0, ',', '.') . '</td>';
                echo '<td>' . number_format($data_b213['TotalEksemplar'], 0, ',', '.') . '</td>';
                echo '<td><strong>' . number_format($data_b213['Persentase'], 2, ',', '.') . ' %</strong></td>';
                echo '</tr>';
                echo '</tbody></table>';

                // Neutral card-based visualization - focus on utilization (skip if table_only mode)
                if (!$table_only) {
                    $digunakan = $data_b213['TotalEksemplar'] - $data_b213['Total'];
                    $pct_digunakan = 100 - $data_b213['Persentase'];

                    echo '<div class="chart-wrapper">';

                    // Highlight positive metric: Collection Utilization
                    echo '<div class="chart-box">';
                    echo '<h4>📊 Tingkat Pemanfaatan Koleksi</h4>';
                    echo '<div style="text-align: center; padding: 30px; background: #eff6ff; border: 2px solid #3b82f620; border-radius: 8px;">';
                    echo '<div style="font-size: 14px; color: #666; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Persentase Pemanfaatan</div>';
                    echo '<div style="font-size: 72px; font-weight: bold; color: #3b82f6; line-height: 1; margin: 15px 0;">' . number_format(round($pct_digunakan, 2), 2, ',', '.') . '%</div>';
                    echo '<div style="margin: 15px 0;">';
                    echo '<span style="display: inline-block; padding: 6px 16px; background: #3b82f6; color: white; border-radius: 16px; font-size: 14px; font-weight: 600;">Koleksi Digunakan</span>';
                    echo '</div>';
                    echo '<div style="margin-top: 15px; font-size: 14px; color: #666; line-height: 1.5;"><strong>' . number_format($digunakan, 0, ',', '.') . '</strong> dari <strong>' . number_format($data_b213['TotalEksemplar'], 0, ',', '.') . '</strong> eksemplar telah dipinjam dalam tahun ini</div>';
                    echo '</div></div>';

                    // Additional statistics in comparison grid
                    echo '<div class="chart-box">';
                    echo '<h4>📈 Detail Statistik Koleksi</h4>';
                    echo '<div class="comparison-grid">';
                    echo '<div class="comparison-item" style="background: #eff6ff; border: 2px solid #3b82f620;">';
                    echo '<div class="comparison-label">Eksemplar Digunakan</div>';
                    echo '<div class="comparison-value" style="color: #3b82f6;">' . number_format($digunakan, 0, ',', '.') . '</div>';
                    echo '<div style="margin-top: 8px; font-size: 13px; color: #666;">' . number_format(round($pct_digunakan, 2), 2, ',', '.') . '% dari total</div>';
                    echo '</div>';
                    echo '<div class="comparison-item" style="background: #f8fafc; border: 2px solid #e2e8f020;">';
                    echo '<div class="comparison-label">Belum Dipinjam Tahun Ini</div>';
                    echo '<div class="comparison-value" style="color: #64748b;">' . number_format($data_b213['Total'], 0, ',', '.') . '</div>';
                    echo '<div style="margin-top: 8px; font-size: 13px; color: #666;">' . number_format($data_b213['Persentase'], 2, ',', '.') . '% dari total</div>';
                    echo '</div>';
                    echo '<div class="comparison-item" style="background: #eff6ff; border: 2px solid #3b82f620;">';
                    echo '<div class="comparison-label">Total Eksemplar</div>';
                    echo '<div class="comparison-value" style="color: #3b82f6;">' . number_format($data_b213['TotalEksemplar'], 0, ',', '.') . '</div>';
                    echo '<div style="margin-top: 8px; font-size: 13px; color: #666;">Keseluruhan koleksi</div>';
                    echo '</div>';
                    echo '</div></div></div>';
                }
            } else {
                echo '<div class="error-box">Tidak ada data untuk tahun ' . $tahun . '</div>';
            }
            echo '</div>';

            // ===== B.2.2.1 - KUNJUNGAN PERPUSTAKAAN PER KAPITA =====
            echo '<div class="indicator-box">';
            echo '<div class="indicator-title">B.2.2.1 - Kunjungan Perpustakaan Per Kapita</div>';
            echo '<div class="indicator-desc">';
            echo 'Jumlah total kunjungan ke perpustakaan selama setahun penuh dibagi dengan jumlah total orang dalam target populasi yang dilayani. ';
            echo 'Indikator ini menilai keberhasilan perpustakaan dalam menarik pemustaka memanfaatkan layanannya.';
            echo '</div>';

            if (!$only_active_members) {
                // Semua anggota terdaftar (MySQL 5.6 compatible)
                $sql_b221 = <<<SQL
SELECT 
    'Kunjungan' AS Indikator
    , hk.TotalKunjungan AS TotalKunjungan
    , hpo.TotalPopulasi AS TotalPopulasi
    , ROUND((hk.TotalKunjungan / hpo.TotalPopulasi), 2) AS Nilai
FROM 
    (SELECT COUNT(1) AS TotalKunjungan
     FROM visitor_count AS vc 
     WHERE vc.checkin_date LIKE ?) AS hk
CROSS JOIN
    (SELECT COUNT(1) AS TotalPopulasi
     FROM member AS m 
     INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
     WHERE m.member_since_date < ?
         AND expire_date > ?) AS hpo
SQL;
                $stmt_b221 = $dbs->prepare($sql_b221);
                $prev_year_end = ($tahun - 1) . '-12-31';
                $stmt_b221->bind_param('sss', $tahun_pattern, $start_date, $prev_year_end);
            } else {
                // Hanya anggota yang pernah berkunjung (MySQL 5.6 compatible)
                $sql_b221 = <<<SQL
SELECT 
    'Kunjungan' AS Indikator
    , hk.TotalKunjungan AS TotalKunjungan
    , hpo.TotalPopulasi AS TotalPopulasi
    , ROUND((hk.TotalKunjungan / hpo.TotalPopulasi), 2) AS Nilai
FROM 
    (SELECT COUNT(1) AS TotalKunjungan
     FROM visitor_count AS vc 
     WHERE vc.checkin_date LIKE ?) AS hk
CROSS JOIN
    (SELECT COUNT(DISTINCT m.member_id) AS TotalPopulasi
     FROM member AS m 
     INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
     INNER JOIN visitor_count AS vc ON m.member_id=vc.member_id
     WHERE m.member_since_date < ?
         AND m.expire_date > ?
         AND vc.checkin_date LIKE ?) AS hpo
SQL;
                $stmt_b221 = $dbs->prepare($sql_b221);
                $prev_year_end = ($tahun - 1) . '-12-31';
                $stmt_b221->bind_param('ssss', $tahun_pattern, $start_date, $prev_year_end, $tahun_pattern);
            }
            $stmt_b221->execute();
            $result_b221 = $stmt_b221->get_result();

            if ($result_b221->num_rows > 0) {
                $data_b221 = $result_b221->fetch_assoc();
                echo '<table>';
                echo '<thead><tr>';
                echo '<th>Indikator</th>';
                echo '<th>Total Kunjungan</th>';
                echo '<th>Total Populasi</th>';
                echo '<th>Nilai</th>';
                echo '</tr></thead>';
                echo '<tbody>';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($data_b221['Indikator']) . '</td>';
                echo '<td>' . number_format($data_b221['TotalKunjungan'], 0, ',', '.') . '</td>';
                echo '<td>' . number_format($data_b221['TotalPopulasi'], 0, ',', '.') . '</td>';
                echo '<td><strong>' . number_format($data_b221['Nilai'], 2, ',', '.') . '</strong></td>';
                echo '</tr>';
                echo '</tbody></table>';

                // CSS visualization (skip if table_only mode)
                if (!$table_only) {
                    echo '<div class="chart-wrapper">';

                    // Comparison boxes
                    echo '<div class="chart-box">';
                    echo '<h4>🚪 Kunjungan vs Populasi</h4>';
                    echo '<div class="comparison-grid">';
                    echo '<div class="comparison-item">';
                    echo '<div class="comparison-label">Total Kunjungan</div>';
                    echo '<div class="comparison-value">' . number_format($data_b221['TotalKunjungan'], 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '<div class="comparison-item">';
                    echo '<div class="comparison-label">Total Populasi</div>';
                    echo '<div class="comparison-value">' . number_format($data_b221['TotalPopulasi'], 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '<div class="comparison-item">';
                    echo '<div class="comparison-label">Kunjungan Per Kapita</div>';
                    echo '<div class="comparison-value" style="color: #10b981;">' . number_format(ceil($data_b221['Nilai']), 2, ',', '.') . '</div>';
                    echo '</div>';
                    echo '</div>';

                    // Bar comparison
                    $max_b221 = max($data_b221['TotalKunjungan'], $data_b221['TotalPopulasi']);
                    $kunjungan_pct = ($data_b221['TotalKunjungan'] / $max_b221) * 100;
                    $populasi_pct_b221 = ($data_b221['TotalPopulasi'] / $max_b221) * 100;
                    echo '<div class="bar-chart" style="margin-top: 20px;">';
                    echo '<div class="bar-item">';
                    echo '<div class="bar-label">Kunjungan</div>';
                    echo '<div class="bar-container">';
                    echo '<div class="bar-fill" style="width: ' . $kunjungan_pct . '%; background: #10b981;"></div>';
                    echo '</div>';
                    echo '<div class="bar-value">' . number_format($data_b221['TotalKunjungan'], 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '<div class="bar-item">';
                    echo '<div class="bar-label">Populasi</div>';
                    echo '<div class="bar-container" style="background: #fef3c7;">';
                    echo '<div class="bar-fill" style="width: ' . $populasi_pct_b221 . '%; background: #f59e0b;"></div>';
                    echo '</div>';
                    echo '<div class="bar-value">' . number_format($data_b221['TotalPopulasi'], 0, ',', '.') . '</div>';
                    echo '</div>';
                    echo '</div></div>';

                    // Per capita card - neutral design without negative labeling
                    $nilai_kunjungan = ceil($data_b221['Nilai']);

                    // Use neutral color scheme for all values
                    $badge_color_visit = '#3b82f6';
                    $bg_color_visit = '#eff6ff';

                    echo '<div class="chart-box">';
                    echo '<h4>📊 Rasio Kunjungan Per Kapita</h4>';
                    echo '<div style="text-align: center; padding: 30px; background: ' . $bg_color_visit . '; border: 2px solid ' . $badge_color_visit . '20; border-radius: 8px;">';
                    echo '<div style="font-size: 14px; color: #666; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Kunjungan Per Kapita</div>';
                    echo '<div style="font-size: 72px; font-weight: bold; color: ' . $badge_color_visit . '; line-height: 1; margin: 15px 0;">' . number_format(ceil($nilai_kunjungan), 2, ',', '.') . '</div>';
                    echo '<div style="margin: 15px 0;">';
                    echo '<span style="display: inline-block; padding: 6px 16px; background: ' . $badge_color_visit . '; color: white; border-radius: 16px; font-size: 14px; font-weight: 600;">per kapita</span>';
                    echo '</div>';
                    echo '<div style="margin-top: 15px; font-size: 14px; color: #666; line-height: 1.5;">Rata-rata setiap anggota berkunjung <strong>' . number_format(ceil($nilai_kunjungan), 2, ',', '.') . ' kali</strong> dalam setahun</div>';
                    echo '</div></div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="error-box">Tidak ada data untuk tahun ' . $tahun . '</div>';
            }
            echo '</div>';

            ?>

        </div><!-- end container -->

    </div><!-- end menuBoxInner -->
</div><!-- end menuBox -->