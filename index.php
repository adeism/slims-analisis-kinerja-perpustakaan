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

?>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f5f5;
    margin: 0;
    padding: 0;
    color: #333;
    line-height: 1.6;
}
.info-popup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    max-width: 600px;
    z-index: 10000;
}
.info-popup.active {
    display: block;
}
.popup-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
}
.popup-overlay.active {
    display: block;
}
.popup-close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 28px;
    font-weight: bold;
    color: #999;
    cursor: pointer;
    border: none;
    background: none;
}
.popup-close:hover {
    color: #333;
}
.filter-form-wrapper {
    background: white;
    padding: 20px;
    margin-bottom: 0;
    border-radius: 0;
}
.inline-form {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}
.inline-form select {
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
}
.inline-form label {
    display: flex;
    align-items: center;
    gap: 5px;
    margin: 0;
}
        @media print {
            .filter-form-wrapper { 
                display: none !important; 
            }
            * { 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
            }
            body { 
                margin: 0;
                padding: 10px;
                background: white !important;
                font-size: 10pt;
            }
            .non-printable { 
                display: none !important; 
            }
            .container {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
            }
            h2 { 
                font-size: 14pt;
                margin: 0 0 10px 0;
                padding: 10px 0;
                page-break-after: avoid;
            }
            .indicator-box {
                page-break-inside: avoid;
                margin: 10px 0;
                padding: 10px;
            }
            .chart-wrapper {
                page-break-inside: avoid;
            }
            table {
                font-size: 9pt;
                page-break-inside: avoid;
            }
            th, td {
                padding: 5px !important;
            }
        }
        .container {
            max-width: 100%;
            margin: 0;
            background: #fff;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        h2 {
            color: #333;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content-area {
            background: #f5f5f5;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        .stat-card {
            background: #4A90E2;
            color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 13px;
            opacity: 0.8;
        }
        .indicator-box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 0 0 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .indicator-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .indicator-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .indicator-badge {
            background: #4A90E2;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .indicator-desc {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
            padding: 12px;
            background: #e7f3ff;
            border-left: 4px solid #4A90E2;
            border-radius: 4px;
        }
        .chart-container {
            position: relative;
            margin: 25px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }
        .chart-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
        }
        .chart-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .chart-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .chart-box h4 {
            color: #333;
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .bar-chart {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 10px 0;
        }
        .bar-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bar-label {
            min-width: 100px;
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
        }
        .bar-container {
            flex: 1;
            background: #f0f0f0;
            border-radius: 6px;
            height: 32px;
            position: relative;
            overflow: hidden;
        }
        .bar-fill {
            height: 100%;
            background: #4A90E2;
            border-radius: 6px;
            transition: width 1s ease-out;
            animation: fillBar 1.5s ease-out;
        }
        @keyframes fillBar {
            from { width: 0; }
        }
        .bar-value {
            min-width: 90px;
            text-align: right;
            font-weight: 600;
            color: #4A90E2;
            font-size: 13px;
        }
        .progress-ring {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }
        .ring-container {
            position: relative;
            width: 200px;
            height: 200px;
        }
        .ring-background {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(
                #4A90E2 0deg,
                #4A90E2 var(--percentage),
                #f0f0f0 var(--percentage),
                #f0f0f0 360deg
            );
            display: flex;
            align-items: center;
            justify-content: center;
            animation: rotateRing 1.5s ease-out;
        }
        @keyframes rotateRing {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .ring-inner {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .ring-value {
            font-size: 32px;
            font-weight: bold;
            color: #4A90E2;
        }
        .ring-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        .comparison-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin: 12px 0;
        }
        .comparison-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .comparison-item:hover {
            transform: translateY(-2px);
            border-color: #4A90E2;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .comparison-value {
            font-size: 24px;
            font-weight: bold;
            color: #4A90E2;
            margin: 8px 0;
        }
        .comparison-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        .metric-bars {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px 0;
        }
        .metric-bar-item {
            position: relative;
        }
        .metric-bar-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .metric-bar-title {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
        }
        .metric-bar-percentage {
            font-size: 14px;
            font-weight: bold;
            color: #4A90E2;
        }
        .metric-bar-track {
            height: 25px;
            background: #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        .metric-bar-progress {
            height: 100%;
            background: #4A90E2;
            border-radius: 12px;
            transition: width 1.2s ease-out;
            animation: slideIn 1.2s ease-out;
        }
        @keyframes slideIn {
            from { width: 0; }
        }
        .donut-chart {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        .donut-ring {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: conic-gradient(
                from 0deg,
                #4A90E2 0deg,
                #4A90E2 calc(var(--value1) * 3.6deg),
                #e0e0e0 calc(var(--value1) * 3.6deg),
                #e0e0e0 360deg
            );
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: spinDonut 1.5s ease-out;
        }
        @keyframes spinDonut {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .donut-hole {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .donut-center-value {
            font-size: 24px;
            font-weight: bold;
            color: #4A90E2;
        }
        .donut-center-label {
            font-size: 11px;
            color: #64748b;
        }
        .donut-legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        .legend-text {
            font-size: 13px;
            color: #2d3748;
        }
        .legend-value {
            font-weight: bold;
            color: #4A90E2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th {
            background: #f9f9f9;
            color: #333;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e0e0;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
            font-size: 13px;
        }
        tr:last-child td {
            border-bottom: none;
        }
        tr:hover {
            background: #f8fafc;
        }
        td:first-child {
            font-weight: 600;
            color: #4A90E2;
        }
        .error-box {
            background: #fee2e2;
            border: 2px solid #fca5a5;
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 8px;
            color: #991b1b;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        .error-box::before {
            content: "⚠️";
            font-size: 20px;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            padding: 12px 15px;
            margin: 10px 0;
            border-radius: 8px;
            color: #1976d2;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 13px;
        }
        .info-box::before {
            content: "ℹ️";
            font-size: 20px;
            flex-shrink: 0;
        }
        .btn-print {
            padding: 8px 16px;
            background: #4A90E2;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .btn-print:hover {
            background: #357ABD;
        }
        .btn-print:active {
            background: #2868A8;
        }
        @media (max-width: 768px) {
            .chart-wrapper {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
    // Query tanpa perpanjangan
    $sql_b211 = <<<SQL
WITH 
HitungPinjaman AS (
    SELECT 
        COUNT(1) AS TotalPinjaman
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
),
HitungEksemplar AS (
    SELECT 
    COUNT(1) AS TotalEksemplar
    FROM item AS i 
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
),
HitungJudul AS (
    SELECT 
    COUNT(1) AS TotalJudul
    FROM biblio AS b 
    WHERE b.biblio_id IN (
        SELECT DISTINCT i.biblio_id
        FROM item AS i
    )
)
SELECT
    'Peminjaman' AS Indikator
    , (SELECT TotalPinjaman FROM HitungPinjaman) AS Total
    , (SELECT TotalEksemplar FROM HitungEksemplar) AS TotalEksemplar
    , ROUND(((SELECT TotalPinjaman FROM HitungPinjaman)/
        (SELECT TotalEksemplar FROM HitungEksemplar)), 2) AS NilaiThdEksemplar
    , (SELECT TotalJudul FROM HitungJudul) AS TotalJudul
    , ROUND(((SELECT TotalPinjaman FROM HitungPinjaman)/
        (SELECT TotalJudul FROM HitungJudul)), 2) AS NilaiThdJudul
SQL;
} else {
    // Query dengan perpanjangan
    $sql_b211 = <<<SQL
WITH 
HitungPerpanjangan AS (
    SELECT 
        COUNT(1) AS TotalPerpanjangan
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
        AND l.renewed > 0
),
HitungPinjaman AS (
    SELECT 
        COUNT(1) AS TotalPinjaman
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
),
HitungEksemplar AS (
    SELECT 
    COUNT(1) AS TotalEksemplar
    FROM item AS i 
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
),
HitungJudul AS (
    SELECT 
    COUNT(1) AS TotalJudul
    FROM biblio AS b 
    WHERE b.biblio_id IN (
        SELECT DISTINCT i.biblio_id
        FROM item AS i
    )
)
SELECT
    'Peminjaman' AS Indikator
    , (SELECT TotalPinjaman FROM HitungPinjaman) AS Total
    , (SELECT TotalEksemplar FROM HitungEksemplar) AS TotalEksemplar
    , ROUND(((SELECT TotalPinjaman FROM HitungPinjaman)/
        (SELECT TotalEksemplar FROM HitungEksemplar)), 2) AS NilaiThdEksemplar
    , (SELECT TotalJudul FROM HitungJudul) AS TotalJudul
    , ROUND(((SELECT TotalPinjaman FROM HitungPinjaman)/
        (SELECT TotalJudul FROM HitungJudul)), 2) AS NilaiThdJudul
UNION ALL
SELECT
    'Perpanjangan' AS Indikator
    , (SELECT TotalPerpanjangan FROM HitungPerpanjangan) AS Total
    , (SELECT TotalEksemplar FROM HitungEksemplar) AS TotalEksemplar
    , ROUND(((SELECT TotalPerpanjangan FROM HitungPerpanjangan)/
        (SELECT TotalEksemplar FROM HitungEksemplar)), 2) AS NilaiThdEksemplar
    , (SELECT TotalJudul FROM HitungJudul) AS TotalJudul
    , ROUND(((SELECT TotalPerpanjangan FROM HitungPerpanjangan)/
        (SELECT TotalJudul FROM HitungJudul)), 2) AS NilaiThdJudul
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
        echo '<td>' . number_format($row['Total']) . '</td>';
        echo '<td>' . number_format($row['TotalEksemplar']) . '</td>';
        echo '<td><strong>' . $row['NilaiThdEksemplar'] . '</strong></td>';
        echo '<td>' . number_format($row['TotalJudul']) . '</td>';
        echo '<td><strong>' . $row['NilaiThdJudul'] . '</strong></td>';
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
            if ($max_total > 0) {
                $percentage = ($item['Total'] / $max_total) * 100;
            } else {
                $percentage = 0;
            }
            echo '<div class="bar-item">';
            echo '<div class="bar-label">' . htmlspecialchars($item['Indikator']) . '</div>';
            echo '<div class="bar-container">';
            echo '<div class="bar-fill" style="width: ' . $percentage . '%;"></div>';
            echo '</div>';
            echo '<div class="bar-value">' . number_format($item['Total']) . '</div>';
            echo '</div>';
        }
        echo '</div></div>';
        
        // Info box: Rasio Eksemplar per Judul
        $eksemplar = $data_b211[0]['TotalEksemplar'];
        $judul = $data_b211[0]['TotalJudul'];
        $rasio = $judul > 0 ? round($eksemplar / $judul, 2) : 0;
        
        echo '<div class="chart-box">';
        echo '<h4>📚 Statistik Koleksi</h4>';
        echo '<div class="comparison-grid">';
        echo '<div class="comparison-item">';
        echo '<div class="comparison-label">Total Eksemplar</div>';
        echo '<div class="comparison-value" style="color: #4A90E2;">' . number_format($eksemplar) . '</div>';
        echo '</div>';
        echo '<div class="comparison-item">';
        echo '<div class="comparison-label">Total Judul</div>';
        echo '<div class="comparison-value" style="color: #10b981;">' . number_format($judul) . '</div>';
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
        // Semua anggota terdaftar
        $sql_b212 = <<<SQL
WITH 
HitungPinjaman AS (
    SELECT 
        COUNT(1) AS TotalPinjaman
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
),
HitungPopulasi AS (
    SELECT 
    COUNT(1) AS TotalPopulasi
    FROM member AS m 
    INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
    WHERE 
        m.member_since_date < ?
        AND expire_date > ?
)
SELECT 
    'Peminjaman' AS Indikator
    , (SELECT TotalPinjaman FROM HitungPinjaman) AS TotalPinjaman
    , (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi
    , ROUND(((SELECT TotalPinjaman FROM HitungPinjaman)/(SELECT TotalPopulasi FROM HitungPopulasi)), 2) AS Nilai
SQL;
        $stmt_b212 = $dbs->prepare($sql_b212);
        $prev_year_end = ($tahun - 1) . '-12-31';
        $stmt_b212->bind_param('sss', $tahun_pattern, $start_date, $prev_year_end);
    } else {
        // Hanya anggota yang pernah meminjam
        $sql_b212 = <<<SQL
WITH 
HitungPinjaman AS (
    SELECT 
        COUNT(1) AS TotalPinjaman
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
),
HitungPopulasi AS (
    SELECT 
        COUNT(DISTINCT m.member_id) AS TotalPopulasi
    FROM member AS m 
    INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
    INNER JOIN loan AS l ON m.member_id=l.member_id
    WHERE 
        m.member_since_date < ?
        AND m.expire_date > ?
        AND l.loan_date LIKE ?
)
SELECT 
    'Peminjaman' AS Indikator
    , (SELECT TotalPinjaman FROM HitungPinjaman) AS TotalPinjaman
    , (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi
    , ROUND(((SELECT TotalPinjaman FROM HitungPinjaman)/(SELECT TotalPopulasi FROM HitungPopulasi)), 2) AS Nilai
SQL;
        $stmt_b212 = $dbs->prepare($sql_b212);
        $prev_year_end = ($tahun - 1) . '-12-31';
        $stmt_b212->bind_param('ssss', $tahun_pattern, $start_date, $prev_year_end, $tahun_pattern);
    }
} else {
    if (!$only_active_members) {
        // Semua anggota terdaftar (dengan perpanjangan)
        $sql_b212 = <<<SQL
WITH 
HitungPerpanjangan AS (
    SELECT 
        COUNT(1) AS TotalPerpanjangan
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
        AND l.renewed > 0
),
HitungPinjaman AS (
    SELECT 
        COUNT(1) AS TotalPinjaman
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
),
HitungPopulasi AS (
    SELECT 
    COUNT(1) AS TotalPopulasi
    FROM member AS m 
    INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
    WHERE 
        m.member_since_date < ?
        AND expire_date > ?
)
SELECT 
    'Peminjaman' AS Indikator
    , (SELECT TotalPinjaman FROM HitungPinjaman) AS TotalPinjaman
    , (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi
    , ROUND(((SELECT TotalPinjaman FROM HitungPinjaman)/(SELECT TotalPopulasi FROM HitungPopulasi)), 2) AS Nilai
UNION ALL
SELECT 
    'Perpanjangan' AS Indikator
    , (SELECT TotalPerpanjangan FROM HitungPerpanjangan) AS TotalPerpanjangan
    , (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi
    , ROUND(((SELECT TotalPerpanjangan FROM HitungPerpanjangan)/(SELECT TotalPopulasi FROM HitungPopulasi)), 2) AS Nilai
SQL;
        $stmt_b212 = $dbs->prepare($sql_b212);
        $prev_year_end = ($tahun - 1) . '-12-31';
        $stmt_b212->bind_param('ssss', $tahun_pattern, $tahun_pattern, $start_date, $prev_year_end);
    } else {
        // Hanya anggota yang pernah meminjam (dengan perpanjangan)
        $sql_b212 = <<<SQL
WITH 
HitungPerpanjangan AS (
    SELECT 
        COUNT(1) AS TotalPerpanjangan
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
        AND l.renewed > 0
),
HitungPinjaman AS (
    SELECT 
        COUNT(1) AS TotalPinjaman
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
),
HitungPopulasi AS (
    SELECT 
        COUNT(DISTINCT m.member_id) AS TotalPopulasi
    FROM member AS m 
    INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
    INNER JOIN loan AS l ON m.member_id=l.member_id
    WHERE 
        m.member_since_date < ?
        AND m.expire_date > ?
        AND l.loan_date LIKE ?
)
SELECT 
    'Peminjaman' AS Indikator
    , (SELECT TotalPinjaman FROM HitungPinjaman) AS TotalPinjaman
    , (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi
    , ROUND(((SELECT TotalPinjaman FROM HitungPinjaman)/(SELECT TotalPopulasi FROM HitungPopulasi)), 2) AS Nilai
UNION ALL
SELECT 
    'Perpanjangan' AS Indikator
    , (SELECT TotalPerpanjangan FROM HitungPerpanjangan) AS TotalPerpanjangan
    , (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi
    , ROUND(((SELECT TotalPerpanjangan FROM HitungPerpanjangan)/(SELECT TotalPopulasi FROM HitungPopulasi)), 2) AS Nilai
SQL;
        $stmt_b212 = $dbs->prepare($sql_b212);
        $prev_year_end = ($tahun - 1) . '-12-31';
        $stmt_b212->bind_param('sssss', $tahun_pattern, $tahun_pattern, $start_date, $prev_year_end, $tahun_pattern);
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
        echo '<td>' . number_format($row['TotalPinjaman']) . '</td>';
        echo '<td>' . number_format($row['TotalPopulasi']) . '</td>';
        echo '<td><strong>' . $row['Nilai'] . '</strong></td>';
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
        echo '<div class="comparison-value">' . number_format($data_b212[0]['TotalPinjaman']) . '</div>';
        echo '</div>';
        echo '<div class="comparison-item">';
        echo '<div class="comparison-label">Total Populasi</div>';
        echo '<div class="comparison-value">' . number_format($data_b212[0]['TotalPopulasi']) . '</div>';
        echo '</div>';
        echo '</div>';
        
        // Progress bar comparison
        $max_val = max($data_b212[0]['TotalPinjaman'], $data_b212[0]['TotalPopulasi']);
        echo '<div class="bar-chart" style="margin-top: 20px;">';
        if ($max_val == 0) {
            $pinjaman_pct = 0;
            $populasi_pct = 0;
        } else {
            $pinjaman_pct = ($data_b212[0]['TotalPinjaman'] / $max_val) * 100;
            $populasi_pct = ($data_b212[0]['TotalPopulasi'] / $max_val) * 100;
        }
        echo '<div class="bar-item">';
        echo '<div class="bar-label">Pinjaman</div>';
        echo '<div class="bar-container">';
        echo '<div class="bar-fill" style="width: ' . $pinjaman_pct . '%;"></div>';
        echo '</div>';
        echo '<div class="bar-value">' . number_format($data_b212[0]['TotalPinjaman']) . '</div>';
        echo '</div>';
        echo '<div class="bar-item">';
        echo '<div class="bar-label">Populasi</div>';
        echo '<div class="bar-container" style="background: #fef3c7;">';
        echo '<div class="bar-fill" style="width: ' . $populasi_pct . '%; background: #f59e0b;"></div>';
        echo '</div>';
        echo '<div class="bar-value">' . number_format($data_b212[0]['TotalPopulasi']) . '</div>';
        echo '</div>';
        echo '</div></div>';
        
        // Per capita metrics - Card style (no bar chart, neutral design)
        echo '<div class="chart-box">';
        echo '<h4>📈 Nilai Per Kapita</h4>';
        echo '<div class="comparison-grid">';
        foreach ($data_b212 as $item) {
            $nilai = $item['Nilai'];
            
            // Use neutral blue color scheme for all values
            $badge_color = '#3b82f6';
            $bg_color = '#eff6ff';
            
            echo '<div class="comparison-item" style="background: ' . $bg_color . '; border: 2px solid ' . $badge_color . '20;">';
            echo '<div class="comparison-label">' . htmlspecialchars($item['Indikator']) . '</div>';
            echo '<div class="comparison-value" style="color: ' . $badge_color . '; font-size: 48px; line-height: 1;">' . $nilai . '</div>';
            echo '<div style="margin-top: 8px;">';
            echo '<span style="display: inline-block; padding: 4px 12px; background: ' . $badge_color . '; color: white; border-radius: 12px; font-size: 12px; font-weight: 600;">per kapita</span>';
            echo '</div>';
            echo '<div style="margin-top: 10px; font-size: 13px; color: #666; line-height: 1.4;">Rata-rata ' . $nilai . ' buku dipinjam per anggota dalam setahun</div>';
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
WITH 
DaftarPinjaman AS (
    SELECT DISTINCT(l.item_code) AS item_code
    FROM loan AS l
    INNER JOIN item AS i ON l.item_code=i.item_code
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE l.loan_date LIKE ?
),
HitungTidakDipinjam AS (
    SELECT 
        COUNT(1) AS TotalTidakDipinjaman
    FROM item AS i 
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE i.item_code NOT IN (
        SELECT item_code FROM DaftarPinjaman
    )
        AND b.input_date < ?
),
HitungEksemplar AS (
    SELECT 
    COUNT(1) AS TotalEksemplar
    FROM item AS i 
    INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id
)
SELECT
    'Tidak Dipinjam' AS Indikator
    , (SELECT TotalTidakDipinjaman FROM HitungTidakDipinjam) AS Total
    , (SELECT TotalEksemplar FROM HitungEksemplar) AS TotalEksemplar
    , ROUND(((SELECT TotalTidakDipinjaman FROM HitungTidakDipinjam)/
        (SELECT TotalEksemplar FROM HitungEksemplar))*100, 2) AS Persentase
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
    echo '<td>' . number_format($data_b213['Total']) . '</td>';
    echo '<td>' . number_format($data_b213['TotalEksemplar']) . '</td>';
    echo '<td><strong>' . $data_b213['Persentase'] . ' %</strong></td>';
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
    echo '<div style="font-size: 72px; font-weight: bold; color: #3b82f6; line-height: 1; margin: 15px 0;">' . round($pct_digunakan, 2) . '%</div>';
    echo '<div style="margin: 15px 0;">';
    echo '<span style="display: inline-block; padding: 6px 16px; background: #3b82f6; color: white; border-radius: 16px; font-size: 14px; font-weight: 600;">Koleksi Digunakan</span>';
    echo '</div>';
    echo '<div style="margin-top: 15px; font-size: 14px; color: #666; line-height: 1.5;"><strong>' . number_format($digunakan) . '</strong> dari <strong>' . number_format($data_b213['TotalEksemplar']) . '</strong> eksemplar telah dipinjam dalam tahun ini</div>';
    echo '</div></div>';
    
    // Additional statistics in comparison grid
    echo '<div class="chart-box">';
    echo '<h4>📈 Detail Statistik Koleksi</h4>';
    echo '<div class="comparison-grid">';
    echo '<div class="comparison-item" style="background: #eff6ff; border: 2px solid #3b82f620;">';
    echo '<div class="comparison-label">Eksemplar Digunakan</div>';
    echo '<div class="comparison-value" style="color: #3b82f6;">' . number_format($digunakan) . '</div>';
    echo '<div style="margin-top: 8px; font-size: 13px; color: #666;">' . round($pct_digunakan, 2) . '% dari total</div>';
    echo '</div>';
    echo '<div class="comparison-item" style="background: #f8fafc; border: 2px solid #e2e8f020;">';
    echo '<div class="comparison-label">Belum Dipinjam Tahun Ini</div>';
    echo '<div class="comparison-value" style="color: #64748b;">' . number_format($data_b213['Total']) . '</div>';
    echo '<div style="margin-top: 8px; font-size: 13px; color: #666;">' . $data_b213['Persentase'] . '% dari total</div>';
    echo '</div>';
    echo '<div class="comparison-item" style="background: #eff6ff; border: 2px solid #3b82f620;">';
    echo '<div class="comparison-label">Total Eksemplar</div>';
    echo '<div class="comparison-value" style="color: #3b82f6;">' . number_format($data_b213['TotalEksemplar']) . '</div>';
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
    // Semua anggota terdaftar
    $sql_b221 = <<<SQL
WITH 
HitungKunjungan AS (
    SELECT 
        COUNT(1) AS TotalKunjungan
    FROM visitor_count AS vc 
    WHERE vc.checkin_date LIKE ?
),
HitungPopulasi AS (
    SELECT 
    COUNT(1) AS TotalPopulasi
    FROM member AS m 
    INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
    WHERE 
        m.member_since_date < ?
        AND expire_date > ?
)
SELECT 
    'Kunjungan' AS Indikator
    , (SELECT TotalKunjungan FROM HitungKunjungan) AS TotalKunjungan
    , (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi
    , ROUND(((SELECT TotalKunjungan FROM HitungKunjungan)/(SELECT TotalPopulasi FROM HitungPopulasi)), 2) AS Nilai
SQL;
    $stmt_b221 = $dbs->prepare($sql_b221);
    $prev_year_end = ($tahun - 1) . '-12-31';
    $stmt_b221->bind_param('sss', $tahun_pattern, $start_date, $prev_year_end);
} else {
    // Hanya anggota yang pernah berkunjung
    $sql_b221 = <<<SQL
WITH 
HitungKunjungan AS (
    SELECT 
        COUNT(1) AS TotalKunjungan
    FROM visitor_count AS vc 
    WHERE vc.checkin_date LIKE ?
),
HitungPopulasi AS (
    SELECT 
        COUNT(DISTINCT m.member_id) AS TotalPopulasi
    FROM member AS m 
    INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
    INNER JOIN visitor_count AS vc ON m.member_id=vc.member_id
    WHERE 
        m.member_since_date < ?
        AND m.expire_date > ?
        AND vc.checkin_date LIKE ?
)
SELECT 
    'Kunjungan' AS Indikator
    , (SELECT TotalKunjungan FROM HitungKunjungan) AS TotalKunjungan
    , (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi
    , ROUND(((SELECT TotalKunjungan FROM HitungKunjungan)/(SELECT TotalPopulasi FROM HitungPopulasi)), 2) AS Nilai
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
    echo '<td>' . number_format($data_b221['TotalKunjungan']) . '</td>';
    echo '<td>' . number_format($data_b221['TotalPopulasi']) . '</td>';
    echo '<td><strong>' . $data_b221['Nilai'] . '</strong></td>';
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
    echo '<div class="comparison-value">' . number_format($data_b221['TotalKunjungan']) . '</div>';
    echo '</div>';
    echo '<div class="comparison-item">';
    echo '<div class="comparison-label">Total Populasi</div>';
    echo '<div class="comparison-value">' . number_format($data_b221['TotalPopulasi']) . '</div>';
    echo '</div>';
    echo '<div class="comparison-item">';
    echo '<div class="comparison-label">Kunjungan Per Kapita</div>';
    echo '<div class="comparison-value" style="color: #10b981;">' . $data_b221['Nilai'] . '</div>';
    echo '</div>';
    echo '</div>';
    
    // Bar comparison
    $max_b221 = max($data_b221['TotalKunjungan'], $data_b221['TotalPopulasi']);
    if ($max_b221 == 0) {
        $kunjungan_pct = 0;
        $populasi_pct_b221 = 0;
    } else {
        $kunjungan_pct = ($data_b221['TotalKunjungan'] / $max_b221) * 100;
        $populasi_pct_b221 = ($data_b221['TotalPopulasi'] / $max_b221) * 100;
    }
    echo '<div class="bar-chart" style="margin-top: 20px;">';
    echo '<div class="bar-item">';
    echo '<div class="bar-label">Kunjungan</div>';
    echo '<div class="bar-container">';
    echo '<div class="bar-fill" style="width: ' . $kunjungan_pct . '%; background: #10b981;"></div>';
    echo '</div>';
    echo '<div class="bar-value">' . number_format($data_b221['TotalKunjungan']) . '</div>';
    echo '</div>';
    echo '<div class="bar-item">';
    echo '<div class="bar-label">Populasi</div>';
    echo '<div class="bar-container" style="background: #fef3c7;">';
    echo '<div class="bar-fill" style="width: ' . $populasi_pct_b221 . '%; background: #f59e0b;"></div>';
    echo '</div>';
    echo '<div class="bar-value">' . number_format($data_b221['TotalPopulasi']) . '</div>';
    echo '</div>';
    echo '</div></div>';
    
    // Per capita card - neutral design without negative labeling
    $nilai_kunjungan = $data_b221['Nilai'];
    
    // Use neutral color scheme for all values
    $badge_color_visit = '#3b82f6';
    $bg_color_visit = '#eff6ff';
    
    echo '<div class="chart-box">';
    echo '<h4>📊 Rasio Kunjungan Per Kapita</h4>';
    echo '<div style="text-align: center; padding: 30px; background: ' . $bg_color_visit . '; border: 2px solid ' . $badge_color_visit . '20; border-radius: 8px;">';
    echo '<div style="font-size: 14px; color: #666; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Kunjungan Per Kapita</div>';
    echo '<div style="font-size: 72px; font-weight: bold; color: ' . $badge_color_visit . '; line-height: 1; margin: 15px 0;">' . $nilai_kunjungan . '</div>';
    echo '<div style="margin: 15px 0;">';
    echo '<span style="display: inline-block; padding: 6px 16px; background: ' . $badge_color_visit . '; color: white; border-radius: 16px; font-size: 14px; font-weight: 600;">per kapita</span>';
    echo '</div>';
    echo '<div style="margin-top: 15px; font-size: 14px; color: #666; line-height: 1.5;">Rata-rata setiap anggota berkunjung <strong>' . $nilai_kunjungan . ' kali</strong> dalam setahun</div>';
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

</body>
</html>
