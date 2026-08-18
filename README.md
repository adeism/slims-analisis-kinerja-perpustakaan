# Plugin Analisis Kinerja Perpustakaan (PAKPI) — SLiMS 9 Bulian

Plugin SLiMS 9 Bulian untuk menganalisis dan mengukur indikator kinerja perpustakaan secara otomatis berdasarkan **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI)** dan standar internasional **SNI ISO 2789:2013** (*Informasi dan Dokumentasi — Statistik Perpustakaan Internasional*).

Plugin ini membantu pengelola perpustakaan dan pustakawan untuk mengevaluasi efektivitas pemanfaatan koleksi bahan pustaka dan tingkat partisipasi pemustaka secara akurat berbasis data transaksi SLiMS.

---

## 📊 4 Indikator Kinerja Utama (SNI ISO 2789:2013)

Plugin ini menghitung 4 indikator kinerja utama sesuai klausul SNI ISO 2789:2013:

### 1. B.2.1.1 — Perputaran Koleksi (*Collection Turnover Rate*)
* **Definisi:** Jumlah total peminjaman dalam koleksi selama satu tahun dibagi dengan jumlah total eksemplar/judul.
* **Tujuan:** Mengukur seberapa sering dan seberapa aktif koleksi fisik buku dipinjam dan berputar di tangan pemustaka.
* **Rumus:**
  $$\text{Perputaran terhadap Eksemplar} = \frac{\text{Total Peminjaman}}{\text{Total Eksemplar Koleksi}}$$
  $$\text{Perputaran terhadap Judul} = \frac{\text{Total Peminjaman}}{\text{Total Judul Koleksi}}$$

---

### 2. B.2.1.2 — Pinjaman Per Kapita (*Loans per Capita*)
* **Definisi:** Jumlah total peminjaman dalam setahun dibagi dengan jumlah total anggota/populasi yang dilayani perpustakaan.
* **Tujuan:** Mengukur rata-rata konsumsi bacaan atau jumlah buku yang dipinjam oleh setiap pemustaka dalam satu tahun.
* **Rumus:**
  $$\text{Pinjaman Per Kapita} = \frac{\text{Total Peminjaman}}{\text{Total Populasi Anggota Terdaftar}}$$

---

### 3. B.2.1.3 — Persentase Koleksi Tidak Digunakan (*Percentage of Dormant Collection*)
* **Definisi:** Persentase dokumen/eksemplar yang **belum pernah dipinjam** sama sekali selama periode tahun berjalan.
* **Tujuan:** Mengetahui tingkat keterpakaian koleksi (*dead stock analysis*) untuk evaluasi relevansi pengadaan buku dengan kebutuhan pemustaka.
* **Rumus:**
  $$\text{Persentase Koleksi Tidak Digunakan} = \left( \frac{\text{Eksemplar Belum Dipinjam}}{\text{Total Eksemplar Koleksi}} \right) \times 100\%$$
  $$\text{Tingkat Pemanfaatan Koleksi} = 100\% - \text{Persentase Koleksi Tidak Digunakan}$$

---

### 4. B.2.2.1 — Kunjungan Perpustakaan Per Kapita (*Library Visits per Capita*)
* **Definisi:** Jumlah total kehadiran/kunjungan ke perpustakaan dalam setahun dibagi dengan jumlah total populasi anggota yang dilayani.
* **Tujuan:** Menilai daya tarik ruang fisik perpustakaan dan intensitas pemustaka datang ke perpustakaan.
* **Rumus:**
  $$\text{Kunjungan Per Kapita} = \frac{\text{Total Kunjungan Pemustaka}}{\text{Total Populasi Anggota Terdaftar}}$$

---

## 📚 Dokumen Referensi & Standar Acuan

1. 💻 **[Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI 2021)](https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/)**  
   *Panduan resmi dan query analitik berbasis database SLiMS yang disusun oleh Hendro Wicaksono dkk.*
2. 🌐 **[SNI ISO 2789:2013 — Informasi dan Dokumentasi: Statistik Perpustakaan Internasional](https://pesta.bsn.go.id/produk/detail/9512-sniiso27892013)**  
   *Standar resmi Badan Standardisasi Nasional (BSN) untuk statistik perpustakaan di Indonesia.*
3. 📊 **[ISO 11620:2014 — Library Performance Indicators](https://www.iso.org/standard/56757.html)**  
   *Standar internasional indikator kinerja perpustakaan.*
4. 📜 **[Undang-Undang RI Nomor 43 Tahun 2007 tentang Perpustakaan](https://jdih.perpusnas.go.id/)** ([Arsip JDIHN BPK RI](https://peraturan.bpk.go.id/Details/39907/uu-no-43-tahun-2007))  
   *Landasan evaluasi mutu penyelenggaraan perpustakaan di Indonesia.*

---

## 🛠️ Panduan Pemasangan (Instalasi)

1. Ekstrak atau clone plugin ini ke direktori plugin SLiMS Anda:
   ```bash
   cd /path/to/slims/plugins/
   git clone https://github.com/adeism/slims-analisis-kinerja-perpustakaan.git
   ```
2. Buka menu **System** > **Plugins** pada SLiMS Admin, lalu aktifkan plugin **Analisis Kinerja Perpustakaan (PAKPI)**.
3. Akses plugin melalui modul **Pelaporan (Reporting)**:
   * **Analisis Kinerja Perpustakaan (PAKPI)** — Dashboard utama metrik 4 indikator, tren multi-tahun, pola musiman bulanan, cetak PDF resmi, dan pengaturan kop laporan.
   * **Eksplorasi Analisis Kinerja** — Analitik data mendalam (Top 100 judul terpopuler, subyek teraktif, anggota peminjam terbanyak, dan daftar koleksi tidur).

---

## 🚀 Fitur Utama

- 📊 **Visual Scorecards & Grafik Bar Interaktif:** Kartu ringkasan metrik ISO, grafik batang proporsional, dan perbandingan eksemplar vs judul.
- 📈 **Tren Kinerja Multi-Tahun (3–5 Tahun):** Matriks perbandingan tahun ke tahun (*Year-over-Year*) untuk melihat perkembangan kinerja perpustakaan.
- 📅 **Distribusi Musiman Bulanan:** Analisis fluktuasi peminjaman dan kunjungan dari Januari hingga Desember.
- 💡 **Evaluasi & Catatan Kinerja Otomatis:** Rekomendasi manajerial berbasis capaian data untuk perbaikan promosi dan penataan koleksi.
- 🔍 **Eksplorasi Data Lengkap (4 Indikator):**
  * B.2.1.1: Top 100 Judul Buku & Top 100 Subyek Terpopuler.
  * B.2.1.2: Top 100 Anggota Teraktif & Peminjaman per Jenis Keanggotaan.
  * B.2.1.3: Top 100 Koleksi Tidur (*Dormant Collection*) yang belum pernah dipinjam.
  * B.2.2.1: Top 100 Pengunjung Teraktif.
- ⚙️ **Kustomisasi Kop & Penandatangan Laporan:**
  * Bebas menyesuaikan Nama Instansi, Nama Unit Perpustakaan, Alamat, dan Kota.
  * Penandatangan dinamis (dapat disesuaikan 1 s.d. 4 pejabat penandatangan).
- 🖨️ **Cetak & Ekspor Lengkap:**
  * **Ekspor Microsoft Excel (`.xls`):** Dokumen spreadsheet multi-tabel rapi dan terstruktur.
  * **Ekspor CSV:** Format data standar RFC 4180 UTF-8 BOM.
  * **Cetak / PDF:** Tata letak A4 resmi siap cetak (*Print-Ready*).
- 🛡️ **Aman & Kompatibel:** Menggunakan sesi native SLiMS (tanpa masalah logout mendadak) dan kompatibel lintas database (MySQL 5.7+, MySQL 8.0+, MariaDB 10.1+).

---

## 📜 Lisensi
Plugin ini dilisensikan di bawah **GNU General Public License v3.0 (GPLv3)**.  
Bebas digunakan, dimodifikasi, dan didistribusikan untuk kemajuan pengelolaan perpustakaan.
