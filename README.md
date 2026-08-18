# Plugin Analisis Kinerja Perpustakaan (PAKPI) — SLiMS 9 Bulian

Plugin resmi SLiMS 9 Bulian untuk menganalisis, mengevaluasi, dan menyusun laporan kinerja perpustakaan berdasarkan standar terbaru **Perpustakaan Nasional Republik Indonesia (Perpusnas RI)**, standar internasional **SNI ISO 2789:2013**, **ISO 11620:2014**, serta **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI)**.

Plugin ini dirancang untuk mempermudah Pustakawan dan Kepala Perpustakaan dalam penyusunan **Borang Akreditasi 9 Komponen Perpustakaan Nasional RI (Perpusnas)** maupun **Akreditasi Institusi / Program Studi (BAN-PT / LAM)**.

---

## ⚖️ Dasar Hukum & Standar Terbaru Perpustakaan Nasional RI

Pengembangan dan perhitungan indikator dalam plugin ini berlandaskan pada regulasi resmi terkini dari Perpustakaan Nasional Republik Indonesia dan Badan Standardisasi Nasional:

1. **Undang-Undang Republik Indonesia No. 43 Tahun 2007 tentang Perpustakaan:**
   * *Pasal 7 ayat (1) huruf f:* Menjamin kelangsungan penyelenggaraan dan pengelolaan perpustakaan sesuai Standar Nasional Perpustakaan (SNP).
   * *Pasal 11:* Standar Nasional Perpustakaan mencakup standar koleksi, sarana prasarana, pelayanan, tenaga, penyelenggaraan, dan pengelolaan.
2. **Peraturan Perpustakaan Nasional RI Nomor 5 Tahun 2024 tentang Standar Nasional Perpustakaan Perguruan Tinggi:**
   * Menetapkan standar penyelenggaraan perpustakaan universitas, institut, sekolah tinggi, politeknik, dan akademi, termasuk rasio kecukupan koleksi, perputaran bahan pustaka, dan layanan peminjaman/kunjungan per kapita.
3. **Peraturan Perpustakaan Nasional RI Nomor 4 Tahun 2024 tentang Standar Nasional Perpustakaan Sekolah/Madrasah:**
   * Menetapkan standar mutu koleksi, pelayanan sirkulasi, dan pemanfaatan bahan pustaka bagi perpustakaan jenjang SD/MI, SMP/MTs, hingga SMA/SMK/MA.
4. **Peraturan Perpustakaan Nasional RI Nomor 11 Tahun 2021 tentang Standar Nasional Perpustakaan Khusus:**
   * Standar penyelenggaraan perpustakaan lembaga pemerintah, riset, BUMN, dan swasta.
5. **Instrumen Akreditasi 9 Komponen Perpustakaan Nasional RI:**
   * **Komponen 1 (Koleksi Perpustakaan):** Rasio perputaran koleksi (*turnover*), pemanfaatan koleksi aktif, dan analisis koleksi tidur (*dead stock*).
   * **Komponen 3 (Pelayanan Perpustakaan):** Rata-rata pinjaman per kapita, frekuensi kunjungan pemustaka per tahun, dan jam layanan.
   * **Komponen 7 & 8 (Inovasi & Pembudayaan Kegemaran Membaca):** Evaluasi keterpakaian bahan pustaka dan indeks partisipasi pemustaka.
6. **SNI ISO 2789:2013 (*Informasi dan Dokumentasi — Statistik Perpustakaan Internasional*):**
   * Standar acuan resmi Badan Standardisasi Nasional (BSN) untuk pengumpulan data statistik peminjaman, perputaran koleksi, kunjungan, dan populasi pemustaka.
7. **ISO 11620:2014 (*Information and documentation — Library performance indicators*):**
   * Standar internasional indikator kinerja perpustakaan untuk mengevaluasi efektivitas layanan dan kepuasan pemustaka.
8. **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI 2021):**
   * Disusun oleh Hendro Wicaksono dkk. ([GitLab Repository](https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/)) sebagai panduan teknis penerapan query analitik pada database SLiMS.

---

## 📐 Rumus Perhitungan & Definisi Operasional

Plugin ini mengimplementasikan 4 indikator kinerja utama SNI ISO 2789:2013 & SNP Perpusnas RI:

### 1. B.2.1.1 — Perputaran Koleksi (*Collection Turnover Rate*)
* **Tujuan:** Menilai intensitas dan frekuensi perputaran koleksi fisik yang dipinjam oleh pemustaka selama periode satu tahun (Komponen 1 Akreditasi Perpusnas).
* **Rumus Perhitungan:**
  $$\text{Perputaran terhadap Eksemplar} = \frac{\text{Total Transaksi Peminjaman}}{\text{Total Eksemplar Koleksi}}$$
  $$\text{Perputaran terhadap Judul} = \frac{\text{Total Transaksi Peminjaman}}{\text{Total Judul Koleksi}}$$
* **Benchmark SNP Perpusnas RI:**
  * $\ge 1.0$ kali/eksemplar/tahun : **Sangat Baik** (Koleksi berputar sangat dinamis)
  * $0.5 - 0.99$ kali/eksemplar/tahun : **Cukup (Moderat)**
  * $< 0.5$ kali/eksemplar/tahun : **Perlu Peningkatan Promosi & Penyelarasan Kurikulum**

---

### 2. B.2.1.2 — Pinjaman Per Kapita (*Loans per Capita*)
* **Tujuan:** Menilai rata-rata konsumsi bahan pustaka yang dipinjam oleh setiap pemustaka dalam target populasi yang dilayani (Komponen 3 Akreditasi Perpusnas).
* **Rumus Perhitungan:**
  $$\text{Pinjaman Per Kapita} = \frac{\text{Total Transaksi Peminjaman}}{\text{Total Populasi Anggota yang Dilayani}}$$
* **Keterangan Variabel:**
  * *Total Populasi:* Anggota terdaftar dengan status aktif pada periode tahun yang dianalisis (`member_since_date` sebelum tahun berjalan dan `expire_date` melampaui awal tahun).
  * *Opsi Anggota Aktif:* Menghitung khusus anggota yang benar-benar melakukan transaksi peminjaman/kunjungan pada tahun tersebut.

---

### 3. B.2.1.3 — Persentase Koleksi Tidak Digunakan (*Percentage of Dormant Collection*)
* **Tujuan:** Mengukur persentase dokumen dalam koleksi yang **belum pernah dipinjam** selama periode satu tahun untuk evaluasi efektivitas pengadaan bahan pustaka (*dead stock analysis*).
* **Rumus Perhitungan:**
  $$\text{Persentase Koleksi Tidak Digunakan} = \left( \frac{\text{Total Eksemplar yang Belum Pernah Dipinjam}}{\text{Total Seluruh Eksemplar Koleksi}} \right) \times 100\%$$
  $$\text{Tingkat Pemanfaatan Koleksi} = 100\% - \text{Persentase Koleksi Tidak Digunakan}$$
* **Rekomendasi Manajerial Perpusnas RI:**
  * Koleksi tidur $> 50\%$ menandakan perlunya promosi buku baru, reposisi display tematik, atau program penyiangan (*weeding*) terhadap buku yang usang/rusak.

---

### 4. B.2.2.1 — Kunjungan Perpustakaan Per Kapita (*Library Visits per Capita*)
* **Tujuan:** Menilai daya tarik ruang fisik perpustakaan dan keberhasilan layanan dalam menarik kehadiran fisik pemustaka.
* **Rumus Perhitungan:**
  $$\text{Kunjungan Per Kapita} = \frac{\text{Total Kehadiran Kunjungan Pemustaka}}{\text{Total Populasi Anggota yang Dilayani}}$$
* **Benchmark SNP Perpusnas RI:**
  * $\ge 12$ kali/anggota/tahun (rata-rata $\ge 1$ kali per bulan): **Daya Tarik Ruang & Kunjungan Sangat Tinggi**

---

## 🛠️ Panduan Pemasangan (Instalasi)

1. Unduh atau clone repositori ini ke folder plugin SLiMS Anda:
   ```bash
   cd d:/laragon/www/slims/plugins/
   git clone https://github.com/adeism/slims-analisis-kinerja-perpustakaan.git
   ```
2. Masuk ke SLiMS Admin > **System** > **Plugins**.
3. Cari **Analisis Kinerja Perpustakaan (PAKPI)** dan klik tombol **Activate (Aktifkan)**.
4. Akses modul melalui menu **Pelaporan (Reporting)**:
   * **Analisis Kinerja Perpustakaan (PAKPI)** — Dashboard eksekutif, tren multi-tahun, narasi evaluasi SNP Perpusnas, cetak PDF resmi, dan pengaturan kop surat.
   * **Eksplorasi Analisis Kinerja** — Rincian Top 30/100 judul terpopuler, subyek teraktif, anggota peminjam terbanyak, dan daftar koleksi tidur.

---

## 🚀 Fitur Unggulan Versi 1.2.0

- 📊 **Executive Scorecards & Visual Charts:** Kartu metrik ringkas dengan kode warna standar ISO, grafik batang animasi proporsional, dan perbandingan eksemplar/judul.
- 📈 **Tren Multi-Tahun (3–5 Tahun):** Matriks komparasi tahun ke tahun (*Year-over-Year Trend*) yang siap salin ke dokumen borang Akreditasi Perpustakaan Nasional RI.
- 📅 **Distribusi Musiman (Bulanan):** Grafik fluktuasi peminjaman dan kehadiran pengunjung dari Januari hingga Desember.
- 💡 **Evaluasi & Rekomendasi Mutu Otomatis (*Actionable Insights*):** Sistem narasi otomatis berbasis standar terbaru **SNP Perpusnas RI No. 4 & No. 5 Tahun 2024**.
- ⚙️ **Kustomisasi Kop & Penandatangan Laporan (Halaman Pengaturan):**
  * Bebas mengatur Nama Instansi/Kementerian/Universitas, Unit Perpustakaan, Alamat Lengkap, dan Kota.
  * Penandatangan dinamis (bisa disesuaikan 1 s.d. 4 orang: Kepala Perpustakaan, Pustakawan Pengolah, Wakil Rektor/Dekan, Kepala Sekolah, dll.).
- 🖨️ **Ekspor & Cetak PDF Berkualitas Tinggi:**
  * Template dokumen A4 standar instansi dengan garis kop ganda resmi, tabel proporsional, dan penataan kolom tanda tangan yang rapi (*Exact Vector Rendering*).
- 📥 **Ekspor Spreadsheet Microsoft Excel (.xls) & CSV:**
  * **Format Excel (.xls):** Ekspor multi-tabel lengkap berformat rapi dengan identitas kop instansi, judul laporan, tabel rincian metrik, dan blok penandatangan resmi.
  * **Format CSV:** Ekspor data cepat dengan standar RFC 4180 UTF-8 BOM untuk analisis data eksternal.
  * Mendukung ekspor pada seluruh tab (Dashboard Tahunan, Tren Multi-Tahun, Pola Bulanan, Evaluasi Mutu, dan Eksplorasi Top 100).
- 🛡️ **Zero Logout Issue & Cross-DB Compatibility:** Menggunakan sesi native SLiMS (bebas bug logout) dan kompatibel lintas database (MySQL 5.7+, MySQL 8.0+, MariaDB 10.1+).

---

## 📜 Lisensi
Plugin ini dilisensikan di bawah **GNU General Public License v3.0 (GPLv3)**.
Bebas digunakan, dimodifikasi, dan didistribusikan untuk kemajuan perpustakaan di seluruh Indonesia.
