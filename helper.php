<?php
/**
 * Helper Functions - Plugin Analisis Kinerja Perpustakaan Indonesia (PAKPI)
 * 
 * Standar SNI ISO 2789:2013, ISO 11620:2014 & PAKPI Hendro Wicaksono
 */

defined('INDEX_AUTH') OR die('Direct access not allowed');

// ── Admin URL Helper ───────────────────────────────────────────────────────
function pakpiAdminUrl(array $params = []): string {
    $self  = $_SERVER['PHP_SELF'] ?? 'plugin_container.php';
    $query = array_merge($_GET, $params);
    return $self . '?' . http_build_query($query);
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

// ── Actionable Insights Generator (Rekomendasi Mutu Otomatis) ───────────────
function pakpiGenerateInsights(array $dataB211, array $dataB212, array $dataB213, array $dataB221): array {
    $insights = [];

    $turnover = $dataB211[0]['nilai_thd_eksemplar'] ?? 0;
    $loansPerCap = $dataB212[0]['nilai'] ?? 0;
    $dormantPct = $dataB213['persentase_tidak'] ?? 0;
    $utilizationPct = $dataB213['pct_digunakan'] ?? 0;
    $visitsPerCap = $dataB221['nilai'] ?? 0;

    // 1. Evaluasi Perputaran Koleksi
    if ($turnover >= 1.5) {
        $insights[] = [
            'type'    => 'success',
            'icon'    => '🌟',
            'title'   => 'Tingkat Perputaran Koleksi Sangat Baik',
            'message' => 'Angka perputaran koleksi (' . $turnover . ' kali/eksemplar) menunjukkan efisiensi dan dinamika pemanfaatan buku yang sangat tinggi oleh pemustaka.'
        ];
    } elseif ($turnover >= 0.5) {
        $insights[] = [
            'type'    => 'info',
            'icon'    => 'ℹ️',
            'title'   => 'Tingkat Perputaran Koleksi Moderat',
            'message' => 'Perputaran koleksi mencapai ' . $turnover . ' kali/eksemplar. Pustakawan dapat meningkatkan promosi judul-judul populer melalui media sosial atau display tematik.'
        ];
    } else {
        $insights[] = [
            'type'    => 'warning',
            'icon'    => '⚠️',
            'title'   => 'Tingkat Perputaran Koleksi Perlu Peningkatan',
            'message' => 'Rasio perputaran sebesar ' . $turnover . ' kali/eksemplar. Disarankan melakukan reposisi letak koleksi, program literasi membaca, atau penyelarasan kurikulum/silabus.'
        ];
    }

    // 2. Evaluasi Koleksi Tidak Digunakan
    if ($dormantPct > 65) {
        $insights[] = [
            'type'    => 'warning',
            'icon'    => '💤',
            'title'   => 'Tingkat Koleksi Tidur (Dormant) Cukup Tinggi',
            'message' => 'Sebesar ' . $dormantPct . '% koleksi belum pernah dipinjam. Direkomendasikan melakukan kegiatan Bedah Buku, Resensi Koleksi Baru, penataan ulang rak (*shelf re-arrangement*), serta program penyiangan (*weeding*) terhadap buku yang usang.'
        ];
    } else {
        $insights[] = [
            'type'    => 'success',
            'icon'    => '📚',
            'title'   => 'Pemanfaatan Koleksi Efektif',
            'message' => 'Sebesar ' . $utilizationPct . '% eksemplar aktif berputar. Pengadaan buku dinilai telah sesuai dengan profil kebutuhan pemustaka.'
        ];
    }

    // 3. Evaluasi Kunjungan Per Kapita
    if ($visitsPerCap >= 10) {
        $insights[] = [
            'type'    => 'success',
            'icon'    => '🚪',
            'title'   => 'Daya Tarik Ruang Perpustakaan Unggul',
            'message' => 'Rata-rata kunjungan ' . $visitsPerCap . ' kali/anggota/tahun menunjukkan perpustakaan telah menjadi ruang ketiga (*third place*) yang nyaman dan fungsional bagi civitas akademika/pemustaka.'
        ];
    } else {
        $insights[] = [
            'type'    => 'info',
            'icon'    => '💡',
            'title'   => 'Peluang Peningkatan Kunjungan Fisik',
            'message' => 'Rasio kunjungan sebesar ' . $visitsPerCap . ' kali/anggota. Perpustakaan dapat mengoptimalkan ruang diskusi kolaboratif, fasilitas Wi-Fi, workshop berkala, dan acara interaktif.'
        ];
    }

    return $insights;
}
