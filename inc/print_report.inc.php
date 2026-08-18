<?php
/**
 * Standalone Official Print & PDF Template - PAKPI SLiMS
 * 
 * High-resolution, professional A4 print layout for Library Accreditation.
 */

defined('INDEX_AUTH') OR die('Direct access not allowed');

$settings = pakpiLoadSettings();

$dataB211 = pakpiGetB211($dbs, $tahun, $include_renewal);
$dataB212 = pakpiGetB212($dbs, $tahun, $include_renewal, $only_active_members);
$dataB213 = pakpiGetB213($dbs, $tahun);
$dataB221 = pakpiGetB221($dbs, $tahun, $only_active_members);
$insights = pakpiGenerateInsights($dataB211, $dataB212, $dataB213, $dataB221);

$bulanIndo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$tglCetak = date('j') . ' ' . $bulanIndo[(int)date('n')] . ' ' . date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Kinerja_Perpustakaan_<?= $tahun ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .action-bar {
            position: fixed;
            top: 15px;
            right: 15px;
            background: #1e293b;
            padding: 10px 16px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 99999;
            display: flex;
            gap: 10px;
        }
        .btn-action {
            padding: 8px 16px;
            border-radius: 6px;
            font-family: 'Segoe UI', sans-serif;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-print {
            background: #2563eb;
            color: white;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        .btn-close {
            background: #475569;
            color: white;
        }
        .btn-close:hover {
            background: #334155;
        }

        /* ── Official Letterhead (Kop Surat) ── */
        .kop-surat {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }
        .kop-instansi {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .kop-unit {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .kop-alamat {
            font-size: 9pt;
            font-style: italic;
            color: #333;
            margin-bottom: 8px;
        }
        .kop-divider {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            height: 4px;
            margin-bottom: 20px;
        }

        /* ── Document Title ── */
        .doc-title-container {
            text-align: center;
            margin-bottom: 25px;
        }
        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 4px;
        }
        .doc-subtitle {
            font-size: 10.5pt;
            font-weight: normal;
        }

        /* ── Summary Scorecards in Print ── */
        .scorecard-grid {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .scorecard {
            flex: 1;
            border: 1px solid #000;
            border-radius: 4px;
            padding: 10px 12px;
            text-align: center;
            background: #fdfdfd;
        }
        .scorecard-title {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
            color: #333;
        }
        .scorecard-val {
            font-size: 20pt;
            font-weight: bold;
            line-height: 1.1;
        }
        .scorecard-unit {
            font-size: 8pt;
            color: #555;
            margin-top: 2px;
        }

        /* ── Section & Tables ── */
        .section-box {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #999;
            padding-bottom: 3px;
            display: flex;
            justify-content: space-between;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10pt;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: left;
        }
        td.num, th.num {
            text-align: right;
        }
        td.center, th.center {
            text-align: center;
        }

        /* ── Insights Narrative Box ── */
        .insights-box {
            border: 1px solid #999;
            border-radius: 4px;
            padding: 12px 15px;
            margin-bottom: 25px;
            background: #fafafa;
            page-break-inside: avoid;
        }
        .insights-title {
            font-size: 10.5pt;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .insights-list {
            margin: 0;
            padding-left: 20px;
            font-size: 9.5pt;
            line-height: 1.5;
        }
        .insights-list li {
            margin-bottom: 6px;
        }

        /* ── Dynamic Signatures Block ── */
        .signature-wrapper {
            margin-top: 35px;
            page-break-inside: avoid;
        }
        .signature-header-date {
            text-align: right;
            margin-bottom: 20px;
            font-size: 10.5pt;
        }
        .signature-grid {
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 20px;
        }
        .signature-col {
            flex: 1;
            min-width: 180px;
            max-width: 250px;
            text-align: center;
            font-size: 10.5pt;
        }
        .signer-label {
            margin-bottom: 2px;
        }
        .signer-jabatan {
            font-weight: bold;
            margin-bottom: 65px; /* Space for physical signature / stamp */
        }
        .signer-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .signer-nip {
            font-size: 9.5pt;
            margin-top: 2px;
        }

        @media print {
            .action-bar {
                display: none !important;
            }
            body {
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

<!-- Non-Printable Floating Action Bar -->
<div class="action-bar">
    <button class="btn-action btn-print" onclick="window.print()">
        🖨️ Cetak / Simpan sebagai PDF
    </button>
    <button class="btn-action btn-close" onclick="window.close()">
        ✖️ Tutup
    </button>
</div>

<!-- Kop Surat Resmi -->
<div class="kop-surat">
    <div class="kop-instansi"><?= htmlspecialchars($settings['instansi'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    <div class="kop-unit"><?= htmlspecialchars($settings['unit'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    <div class="kop-alamat"><?= htmlspecialchars($settings['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    <div class="kop-divider"></div>
</div>

<!-- Judul Dokumen -->
<div class="doc-title-container">
    <div class="doc-title">LAPORAN ANALISIS KINERJA PERPUSTAKAAN</div>
    <div class="doc-subtitle">
        Tahun Evaluasi: <strong><?= $tahun ?></strong> | Standar Acuan: <strong>SNI ISO 2789:2013 &amp; ISO 11620:2014</strong>
    </div>
</div>

<!-- Ringkasan Eksekutif 4 Metrik Utama -->
<div class="scorecard-grid">
    <div class="scorecard">
        <div class="scorecard-title">B.2.1.1 Perputaran Koleksi</div>
        <div class="scorecard-val"><?= $dataB211[0]['nilai_thd_eksemplar'] ?? 0 ?></div>
        <div class="scorecard-unit">kali per eksemplar/tahun</div>
    </div>
    <div class="scorecard">
        <div class="scorecard-title">B.2.1.2 Pinjaman Per Kapita</div>
        <div class="scorecard-val"><?= $dataB212[0]['nilai'] ?? 0 ?></div>
        <div class="scorecard-unit">buku per anggota/tahun</div>
    </div>
    <div class="scorecard">
        <div class="scorecard-title">B.2.1.3 Pemanfaatan Koleksi</div>
        <div class="scorecard-val"><?= $dataB213['pct_digunakan'] ?>%</div>
        <div class="scorecard-unit"><?= $dataB213['persentase_tidak'] ?>% koleksi belum dipinjam</div>
    </div>
    <div class="scorecard">
        <div class="scorecard-title">B.2.2.1 Kunjungan Per Kapita</div>
        <div class="scorecard-val"><?= $dataB221['nilai'] ?></div>
        <div class="scorecard-unit">kali per anggota/tahun</div>
    </div>
</div>

<!-- 1. B.2.1.1 Perputaran Koleksi -->
<div class="section-box">
    <div class="section-title">
        <span>1. B.2.1.1 — Perputaran Koleksi (Collection Turnover Rate)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Indikator / Transaksi</th>
                <th class="num">Total Transaksi</th>
                <th class="num">Total Eksemplar</th>
                <th class="center">Nilai thd Eksemplar</th>
                <th class="num">Total Judul</th>
                <th class="center">Nilai thd Judul</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dataB211 as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['indikator'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td class="num"><?= number_format($r['total']) ?></td>
                    <td class="num"><?= number_format($r['total_eksemplar']) ?></td>
                    <td class="center"><strong><?= $r['nilai_thd_eksemplar'] ?></strong></td>
                    <td class="num"><?= number_format($r['total_judul']) ?></td>
                    <td class="center"><strong><?= $r['nilai_thd_judul'] ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 2. B.2.1.2 Pinjaman Per Kapita -->
<div class="section-box">
    <div class="section-title">
        <span>2. B.2.1.2 — Pinjaman Per Kapita (Loans per Capita)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Indikator Transaksi</th>
                <th class="num">Total Peminjaman</th>
                <th class="num">Total Populasi Anggota</th>
                <th class="center">Nilai Capaian</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dataB212 as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['indikator'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td class="num"><?= number_format($r['total_pinjaman']) ?></td>
                    <td class="num"><?= number_format($r['total_populasi']) ?></td>
                    <td class="center"><strong><?= $r['nilai'] ?></strong></td>
                    <td>Rata-rata <?= $r['nilai'] ?> buku dipinjam per anggota dalam setahun</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 3. B.2.1.3 Persentase Koleksi Tidak Digunakan -->
<div class="section-box">
    <div class="section-title">
        <span>3. B.2.1.3 — Persentase Koleksi Tidak Digunakan (Dormant Collection)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Status Koleksi Bahan Pustaka</th>
                <th class="num">Jumlah Eksemplar</th>
                <th class="num">Total Seluruh Eksemplar</th>
                <th class="center">Persentase (%)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Belum Pernah Dipinjam pada Tahun Ini</td>
                <td class="num"><?= number_format($dataB213['total_tidak']) ?></td>
                <td class="num"><?= number_format($dataB213['total_eksemplar']) ?></td>
                <td class="center"><strong><?= $dataB213['persentase_tidak'] ?> %</strong></td>
            </tr>
            <tr>
                <td>Telah Dimanfaatkan (Peminjaman Aktif)</td>
                <td class="num"><?= number_format($dataB213['total_digunakan']) ?></td>
                <td class="num"><?= number_format($dataB213['total_eksemplar']) ?></td>
                <td class="center"><strong><?= $dataB213['pct_digunakan'] ?> %</strong></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 4. B.2.2.1 Kunjungan Perpustakaan Per Kapita -->
<div class="section-box">
    <div class="section-title">
        <span>4. B.2.2.1 — Kunjungan Perpustakaan Per Kapita (Library Visits per Capita)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Indikator</th>
                <th class="num">Total Kehadiran Kunjungan</th>
                <th class="num">Total Populasi Anggota</th>
                <th class="center">Nilai Capaian</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Kunjungan Fisik Pemustaka</strong></td>
                <td class="num"><?= number_format($dataB221['total_kunjungan']) ?></td>
                <td class="num"><?= number_format($dataB221['total_populasi']) ?></td>
                <td class="center"><strong><?= $dataB221['nilai'] ?></strong></td>
                <td>Rata-rata setiap anggota berkunjung <?= $dataB221['nilai'] ?> kali dalam setahun</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Evaluasi & Rekomendasi Mutu -->
<div class="insights-box">
    <div class="insights-title">📋 Evaluasi Kinerja &amp; Rekomendasi Manajerial:</div>
    <ul class="insights-list">
        <?php foreach ($insights as $ins): ?>
            <li>
                <strong><?= htmlspecialchars($ins['title'], ENT_QUOTES, 'UTF-8') ?>:</strong>
                <?= htmlspecialchars($ins['message'], ENT_QUOTES, 'UTF-8') ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Blok Tanda Tangan Kustom Dinamis -->
<div class="signature-wrapper">
    <div class="signature-header-date">
        <?= htmlspecialchars($settings['kota'] ?? 'Kota', ENT_QUOTES, 'UTF-8') ?>, <?= $tglCetak ?>
    </div>
    <div class="signature-grid">
        <?php 
        $signers = $settings['signers'] ?? [];
        foreach ($signers as $signer): 
        ?>
            <div class="signature-col">
                <div class="signer-label"><?= htmlspecialchars($signer['label'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="signer-jabatan"><?= htmlspecialchars($signer['jabatan'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="signer-name"><?= htmlspecialchars($signer['nama'] ?? '........................................', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="signer-nip">NIP. <?= htmlspecialchars($signer['nip'] ?? '........................', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Auto-trigger print dialog after load
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 500);
    });
</script>

</body>
</html>
