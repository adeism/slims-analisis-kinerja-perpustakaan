# Plugin Analisis Kinerja Perpustakaan (PAKPI) — SLiMS 9 Bulian

Plugin resmi SLiMS 9 Bulian untuk menganalisis, mengevaluasi, dan menyusun laporan kinerja perpustakaan berdasarkan standar terbaru **Perpustakaan Nasional Republik Indonesia (Perpusnas RI)**, standar internasional **SNI ISO 2789:2013**, **ISO 11620:2014**, serta **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI)**.

Plugin ini dirancang khusus untuk mempermudah Pustakawan dan Kepala Perpustakaan dalam penyusunan **Borang Akreditasi 9 Komponen Perpustakaan Nasional RI (Perpusnas)** maupun **Akreditasi Institusi / Program Studi (BAN-PT / LAM)**.

---

## ⚖️ Dasar Hukum & Tautan Dokumen Referensi Resmi

Pengembangan dan formulasi indikator dalam plugin ini mengacu secara ketat pada regulasi resmi terkini dari **Perpustakaan Nasional Republik Indonesia (Perpusnas RI)** dan Badan Standardisasi Nasional (BSN):

1. 📜 **[Undang-Undang Republik Indonesia Nomor 43 Tahun 2007 tentang Perpustakaan](https://jdih.perpusnas.go.id/)**
   * *Pasal 7 ayat (1) huruf f:* Menjamin kelangsungan penyelenggaraan dan pengelolaan perpustakaan sesuai Standar Nasional Perpustakaan (SNP).
   * *Pasal 11:* Standar Nasional Perpustakaan mencakup standar koleksi, sarana prasarana, pelayanan, tenaga, penyelenggaraan, dan pengelolaan.
   * 🔗 Unduh Dokumen: [JDIH Perpusnas RI](https://jdih.perpusnas.go.id/) | [Arsip JDIHN BPK RI](https://peraturan.bpk.go.id/Details/39907/uu-no-43-tahun-2007)

2. 🏛️ **[Peraturan Perpustakaan Nasional RI Nomor 5 Tahun 2024 tentang Standar Nasional Perpustakaan Perguruan Tinggi](https://jdih.perpusnas.go.id/)**
   * Standar acuan resmi terbaru bagi universitas, institut, sekolah tinggi, politeknik, dan akademi terkait kecukupan koleksi, rasio perputaran buku, dan volume layanan peminjaman/kunjungan tahunan.
   * 🔗 Unduh Dokumen: [JDIH Perpusnas RI](https://jdih.perpusnas.go.id/) | [Arsip JDIHN BPK RI No. 5/2024](https://peraturan.bpk.go.id/Details/295328/peraturan-perpusnas-no-5-tahun-2024)

3. 🏫 **[Peraturan Perpustakaan Nasional RI Nomor 4 Tahun 2024 tentang Standar Nasional Perpustakaan Sekolah/Madrasah](https://jdih.perpusnas.go.id/)**
   * Standar baku mutu koleksi, rasio sirkulasi bahan pustaka per siswa/guru, dan keterpakaian koleksi perpustakaan SD/MI, SMP/MTs, dan SMA/SMK/MA.
   * 🔗 Unduh Dokumen: [JDIH Perpusnas RI](https://jdih.perpusnas.go.id/) | [Arsip JDIHN BPK RI No. 4/2024](https://peraturan.bpk.go.id/Details/295327/peraturan-perpusnas-no-4-tahun-2024)

4. 🏢 **[Peraturan Perpustakaan Nasional RI Nomor 2 Tahun 2024 tentang Standar Nasional Perpustakaan Umum](https://jdih.perpusnas.go.id/)**
   * Standar acuan bagi perpustakaan umum tingkat provinsi, kabupaten/kota, kecamatan, dan desa/kelurahan.
   * 🔗 Unduh Dokumen: [JDIH Perpusnas RI](https://jdih.perpusnas.go.id/) | [Arsip JDIHN BPK RI No. 2/2024](https://peraturan.bpk.go.id/Details/295326/peraturan-perpusnas-no-2-tahun-2024)

5. 🔬 **[Peraturan Perpustakaan Nasional RI Nomor 11 Tahun 2021 tentang Standar Nasional Perpustakaan Khusus](https://jdih.perpusnas.go.id/)**
   * Standar penyelenggaraan perpustakaan pada kementerian, lembaga riset/pemerintah, BUMN, dan institusi swasta.
   * 🔗 Unduh Dokumen: [JDIH Perpusnas RI](https://jdih.perpusnas.go.id/) | [Arsip JDIHN BPK RI No. 11/2021](https://peraturan.bpk.go.id/Details/210457/peraturan-perpusnas-no-11-tahun-2021)

6. 🎖️ **[Instrumen Akreditasi 9 Komponen Perpustakaan Nasional RI (SIMASKA)](https://akreditasi.perpusnas.go.id/)**
   * Diterbitkan oleh Direktorat Standardisasi dan Akreditasi Perpustakaan Nasional RI untuk akreditasi institusi perpustakaan:
     * **Komponen 1 (Koleksi Perpustakaan):** Rasio perputaran koleksi (*turnover rate*), persentase koleksi aktif, dan penanganan koleksi tidur (*dead stock*).
     * **Komponen 3 (Pelayanan Perpustakaan):** Rata-rata pinjaman per kapita dan frekuensi kunjungan pemustaka ($\ge 12$ kali/anggota/tahun).
     * **Komponen 7 & 8 (Inovasi & Pembudayaan Literasi):** Pemanfaatan ruang baca dan indeks partisipasi pemustaka.
   * 🔗 Portal Akreditasi Resmi: [SIMASKA Perpustakaan Nasional RI](https://akreditasi.perpusnas.go.id/)

7. 🌐 **[SNI ISO 2789:2013 — Informasi dan Dokumentasi: Statistik Perpustakaan Internasional](https://pesta.bsn.go.id/produk/detail/9512-sniiso27892013)**
   * Standar resmi Badan Standardisasi Nasional (BSN) yang diadopsi dari ISO 2789 untuk standarisasi pengumpulan metrik perpustakaan.
   * 🔗 Akses katalog: [Katalog PESTA BSN](https://pesta.bsn.go.id/produk/detail/9512-sniiso27892013)

8. 📊 **[ISO 11620:2014 — Information and documentation: Library performance indicators](https://www.iso.org/standard/56757.html)**
   * Standar internasional tolak ukur evaluasi kinerja perpustakaan.
   * 🔗 Portal resmi: [ISO.org Standard 11620](https://www.iso.org/standard/56757.html)

9. 💻 **[Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI 2021)](https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/)**
   * Disusun oleh Hendro Wicaksono dkk. sebagai panduan teknis penerapan query analitik berbasis database SLiMS.
   * 🔗 Repositori resmi: [GitLab Hendro Wicaksono (PAKPI)](https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/)

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
