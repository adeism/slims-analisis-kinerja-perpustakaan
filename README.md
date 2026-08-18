# Plugin Analisis Kinerja Perpustakaan (PAKPI) — SLiMS 9 Bulian

Plugin resmi SLiMS 9 Bulian untuk menganalisis, mengevaluasi, dan menyusun laporan kinerja perpustakaan berdasarkan standar internasional **SNI ISO 2789:2013**, **ISO 11620:2014**, serta **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI)**.

Plugin ini dirancang untuk mempermudah Pustakawan dan Kepala Perpustakaan dalam penyusunan **Borang Akreditasi Perpustakaan Nasional RI (Perpusnas)** maupun **Akreditasi Institusi / Program Studi (BAN-PT / LAM)**.

---

## ⚖️ Dasar Hukum & Referensi Standar

Pengembangan dan perhitungan indikator dalam plugin ini berlandaskan pada regulasi dan pedoman resmi berikut:

1. **Undang-Undang Republik Indonesia No. 43 Tahun 2007 tentang Perpustakaan:**
   * *Pasal 7 ayat (1) huruf f:* Menjamin kelangsungan penyelenggaraan dan pengelolaan perpustakaan sesuai Standar Nasional Perpustakaan (SNP).
   * *Pasal 11:* Standar Nasional Perpustakaan mencakup standar koleksi, sarana prasarana, pelayanan, tenaga, penyelenggaraan, dan pengelolaan.
2. **SNI ISO 2789:2013 (*Informasi dan Dokumentasi — Statistik Perpustakaan Internasional*):**
   * Standar acuan resmi Badan Standardisasi Nasional (BSN) untuk pengumpulan data statistik peminjaman, perputaran koleksi, kunjungan, dan populasi pemustaka.
3. **ISO 11620:2014 (*Information and documentation — Library performance indicators*):**
   * Standar internasional mengenai indikator kinerja perpustakaan untuk mengevaluasi efektivitas layanan dan pemanfaatan sumber daya perpustakaan.
4. **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI 2021):**
   * Disusun oleh Hendro Wicaksono dkk. ([GitLab Repository](https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/)) sebagai panduan teknis penerapan query SQL analitik pada database SLiMS.
5. **Standar Nasional Perpustakaan (SNP) Perpusnas RI:**
   * SNP 002:2011 (Perpustakaan Perguruan Tinggi), SNP 003:2011 (Perpustakaan Sekolah), dan SNP 004:2011 (Perpustakaan Khusus).

---

## 📐 Rumus Perhitungan & Definisi Operasional

Plugin ini mengimplementasikan 4 indikator kinerja utama SNI ISO 2789:2013:

### 1. B.2.1.1 — Perputaran Koleksi (*Collection Turnover Rate*)
* **Tujuan:** Menilai intensitas dan frekuensi perputaran koleksi fisik yang dipinjam oleh pemustaka selama periode satu tahun.
* **Rumus Perhitungan:**
  $$\text{Perputaran terhadap Eksemplar} = \frac{\text{Total Transaksi Peminjaman}}{\text{Total Eksemplar Koleksi}}$$
  $$\text{Perputaran terhadap Judul} = \frac{\text{Total Transaksi Peminjaman}}{\text{Total Judul Koleksi}}$$
* **Interpretasi Nilai:**
  * Semakin tinggi nilainya, semakin aktif koleksi buku berputar dan dimanfaatkan. Nilai di atas `1.0` menunjukkan koleksi dipinjam rata-rata lebih dari satu kali per eksemplar per tahun.

---

### 2. B.2.1.2 — Pinjaman Per Kapita (*Loans per Capita*)
* **Tujuan:** Menilai rata-rata konsumsi bahan pustaka yang dipinjam oleh setiap pemustaka dalam target populasi yang dilayani.
* **Rumus Perhitungan:**
  $$\text{Pinjaman Per Kapita} = \frac{\text{Total Transaksi Peminjaman}}{\text{Total Populasi Anggota yang Dilayani}}$$
* **Keterangan Variabel:**
  * *Total Populasi:* Anggota terdaftar dengan status aktif pada periode tahun yang dianalisis (`member_since_date` sebelum tahun berjalan dan `expire_date` melampaui awal tahun).
  * *Opsi Anggota Aktif:* Menghitung khusus anggota yang benar-benar melakukan transaksi peminjaman/kunjungan pada tahun tersebut.

---

### 3. B.2.1.3 — Persentase Koleksi Tidak Digunakan (*Percentage of Dormant Collection*)
* **Tujuan:** Mengukur persentase dokumen dalam koleksi yang **belum pernah dipinjam** selama periode satu tahun untuk mengevaluasi keselarasan pengadaan buku dengan minat baca pemustaka (*dead stock analysis*).
* **Rumus Perhitungan:**
  $$\text{Persentase Koleksi Tidak Digunakan} = \left( \frac{\text{Total Eksemplar yang Belum Pernah Dipinjam}}{\text{Total Seluruh Eksemplar Koleksi}} \right) \times 100\%$$
  $$\text{Tingkat Pemanfaatan Koleksi} = 100\% - \text{Persentase Koleksi Tidak Digunakan}$$
* **Interpretasi Nilai:**
  * Persentase koleksi tidur yang tinggi (> 60%) menjadi indikasi perlunya promosi buku baru, reposisi display tematik, atau penyiangan (*weeding*) terhadap buku yang usang.

---

### 4. B.2.2.1 — Kunjungan Perpustakaan Per Kapita (*Library Visits per Capita*)
* **Tujuan:** Menilai daya tarik ruang fisik perpustakaan dan keberhasilan layanan dalam menarik pemustaka untuk hadir langsung.
* **Rumus Perhitungan:**
  $$\text{Kunjungan Per Kapita} = \frac{\text{Total Kehadiran Kunjungan Pemustaka}}{\text{Total Populasi Anggota yang Dilayani}}$$
* **Interpretasi Nilai:**
  * Menghitung rata-rata frekuensi seorang anggota datang ke perpustakaan dalam setahun penuh.

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
   * **Analisis Kinerja Perpustakaan (PAKPI)** — Dashboard eksekutif, tren multi-tahun, dan narasi evaluasi.
   * **Eksplorasi Analisis Kinerja** — Rincian Top 30 judul terpopuler, subyek teraktif, anggota peminjam terbanyak, dan daftar koleksi tidur.

---

## 🚀 Fitur Unggulan Versi 1.1.0

- 📊 **Executive Scorecards:** Kartu metrik ringkas dengan kode warna standar ISO.
- 📈 **Tren Multi-Tahun (3–5 Tahun):** Matriks komparasi tahun ke tahun (*Year-over-Year Trend*) yang siap salin ke dokumen borang akreditasi.
- 📅 **Distribusi Musiman (Bulanan):** Grafik fluktuasi peminjaman dan kehadiran pengunjung dari Januari hingga Desember.
- 💡 **Evaluasi & Rekomendasi Mutu Otomatis (*Actionable Insights*):** Sistem narasi otomatis yang memberikan saran manajerial bagi pustakawan.
- 📥 **Ekspor CSV (Excel Ready):** Dilengkapi UTF-8 BOM untuk kompatibilitas langsung tanpa konversi di Microsoft Excel.
- 🖨️ **Print Sheet Resmi:** Tata letak cetak ramah printer dengan kolom tanda tangan resmi Kepala Perpustakaan & Pustakawan Pengolah.
- 🛡️ **Zero Logout Issue & Cross-DB Compatibility:** Menggunakan sesi native SLiMS (bebas bug logout) dan kompatibel lintas database (MySQL 5.7+, MySQL 8.0+, MariaDB 10.1+).

---

## 📜 Lisensi
Plugin ini dilisensikan di bawah **GNU General Public License v3.0 (GPLv3)**.
Bebas digunakan, dimodifikasi, dan didistribusikan untuk kemajuan perpustakaan di seluruh Indonesia.
