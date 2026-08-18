# Plugin Analisis Kinerja Perpustakaan (PAKPI)

Plugin resmi SLiMS 9 Bulian untuk menganalisis kinerja perpustakaan berdasarkan **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI)** dan standar internasional **SNI ISO 2789:2013**.

---

## 📋 4 Indikator Utama (SNI ISO 2789:2013)

1. **B.2.1.1 — Perputaran Koleksi (*Collection Turnover Rate*)**  
   Menilai tingkat intensitas dan perputaran pemanfaatan fisik buku (Total Peminjaman dibagi Total Eksemplar / Total Judul).
2. **B.2.1.2 — Pinjaman Per Kapita (*Loans per Capita*)**  
   Menilai rata-rata buku yang dipinjam oleh setiap anggota yang dilayani perpustakaan dalam setahun.
3. **B.2.1.3 — Persentase Koleksi Tidak Digunakan (*Percentage of Dormant Collection*)**  
   Mengukur jumlah dan persentase koleksi yang belum pernah dipinjam dalam periode tahun berjalan untuk evaluasi pengadaan koleksi.
4. **B.2.2.1 — Kunjungan Perpustakaan Per Kapita (*Library Visits per Capita*)**  
   Menilai keberhasilan perpustakaan menarik pemustaka melalui kehadiran fisik ke perpustakaan.

---

## 🛠️ Instalasi & Pemasangan

1. Ekstrak atau clone plugin ke direktori:
   ```bash
   plugins/slims-analisis-kinerja-perpustakaan
   ```
2. Buka modul **System** > **Plugin** pada SLiMS Admin, lalu aktifkan plugin **Analisis Kinerja Perpustakaan (PAKPI)**.
3. Menu baru akan otomatis muncul di bawah modul **Pelaporan (Reporting)**:
   * **Analisis Kinerja Perpustakaan (PAKPI)** — Dashboard utama & metrik ISO.
   * **Eksplorasi Analisis Kinerja** — Analitik mendalam (Top 30 buku, topik, anggota teraktif, dan koleksi tidur).

---

## ⚡ Fitur & Peningkatan Versi 1.1.0

- 🛡️ **Session & SLiMS AJAX Hardening:** Menggunakan standar native SLiMS `session.inc.php` tanpa merusak sesi admin (`session_check.inc.php` dihapus).
- 🚀 **Iframe-Less Exploration Dashboard:** Menghilangkan arsitektur `<iframe>` yang lambat pada menu Eksplorasi menjadi tata letak native AJAX SLiMS.
- 📊 **Eksplorasi Lengkap 4 Indikator:**
  * B.2.1.1: Top 30 Buku & Top 30 Subyek Terpopuler.
  * B.2.1.2: Top 30 Anggota Teraktif & Peminjaman per Jenis Keanggotaan.
  * B.2.1.3: Top 30 Koleksi Tidur (*Dormant Collection*) yang belum pernah dipinjam.
  * B.2.2.1: Top 30 Pengunjung Teraktif ke Perpustakaan.
- 📥 **Ekspor CSV (RFC 4180 UTF-8 BOM):** Mendukung ekspor data dashboard dan eksplorasi langsung ke format spreadsheet (Microsoft Excel compatible).
- 🖨️ **Print Ready:** Dilengkapi stylesheet cetak khusus untuk pembuatan laporan borang Akreditasi Perpustakaan.
- 🔄 **Cross-Database Compatibility:** Query dioptimalkan dengan Prepared Statements dan subquery standar yang kompatibel dengan seluruh versi MySQL 5.7+ / MariaDB 10.1+.

---

## 📚 Referensi & Lisensi
- **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI)** oleh Hendro Wicaksono ([GitLab](https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/)).
- **SNI ISO 2789:2013** — Statistik Perpustakaan Internasional.
- **Lisensi:** GNU General Public License v3.0 (GPLv3).
