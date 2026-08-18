<?php
/**
 * Helper Functions - Plugin Analisis Kinerja Perpustakaan Indonesia (PAKPI)
 * 
 * Standar SNI ISO 2789:2013 & PAKPI Hendro Wicaksono
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

    // Total Eksemplar Tidak Dipinjam pada tahun berjalan (buku yang sudah ada sebelum tahun berikutnya)
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
