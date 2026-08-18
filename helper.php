<?php
/**
 * Helper Functions - Plugin Analisis Kinerja Perpustakaan Indonesia (PAKPI)
 * 
 * Mengacu pada Standar Nasional Perpustakaan (SNP) Perpusnas RI:
 * - Peraturan Perpustakaan Nasional RI No. 5 Tahun 2024 (Perpustakaan Perguruan Tinggi)
 * - Peraturan Perpustakaan Nasional RI No. 4 Tahun 2024 (Perpustakaan Sekolah/Madrasah)
 * - Peraturan Perpustakaan Nasional RI No. 11 Tahun 2021 (Perpustakaan Khusus)
 * - Standar Internasional SNI ISO 2789:2013 & ISO 11620:2014
 * - Instrumen Akreditasi 9 Komponen Perpustakaan Nasional RI
 */

defined('INDEX_AUTH') OR die('Direct access not allowed');

// ── Admin URL Helper ───────────────────────────────────────────────────────
function pakpiAdminUrl(array $params = []): string {
    $self  = $_SERVER['PHP_SELF'] ?? 'plugin_container.php';
    $query = array_merge($_GET, $params);
    return $self . '?' . http_build_query($query);
}

// ── CSRF Protection ────────────────────────────────────────────────────────
function pakpiGetCsrfToken(): string {
    if (empty($_SESSION['pakpi_csrf'])) {
        $_SESSION['pakpi_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['pakpi_csrf'];
}

function pakpiValidateCsrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['pakpi_csrf'] ?? '', $token);
}

// ── Report & Signer Settings ───────────────────────────────────────────────
function pakpiLoadSettings(): array {
    $defaults = [
        'instansi' => 'KEMENTERIAN PENDIDIKAN, KEBUDAYAAN, RISET, DAN TEKNOLOGI',
        'unit'     => 'UPT PERPUSTAKAAN',
        'alamat'   => 'Jl. Perpustakaan No. 1, Kota, Indonesia - Telp: (021) 1234567 | Website: perpustakaan.ac.id',
        'kota'     => 'Jakarta',
        'signers'  => [
            [
                'label'   => 'Mengetahui,',
                'jabatan' => 'Kepala Perpustakaan',
                'nama'    => 'Dra. Hj. Siti Nurhaliza, M.Hum.',
                'nip'     => '19750101 200003 2 001'
            ],
            [
                'label'   => 'Dibuat Oleh,',
                'jabatan' => 'Pustakawan / Analis Kinerja',
                'nama'    => 'Ahmad Fauzi, S.I.Pust.',
                'nip'     => '19880512 201402 1 003'
            ]
        ]
    ];

    $path = __DIR__ . '/settings.json';
    if (file_exists($path)) {
        $content = @file_get_contents($path);
        if ($content) {
            $data = json_decode($content, true);
            if (is_array($data)) {
                return array_merge($defaults, $data);
            }
        }
    }
    return $defaults;
}

function pakpiSaveSettings(array $settings): bool {
    $path = __DIR__ . '/settings.json';
    return (bool)@file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ── CSV Exporter (RFC 4180 with UTF-8 BOM) ─────────────────────────────────
function pakpiExportCsv(string $filename, array $headers, array $rows): void {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // Output UTF-8 BOM for Microsoft Excel compatibility
    fwrite($out, "\xEF\xBB\xBF");

    if (!empty($headers)) {
        fputcsv($out, $headers);
    }
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// ── Excel Exporter (.xls format with styling and multi-section tables) ───────
function pakpiExportExcel(string $filename, string $reportTitle, array $tables, array $meta = [], array $signers = []): void {
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta charset="UTF-8">';
    echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Laporan Kinerja</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    echo '<style>';
    echo 'table { border-collapse: collapse; font-family: Calibri, Arial, sans-serif; font-size: 11pt; margin-bottom: 20px; }';
    echo 'th { background-color: #1e40af; color: #ffffff; font-weight: bold; border: 1px solid #000000; padding: 6px 10px; text-align: left; }';
    echo 'td { border: 1px solid #d1d5db; padding: 5px 8px; }';
    echo '.title-cell { font-size: 15pt; font-weight: bold; text-align: center; color: #1e3a8a; }';
    echo '.meta-header { background-color: #f1f5f9; font-weight: bold; color: #334155; }';
    echo '.section-header { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 12pt; padding: 8px; }';
    echo '.num { text-align: right; }';
    echo '.center { text-align: center; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';

    // Header Meta Table
    echo '<table>';
    echo '<tr><td colspan="6" class="title-cell">' . htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    foreach ($meta as $k => $v) {
        echo '<tr><td colspan="2" class="meta-header">' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '</td><td colspan="4">' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }
    echo '<tr><td colspan="6">&nbsp;</td></tr>';
    echo '</table>';

    // Loop through Section Tables
    foreach ($tables as $tbl) {
        $colCount = max(1, count($tbl['headers'] ?? [1]));
        echo '<table>';
        if (!empty($tbl['title'])) {
            echo '<tr><td colspan="' . $colCount . '" class="section-header">' . htmlspecialchars($tbl['title'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        if (!empty($tbl['headers'])) {
            echo '<tr>';
            foreach ($tbl['headers'] as $th) {
                echo '<th>' . htmlspecialchars($th, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            echo '</tr>';
        }
        if (!empty($tbl['rows'])) {
            foreach ($tbl['rows'] as $r) {
                echo '<tr>';
                foreach ($r as $c) {
                    $isNum = is_numeric(str_replace([',', '.', ' '], '', (string)$c));
                    $align = $isNum && is_numeric($c) ? 'num' : '';
                    echo '<td class="' . $align . '">' . htmlspecialchars((string)$c, ENT_QUOTES, 'UTF-8') . '</td>';
                }
                echo '</tr>';
            }
        }
        echo '<tr><td colspan="' . $colCount . '">&nbsp;</td></tr>';
        echo '</table>';
    }

    // Signers Table
    if (!empty($signers)) {
        echo '<table>';
        echo '<tr><td colspan="6">&nbsp;</td></tr>';
        echo '<tr>';
        foreach ($signers as $s) {
            echo '<td colspan="3" class="center">';
            echo '<div>' . htmlspecialchars($s['label'] ?? 'Mengetahui,', ENT_QUOTES, 'UTF-8') . '</div>';
            echo '<div><b>' . htmlspecialchars($s['jabatan'] ?? '', ENT_QUOTES, 'UTF-8') . '</b></div><br><br><br>';
            echo '<div><u><b>' . htmlspecialchars($s['nama'] ?? '', ENT_QUOTES, 'UTF-8') . '</b></u></div>';
            echo '<div>NIP. ' . htmlspecialchars($s['nip'] ?? '-', ENT_QUOTES, 'UTF-8') . '</div>';
            echo '</td>';
        }
        echo '</tr>';
        echo '</table>';
    }

    echo '</body></html>';
    exit;
}

// ── Query Engine for B.2.1.1 (Perputaran Koleksi) ──────────────────────────
function pakpiGetB211(mysqli $dbs, int $tahun, bool $includeRenewal = false): array {
    $tahun_pattern = $tahun . '-%';
    
    // Total Eksemplar
    $qEks = "SELECT COUNT(1) AS total FROM item AS i INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id";
    $resEks = $dbs->query($qEks);
    $totalEksemplar = (int)($resEks ? ($resEks->fetch_assoc()['total'] ?? 0) : 0);

    // Total Judul yang memiliki eksemplar
    $qJudul = "SELECT COUNT(DISTINCT i.biblio_id) AS total FROM item AS i";
    $resJudul = $dbs->query($qJudul);
    $totalJudul = (int)($resJudul ? ($resJudul->fetch_assoc()['total'] ?? 0) : 0);

    // Total Peminjaman
    $stmt = $dbs->prepare("SELECT COUNT(1) AS total FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE l.loan_date LIKE ?");
    $stmt->bind_param('s', $tahun_pattern);
    $stmt->execute();
    $totalPinjaman = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

    $rows = [];
    $nilaiEks = $totalEksemplar > 0 ? round($totalPinjaman / $totalEksemplar, 2) : 0;
    $nilaiJudul = $totalJudul > 0 ? round($totalPinjaman / $totalJudul, 2) : 0;

    $rows[] = [
        'indikator'           => 'Peminjaman',
        'total'               => $totalPinjaman,
        'total_eksemplar'     => $totalEksemplar,
        'nilai_thd_eksemplar' => $nilaiEks,
        'total_judul'         => $totalJudul,
        'nilai_thd_judul'     => $nilaiJudul,
    ];

    if ($includeRenewal) {
        $stmtRen = $dbs->prepare("SELECT COUNT(1) AS total FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE l.loan_date LIKE ? AND l.renewed > 0");
        $stmtRen->bind_param('s', $tahun_pattern);
        $stmtRen->execute();
        $totalPerpanjangan = (int)($stmtRen->get_result()->fetch_assoc()['total'] ?? 0);

        $nilaiRenEks = $totalEksemplar > 0 ? round($totalPerpanjangan / $totalEksemplar, 2) : 0;
        $nilaiRenJudul = $totalJudul > 0 ? round($totalPerpanjangan / $totalJudul, 2) : 0;

        $rows[] = [
            'indikator'           => 'Perpanjangan',
            'total'               => $totalPerpanjangan,
            'total_eksemplar'     => $totalEksemplar,
            'nilai_thd_eksemplar' => $nilaiRenEks,
            'total_judul'         => $totalJudul,
            'nilai_thd_judul'     => $nilaiRenJudul,
        ];
    }

    return $rows;
}

// ── Query Engine for B.2.1.2 (Pinjaman Per Kapita) ─────────────────────────
function pakpiGetB212(mysqli $dbs, int $tahun, bool $includeRenewal = false, bool $onlyActive = false): array {
    $tahun_pattern = $tahun . '-%';
    $start_date    = $tahun . '-01-01';
    $prev_year_end = ($tahun - 1) . '-12-31';

    // Populasi yang dilayani
    if (!$onlyActive) {
        $stmtPop = $dbs->prepare("SELECT COUNT(1) AS total FROM member AS m INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id WHERE m.member_since_date < ? AND m.expire_date > ?");
        $stmtPop->bind_param('ss', $start_date, $prev_year_end);
    } else {
        $stmtPop = $dbs->prepare("SELECT COUNT(DISTINCT m.member_id) AS total FROM member AS m INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id INNER JOIN loan AS l ON m.member_id=l.member_id WHERE m.member_since_date < ? AND m.expire_date > ? AND l.loan_date LIKE ?");
        $stmtPop->bind_param('sss', $start_date, $prev_year_end, $tahun_pattern);
    }
    $stmtPop->execute();
    $totalPopulasi = (int)($stmtPop->get_result()->fetch_assoc()['total'] ?? 0);

    // Total Pinjaman
    $stmt = $dbs->prepare("SELECT COUNT(1) AS total FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE l.loan_date LIKE ?");
    $stmt->bind_param('s', $tahun_pattern);
    $stmt->execute();
    $totalPinjaman = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

    $nilai = $totalPopulasi > 0 ? round($totalPinjaman / $totalPopulasi, 2) : 0;

    $rows = [];
    $rows[] = [
        'indikator'      => 'Peminjaman',
        'total_pinjaman' => $totalPinjaman,
        'total_populasi' => $totalPopulasi,
        'nilai'          => $nilai,
    ];

    if ($includeRenewal) {
        $stmtRen = $dbs->prepare("SELECT COUNT(1) AS total FROM loan AS l INNER JOIN item AS i ON l.item_code=i.item_code INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE l.loan_date LIKE ? AND l.renewed > 0");
        $stmtRen->bind_param('s', $tahun_pattern);
        $stmtRen->execute();
        $totalPerpanjangan = (int)($stmtRen->get_result()->fetch_assoc()['total'] ?? 0);

        $nilaiRen = $totalPopulasi > 0 ? round($totalPerpanjangan / $totalPopulasi, 2) : 0;
        $rows[] = [
            'indikator'      => 'Perpanjangan',
            'total_pinjaman' => $totalPerpanjangan,
            'total_populasi' => $totalPopulasi,
            'nilai'          => $nilaiRen,
        ];
    }

    return $rows;
}

// ── Query Engine for B.2.1.3 (Persentase Koleksi Tidak Digunakan) ──────────
function pakpiGetB213(mysqli $dbs, int $tahun): array {
    $tahun_pattern = $tahun . '-%';
    $next_year     = ($tahun + 1) . '-01-01';

    // Total Eksemplar
    $qEks = "SELECT COUNT(1) AS total FROM item AS i INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id";
    $resEks = $dbs->query($qEks);
    $totalEksemplar = (int)($resEks ? ($resEks->fetch_assoc()['total'] ?? 0) : 0);

    // Total Eksemplar Tidak Dipinjam pada tahun berjalan
    $sql = "SELECT COUNT(1) AS total FROM item AS i 
            INNER JOIN biblio AS b ON i.biblio_id=b.biblio_id 
            WHERE i.item_code NOT IN (
                SELECT DISTINCT l.item_code FROM loan AS l 
                INNER JOIN item AS it ON l.item_code=it.item_code 
                WHERE l.loan_date LIKE ?
            ) AND b.input_date < ?";
    
    $stmt = $dbs->prepare($sql);
    $stmt->bind_param('ss', $tahun_pattern, $next_year);
    $stmt->execute();
    $totalTidakDipinjam = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

    $persentase = $totalEksemplar > 0 ? round(($totalTidakDipinjam / $totalEksemplar) * 100, 2) : 0;
    $digunakan = max(0, $totalEksemplar - $totalTidakDipinjam);
    $pctDigunakan = round(100 - $persentase, 2);

    return [
        'indikator'        => 'Tidak Dipinjam',
        'total_tidak'      => $totalTidakDipinjam,
        'total_eksemplar'  => $totalEksemplar,
        'persentase_tidak' => $persentase,
        'total_digunakan'  => $digunakan,
        'pct_digunakan'    => $pctDigunakan,
    ];
}

// ── Query Engine for B.2.2.1 (Kunjungan Per Kapita) ────────────────────────
function pakpiGetB221(mysqli $dbs, int $tahun, bool $onlyActive = false): array {
    $tahun_pattern = $tahun . '-%';
    $start_date    = $tahun . '-01-01';
    $prev_year_end = ($tahun - 1) . '-12-31';

    // Total Kunjungan
    $stmtKunj = $dbs->prepare("SELECT COUNT(1) AS total FROM visitor_count WHERE checkin_date LIKE ?");
    $stmtKunj->bind_param('s', $tahun_pattern);
    $stmtKunj->execute();
    $totalKunjungan = (int)($stmtKunj->get_result()->fetch_assoc()['total'] ?? 0);

    // Populasi yang dilayani
    if (!$onlyActive) {
        $stmtPop = $dbs->prepare("SELECT COUNT(1) AS total FROM member AS m INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id WHERE m.member_since_date < ? AND m.expire_date > ?");
        $stmtPop->bind_param('ss', $start_date, $prev_year_end);
    } else {
        $stmtPop = $dbs->prepare("SELECT COUNT(DISTINCT m.member_id) AS total FROM member AS m INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id INNER JOIN visitor_count AS vc ON m.member_id=vc.member_id WHERE m.member_since_date < ? AND m.expire_date > ? AND vc.checkin_date LIKE ?");
        $stmtPop->bind_param('sss', $start_date, $prev_year_end, $tahun_pattern);
    }
    $stmtPop->execute();
    $totalPopulasi = (int)($stmtPop->get_result()->fetch_assoc()['total'] ?? 0);

    $nilai = $totalPopulasi > 0 ? round($totalKunjungan / $totalPopulasi, 2) : 0;

    return [
        'indikator'       => 'Kunjungan',
        'total_kunjungan' => $totalKunjungan,
        'total_populasi'  => $totalPopulasi,
        'nilai'           => $nilai,
    ];
}

// ── Multi-Year Trend Analysis (3–5 Tahun Terakhir) ─────────────────────────
function pakpiGetMultiYearTrend(mysqli $dbs, int $endYear, int $numYears = 3): array {
    $trend = [];
    $startYear = $endYear - ($numYears - 1);

    for ($y = $startYear; $y <= $endYear; $y++) {
        $b211 = pakpiGetB211($dbs, $y, false);
        $b212 = pakpiGetB212($dbs, $y, false, false);
        $b213 = pakpiGetB213($dbs, $y);
        $b221 = pakpiGetB221($dbs, $y, false);

        $trend[$y] = [
            'tahun'            => $y,
            'perputaran_eks'   => $b211[0]['nilai_thd_eksemplar'] ?? 0,
            'perputaran_judul' => $b211[0]['nilai_thd_judul'] ?? 0,
            'pinjaman_kapita'  => $b212[0]['nilai'] ?? 0,
            'total_pinjaman'   => $b212[0]['total_pinjaman'] ?? 0,
            'pct_koleksi_tdk'  => $b213['persentase_tidak'] ?? 0,
            'pct_pemanfaatan'  => $b213['pct_digunakan'] ?? 0,
            'kunjungan_kapita' => $b221['nilai'] ?? 0,
            'total_kunjungan'  => $b221['total_kunjungan'] ?? 0,
            'populasi'         => $b221['total_populasi'] ?? 0,
        ];
    }

    return $trend;
}

// ── Monthly Seasonal Breakdown (Januari - Desember) ────────────────────────
function pakpiGetMonthlyTrend(mysqli $dbs, int $tahun): array {
    $monthNames = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];

    $data = [];
    foreach ($monthNames as $mKey => $mName) {
        $pattern = $tahun . '-' . $mKey . '-%';

        // Peminjaman
        $stmtL = $dbs->prepare("SELECT COUNT(1) AS total FROM loan WHERE loan_date LIKE ?");
        $stmtL->bind_param('s', $pattern);
        $stmtL->execute();
        $totLoan = (int)($stmtL->get_result()->fetch_assoc()['total'] ?? 0);

        // Kunjungan
        $stmtV = $dbs->prepare("SELECT COUNT(1) AS total FROM visitor_count WHERE checkin_date LIKE ?");
        $stmtV->bind_param('s', $pattern);
        $stmtV->execute();
        $totVis = (int)($stmtV->get_result()->fetch_assoc()['total'] ?? 0);

        $data[] = [
            'month_code' => $mKey,
            'month_name' => $mName,
            'loans'      => $totLoan,
            'visits'     => $totVis,
        ];
    }
    return $data;
}

// ── Actionable Insights Generator Berbasis Standar SNP Perpusnas RI ────────
function pakpiGenerateInsights(array $dataB211, array $dataB212, array $dataB213, array $dataB221): array {
    $insights = [];

    $turnover = $dataB211[0]['nilai_thd_eksemplar'] ?? 0;
    $loansPerCap = $dataB212[0]['nilai'] ?? 0;
    $dormantPct = $dataB213['persentase_tidak'] ?? 0;
    $utilizationPct = $dataB213['pct_digunakan'] ?? 0;
    $visitsPerCap = $dataB221['nilai'] ?? 0;

    // 1. Evaluasi Komponen Koleksi: Perputaran Koleksi (SNP Perpusnas RI 2024)
    if ($turnover >= 1.0) {
        $insights[] = [
            'type'    => 'success',
            'icon'    => '🌟',
            'title'   => 'Perputaran Koleksi Memenuhi Standar Nasional (SNP Perpusnas RI)',
            'message' => 'Angka perputaran koleksi sebesar ' . $turnover . ' kali/eksemplar/tahun melampaui batas standar minimum SNP Perpusnas RI (> 0.5 - 1.0), menunjukkan efisiensi dan dinamika pemanfaatan fisik buku yang sangat aktif.'
        ];
    } elseif ($turnover >= 0.5) {
        $insights[] = [
            'type'    => 'info',
            'icon'    => 'ℹ️',
            'title'   => 'Perputaran Koleksi Berada pada Kategori Cukup (Moderat)',
            'message' => 'Perputaran koleksi mencapai ' . $turnover . ' kali/eksemplar/tahun. Disarankan meningkatkan promosi judul-judul populer melalui display tematik dan kurasi buku rekomendasi dosen/guru.'
        ];
    } else {
        $insights[] = [
            'type'    => 'warning',
            'icon'    => '⚠️',
            'title'   => 'Perputaran Koleksi di Bawah Standar Optimal SNP Perpusnas',
            'message' => 'Rasio perputaran sebesar ' . $turnover . ' kali/eksemplar/tahun (Standar SNP minimal > 0.5). Disarankan melakukan reposisi letak koleksi, program gerakan literasi membaca, atau pemutakhiran koleksi wajib kurikulum.'
        ];
    }

    // 2. Evaluasi Komponen Koleksi: Pemanfaatan vs Koleksi Tidur (Dead Stock)
    if ($dormantPct > 50) {
        $insights[] = [
            'type'    => 'warning',
            'icon'    => '💤',
            'title'   => 'Tingkat Koleksi Tidur (Dormant Collection) Perlu Penanganan',
            'message' => 'Sebesar ' . $dormantPct . '% eksemplar belum pernah dipinjam dalam setahun. Berdasarkan SNP Perpusnas RI Komponen 1, perpustakaan direkomendasikan mengadakan kegiatan Bedah Buku, penataan ulang rak (*shelf re-arrangement*), dan evaluasi penyiangan (*weeding*) terhadap bahan pustaka usang/rusak.'
        ];
    } else {
        $insights[] = [
            'type'    => 'success',
            'icon'    => '📚',
            'title'   => 'Tingkat Keterpakaian Koleksi Sangat Baik',
            'message' => 'Sebesar ' . $utilizationPct . '% eksemplar aktif berputar. Pengadaan bahan pustaka dinilai sangat relevan dengan kebutuhan kurikulum dan minat pemustaka.'
        ];
    }

    // 3. Evaluasi Komponen Pelayanan: Kunjungan & Pembudayaan Literasi (SNP Perpusnas)
    if ($visitsPerCap >= 12) {
        $insights[] = [
            'type'    => 'success',
            'icon'    => '🚪',
            'title'   => 'Daya Tarik Ruang & Kunjungan Pemustaka Sangat Tinggi',
            'message' => 'Rata-rata kunjungan mencapai ' . $visitsPerCap . ' kali/anggota/tahun (rata-rata >= 1 kali/bulan). Perpustakaan berhasil memposisikan diri sebagai ruang ketiga (*third place*) dan pusat kegiatan literasi civitas akademika.'
        ];
    } else {
        $insights[] = [
            'type'    => 'info',
            'icon'    => '💡',
            'title'   => 'Peluang Peningkatan Kunjungan Pemustaka',
            'message' => 'Rasio kunjungan sebesar ' . $visitsPerCap . ' kali/anggota/tahun. Sesuai indikator Akreditasi Komponen Pelayanan Perpusnas RI, perpustakaan dapat mengoptimalkan ruang diskusi kolaboratif, fasilitas Wi-Fi cepat, workshop literasi, dan kegiatan bedah karya ilmiah.'
        ];
    }

    return $insights;
}
