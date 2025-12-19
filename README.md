# Plugin Analisis Kinerja Perpustakaan

Plugin untuk menganalisis kinerja perpustakaan berdasarkan **Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI) 2021** dan standar **SNI ISO 2789:2013**.


## 🛠️ Instalasi & Pemasangan

Panduan lengkap untuk memasang plugin ini dapat ditemukan pada tautan berikut: ➡️ [Panduan Pemasangan Plugin SLiMS](https://github.com/adeism/belajarslims/blob/main/belajar-pasang-plugin.md)

<img width="901" height="1778" alt="msedge_C39qFI8ZlC" src="https://github.com/user-attachments/assets/623959da-4624-48f4-a171-a407c349211c" />


## � Filter dan Opsi

Plugin ini menyediakan beberapa filter untuk menganalisis data sesuai kebutuhan:

1. **Tahun**: Pilih tahun yang ingin dianalisis (2015 - sekarang)
2. **Termasuk Perpanjangan**: Menampilkan data perpanjangan buku terpisah dari peminjaman baru (hanya untuk indikator B.2.1.1 dan B.2.1.2)
3. **Hanya Anggota Aktif**: Menghitung hanya anggota yang benar-benar aktif (pernah meminjam/berkunjung) dalam tahun yang dipilih, bukan semua anggota terdaftar (hanya untuk indikator B.2.1.2 dan B.2.2.1)4. **Hanya Tabel**: Menampilkan hanya tabel data tanpa visualisasi grafik atau kartu metric (cocok untuk laporan ringkas atau export)### ⚡ Cara Kerja Filter "Hanya Anggota Aktif"

Filter ini **dinamis mengikuti tahun yang dipilih**:

**Contoh Kasus:**
- **Anggota A**: Aktif meminjam 10 buku di tahun 2021, tidak meminjam lagi di tahun 2022-2025

**Laporan Tahun 2021 dengan ✅ "Hanya Anggota Aktif":**
- Anggota A **DIHITUNG** (karena dia aktif meminjam di tahun 2021)
- Hasil: Pinjaman Per Kapita lebih tinggi karena hanya menghitung anggota yang benar-benar aktif di 2021

**Laporan Tahun 2025 dengan ✅ "Hanya Anggota Aktif":**
- Anggota A **TIDAK DIHITUNG** (karena dia tidak meminjam di tahun 2025)
- Hasil: Hanya anggota yang aktif di 2025 yang dihitung

**Manfaat:**
- Membandingkan kinerja perpustakaan dari tahun ke tahun dengan perhitungan yang konsisten
- Mengetahui tingkat partisipasi anggota aktif di setiap periode
- Analisis tren peminjaman/kunjungan yang lebih akurat

### 📋 Mode Tampilan: "Hanya Tabel"

Jika checkbox **"Hanya Tabel"** dicentang, laporan akan menampilkan:
- ✅ **Hanya tabel data** untuk semua indikator (B.2.1.1, B.2.1.2, B.2.1.3, B.2.2.1)
- ❌ **Tanpa visualisasi**: Grafik bar, kartu metric, dan card statistik tidak ditampilkan
- 📄 **Cocok untuk**:
  - Laporan resmi yang fokus pada angka
  - Export ke Excel/PDF
  - Print dokumen yang lebih ringkas
  - Analisis cepat tanpa distraksi visual

**Cara Menggunakan:**
1. Centang checkbox ☑️ **"Hanya Tabel"** di form filter
2. Klik **"Tampilkan Laporan"**
3. Semua indikator akan menampilkan hanya tabel data

---

## �📊 Fitur Utama

### 1. Analisis Kinerja Perpustakaan
Menu utama yang menampilkan 4 indikator kinerja perpustakaan:

#### **B.2.1.1 - Perputaran Koleksi**
Mengukur seberapa sering koleksi perpustakaan dipinjam dalam satu tahun.

**Data yang Ditampilkan dalam Tabel:**

| Kolom | Keterangan | Sumber Data |
|-------|------------|-------------|
| **Indikator** | Label baris data (Peminjaman/Perpanjangan) | Manual (label) |
| **Total** | Jumlah transaksi peminjaman atau perpanjangan | Tabel `loan` - hitung semua transaksi dengan `loan_date` di tahun terpilih |
| **Total Eksemplar** | Jumlah total eksemplar fisik yang dimiliki | Tabel `item` - hitung semua `item_code` |
| **Nilai** | Rata-rata setiap eksemplar dipinjam berapa kali | Perhitungan: Total ÷ Total Eksemplar |
| **Total Judul** | Jumlah total judul buku yang berbeda | Tabel `biblio` - hitung `biblio_id` unik |
| **Nilai Judul** | Rata-rata setiap judul dipinjam berapa kali | Perhitungan: Total ÷ Total Judul |

**Visualisasi Data:**
- **Grafik Bar**: Perbandingan total peminjaman dan perpanjangan
- **Statistik Koleksi**: Kartu menampilkan:
  - Total Eksemplar (jumlah fisik buku)
  - Total Judul (jumlah judul berbeda)
  - **Rasio Eksemplar per Judul**: Rata-rata setiap judul memiliki berapa eksemplar
    - Contoh: Rasio 1.22 = rata-rata setiap judul punya 1.22 eksemplar

**Contoh Interpretasi:**
- Jika nilai terhadap eksemplar = 0.60, artinya setiap eksemplar rata-rata dipinjam 0.60 kali dalam setahun
- Jika nilai terhadap judul = 0.73, artinya setiap judul rata-rata dipinjam 0.73 kali dalam setahun
- Semakin tinggi nilainya, semakin baik perputaran koleksi perpustakaan

**Opsi Tambahan:**
- ☑️ **Termasuk Perpanjangan**: Jika dicentang, akan menampilkan data perpanjangan terpisah dari peminjaman baru

**Contoh SQL Query:**

```sql
-- Query untuk mendapatkan data perputaran koleksi (tanpa perpanjangan)
WITH 
HitungPeminjaman AS (
    SELECT 
        COUNT(1) AS TotalPeminjaman
    FROM loan AS l 
    INNER JOIN item AS i ON l.item_code = i.item_code
    INNER JOIN biblio AS b ON i.biblio_id = b.biblio_id
    WHERE l.loan_date LIKE '2024-%'  -- Ganti dengan tahun yang dipilih
),
HitungEksemplar AS (
    SELECT 
        COUNT(1) AS TotalEksemplar
    FROM item AS i 
    INNER JOIN biblio AS b ON i.biblio_id = b.biblio_id
),
HitungJudul AS (
    SELECT 
        COUNT(DISTINCT b.biblio_id) AS TotalJudul
    FROM biblio AS b
)
SELECT 
    'Peminjaman' AS Indikator,
    (SELECT TotalPeminjaman FROM HitungPeminjaman) AS Total,
    (SELECT TotalEksemplar FROM HitungEksemplar) AS TotalEksemplar,
    ROUND((SELECT TotalPeminjaman FROM HitungPeminjaman) / 
          (SELECT TotalEksemplar FROM HitungEksemplar), 2) AS Nilai,
    (SELECT TotalJudul FROM HitungJudul) AS TotalJudul,
    ROUND((SELECT TotalPeminjaman FROM HitungPeminjaman) / 
          (SELECT TotalJudul FROM HitungJudul), 2) AS NilaiJudul;
```

---

#### **B.2.1.2 - Pinjaman Per Kapita**
Mengukur rata-rata jumlah peminjaman per anggota perpustakaan.

**Data yang Ditampilkan dalam Tabel:**

| Kolom | Keterangan | Sumber Data |
|-------|------------|-------------|
| **Indikator** | Label baris (Peminjaman/Perpanjangan) | Manual (label) |
| **Total Pinjaman** | Jumlah total transaksi peminjaman dalam tahun terpilih | Tabel `loan` - hitung semua dengan `loan_date LIKE 'YYYY-%'` |
| **Total Populasi** | Jumlah anggota perpustakaan | Tabel `member` - filter berdasarkan opsi yang dipilih (lihat di bawah) |
| **Nilai** | Pinjaman per kapita = rata-rata peminjaman per anggota | Perhitungan: Total Pinjaman ÷ Total Populasi |

**Detail Perhitungan Total Populasi:**
- **Mode Normal** (tanpa centang "Hanya Anggota Aktif"):
  - Menghitung semua anggota yang terdaftar dan masa keanggotaannya masih aktif
  - Filter: `member_since_date < tahun_awal` DAN `expire_date > tahun_sebelumnya`
  
- **Mode Anggota Aktif** (centang "Hanya Anggota Aktif"):
  - Menghitung hanya anggota yang benar-benar meminjam di tahun terpilih
  - Filter: JOIN dengan tabel `loan` WHERE `loan_date` di tahun terpilih
  - Menggunakan `COUNT(DISTINCT member_id)`

**Visualisasi Data:**
- **Grafik Bar**: Perbandingan total pinjaman vs total populasi
- **Kartu Metric**: Nilai per kapita dengan angka besar dan desain netral

**Contoh Interpretasi:**
- Jika nilai = 0.60 per kapita, artinya rata-rata setiap anggota meminjam 0.60 buku dalam setahun
- Jika nilai = 5.0 per kapita, artinya rata-rata setiap anggota meminjam 5 buku dalam setahun
- Nilai tinggi menunjukkan anggota aktif memanfaatkan perpustakaan

**Contoh SQL Query:**

```sql
-- Mode Normal: Semua anggota terdaftar
WITH 
HitungPinjaman AS (
    SELECT 
        COUNT(1) AS TotalPinjaman
    FROM loan AS l 
    WHERE l.loan_date LIKE '2024-%'  -- Tahun yang dipilih
),
HitungPopulasi AS (
    SELECT 
        COUNT(1) AS TotalPopulasi
    FROM member AS m 
    WHERE m.member_since_date < '2024-01-01' 
      AND m.expire_date > '2023-12-31'
)
SELECT 
    'Peminjaman' AS Indikator,
    (SELECT TotalPinjaman FROM HitungPinjaman) AS TotalPinjaman,
    (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi,
    ROUND((SELECT TotalPinjaman FROM HitungPinjaman) / 
          (SELECT TotalPopulasi FROM HitungPopulasi), 2) AS Nilai;
```

```sql
-- Mode Anggota Aktif: Hanya yang meminjam
WITH 
HitungPinjaman AS (
    SELECT 
        COUNT(1) AS TotalPinjaman
    FROM loan AS l 
    WHERE l.loan_date LIKE '2024-%'
),
HitungPopulasi AS (
    SELECT 
        COUNT(DISTINCT m.member_id) AS TotalPopulasi
    FROM member AS m 
    INNER JOIN loan AS l ON m.member_id = l.member_id
    WHERE m.member_since_date < '2024-01-01' 
      AND m.expire_date > '2023-12-31'
      AND l.loan_date LIKE '2024-%'
)
SELECT 
    'Peminjaman' AS Indikator,
    (SELECT TotalPinjaman FROM HitungPinjaman) AS TotalPinjaman,
    (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi,
    ROUND((SELECT TotalPinjaman FROM HitungPinjaman) / 
          (SELECT TotalPopulasi FROM HitungPopulasi), 2) AS Nilai;
```

**Visualisasi Data:**
- Menggunakan **kartu metric** dengan angka besar untuk kemudahan pembacaan
- Desain netral dengan warna biru untuk semua nilai (cocok untuk laporan resmi)
- Label "per kapita" untuk memperjelas konteks angka

**Opsi Tambahan:**
- ☑️ **Hanya Anggota Aktif**: Jika dicentang, hanya menghitung anggota yang pernah meminjam buku **dalam tahun yang dipilih** (bukan semua anggota terdaftar). Filter ini dinamis mengikuti tahun yang dipilih, sehingga anggota yang aktif di tahun 2021 belum tentu dihitung di tahun 2025 jika mereka tidak meminjam lagi.

---

#### **B.2.1.3 - Persentase Koleksi Tidak Digunakan**
Mengidentifikasi tingkat pemanfaatan koleksi perpustakaan berdasarkan peminjaman dalam satu tahun.

**Data yang Ditampilkan dalam Tabel:**

| Kolom | Keterangan | Sumber Data |
|-------|------------|-------------|
| **Indikator** | Label/nama indikator yang diukur | Tabel `loan` (transaksi peminjaman) |
| **Total** | Jumlah eksemplar yang **tidak pernah dipinjam** dalam tahun tersebut | Hitung eksemplar yang tidak ada di tabel `loan` untuk tahun terpilih |
| **Total Eksemplar** | Jumlah total semua eksemplar fisik yang dimiliki perpustakaan | Tabel `item` (semua eksemplar) |
| **Persentase** | Persentase koleksi tidak digunakan = (Total ÷ Total Eksemplar) × 100% | Perhitungan: nilai "Total" dibagi "Total Eksemplar" |

**Visualisasi Data:**
- **Kartu Utama**: Menampilkan **"Tingkat Pemanfaatan Koleksi"** dengan fokus pada nilai positif
  - Angka besar: **Persentase koleksi yang DIGUNAKAN** (bukan yang tidak digunakan)
  - Contoh: Jika 1.69% digunakan, angka yang tampil adalah **1.69%** dengan label "Koleksi Digunakan"
  - Keterangan: "1,725 dari 102,239 eksemplar telah dipinjam dalam tahun ini"

- **Detail Statistik**: 3 kartu informasi dengan warna netral
  - **Eksemplar Digunakan** (warna biru): Jumlah eksemplar yang pernah dipinjam
  - **Belum Dipinjam Tahun Ini** (warna abu-abu netral): Jumlah eksemplar yang belum dipinjam
  - **Total Eksemplar** (warna biru): Jumlah keseluruhan koleksi

**Contoh Interpretasi:**
- Jika tingkat pemanfaatan = 1.69%, artinya 1.69% koleksi telah dipinjam dalam tahun tersebut
- Jika 98.31% belum dipinjam, bisa menunjukkan:
  - Koleksi sangat lengkap dan beragam
  - Ada koleksi referensi yang memang tidak untuk dipinjam
  - Koleksi tertentu perlu dipromosikan
  - Perlu evaluasi untuk koleksi yang benar-benar tidak diminati

**Catatan Penting:**
- Visualisasi dirancang dengan **pendekatan positif** untuk laporan resmi
- Tidak menggunakan warna merah atau label negatif
- Fokus pada pencapaian pemanfaatan, bukan kekurangan

**Contoh SQL Query:**

```sql
-- Query untuk menghitung persentase koleksi tidak digunakan
WITH 
DaftarPinjaman AS (
    -- Daftar item_code yang pernah dipinjam di tahun 2024
    SELECT DISTINCT(l.item_code) AS item_code
    FROM loan AS l
    WHERE l.loan_date LIKE '2024-%'
),
HitungTidakDipinjam AS (
    -- Hitung eksemplar yang TIDAK ada di daftar pinjaman
    SELECT 
        COUNT(1) AS TotalTidakDipinjaman
    FROM item AS i 
    WHERE i.item_code NOT IN (
        SELECT item_code FROM DaftarPinjaman
    )
),
HitungSemuaEksemplar AS (
    -- Hitung total semua eksemplar
    SELECT 
        COUNT(1) AS TotalEksemplar
    FROM item
)
SELECT 
    'Koleksi Tidak Digunakan' AS Indikator,
    (SELECT TotalTidakDipinjaman FROM HitungTidakDipinjam) AS Total,
    (SELECT TotalEksemplar FROM HitungSemuaEksemplar) AS TotalEksemplar,
    ROUND(
        ((SELECT TotalTidakDipinjaman FROM HitungTidakDipinjam) / 
         (SELECT TotalEksemplar FROM HitungSemuaEksemplar)) * 100, 2
    ) AS Persentase;
```

---

#### **B.2.2.1 - Kunjungan Perpustakaan Per Kapita**
Mengukur rata-rata kunjungan perpustakaan per anggota.

**Data yang Ditampilkan dalam Tabel:**

| Kolom | Keterangan | Sumber Data |
|-------|------------|-------------|
| **Indikator** | Label baris (Kunjungan) | Manual (label) |
| **Total Kunjungan** | Jumlah kunjungan tercatat dari sistem presensi | Tabel `visitor_count` - hitung semua dengan `checkin_date LIKE 'YYYY-%'` |
| **Total Populasi** | Jumlah anggota perpustakaan | Tabel `member` - filter berdasarkan opsi yang dipilih (lihat di bawah) |
| **Nilai** | Kunjungan per kapita = rata-rata kunjungan per anggota | Perhitungan: Total Kunjungan ÷ Total Populasi |

**Detail Perhitungan Total Populasi:**
- **Mode Normal** (tanpa centang "Hanya Anggota Aktif"):
  - Menghitung semua anggota yang terdaftar dan masa keanggotaannya masih aktif
  - Filter: `member_since_date < tahun_awal` DAN `expire_date > tahun_sebelumnya`
  
- **Mode Anggota Aktif** (centang "Hanya Anggota Aktif"):
  - Menghitung hanya anggota yang benar-benar berkunjung di tahun terpilih
  - Filter: JOIN dengan tabel `visitor_count` WHERE `checkin_date` di tahun terpilih
  - Menggunakan `COUNT(DISTINCT member_id)`

**Contoh Interpretasi:**
- Jika nilai = 5.2, artinya rata-rata setiap anggota berkunjung 5.2 kali per tahun
- Jika nilai = 15.0, artinya rata-rata setiap anggota berkunjung 15 kali per tahun
- Nilai tinggi menunjukkan perpustakaan ramai dikunjungi

**Contoh SQL Query:**

```sql
-- Mode Normal: Semua anggota terdaftar
WITH 
HitungKunjungan AS (
    SELECT 
        COUNT(1) AS TotalKunjungan
    FROM visitor_count AS vc 
    WHERE vc.checkin_date LIKE '2024-%'  -- Tahun yang dipilih
),
HitungPopulasi AS (
    SELECT 
        COUNT(1) AS TotalPopulasi
    FROM member AS m 
    WHERE m.member_since_date < '2024-01-01' 
      AND m.expire_date > '2023-12-31'
)
SELECT 
    'Kunjungan' AS Indikator,
    (SELECT TotalKunjungan FROM HitungKunjungan) AS TotalKunjungan,
    (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi,
    ROUND((SELECT TotalKunjungan FROM HitungKunjungan) / 
          (SELECT TotalPopulasi FROM HitungPopulasi), 2) AS Nilai;
```

```sql
-- Mode Anggota Aktif: Hanya yang berkunjung
WITH 
HitungKunjungan AS (
    SELECT 
        COUNT(1) AS TotalKunjungan
    FROM visitor_count AS vc 
    WHERE vc.checkin_date LIKE '2024-%'
),
HitungPopulasi AS (
    SELECT 
        COUNT(DISTINCT m.member_id) AS TotalPopulasi
    FROM member AS m 
    INNER JOIN visitor_count AS vc ON m.member_id = vc.member_id
    WHERE m.member_since_date < '2024-01-01' 
      AND m.expire_date > '2023-12-31'
      AND vc.checkin_date LIKE '2024-%'
)
SELECT 
    'Kunjungan' AS Indikator,
    (SELECT TotalKunjungan FROM HitungKunjungan) AS TotalKunjungan,
    (SELECT TotalPopulasi FROM HitungPopulasi) AS TotalPopulasi,
    ROUND((SELECT TotalKunjungan FROM HitungKunjungan) / 
          (SELECT TotalPopulasi FROM HitungPopulasi), 2) AS Nilai;
```

**Visualisasi Data:**
- Menggunakan **kartu metric besar** untuk kemudahan pembacaan
- Desain netral dengan warna biru untuk semua nilai (cocok untuk laporan resmi)
- Label "per kapita" untuk memperjelas konteks angka

**Opsi Tambahan:**
- ☑️ **Hanya Anggota Aktif**: Jika dicentang, hanya menghitung anggota yang pernah berkunjung ke perpustakaan **dalam tahun yang dipilih** (bukan semua anggota terdaftar). Filter ini dinamis mengikuti tahun yang dipilih, sehingga anggota yang aktif berkunjung di tahun 2021 belum tentu dihitung di tahun 2025 jika mereka tidak berkunjung lagi.

---

### 2. Eksplorasi Analisis Kinerja Perpustakaan
Menu eksplorasi untuk analisis mendalam dengan ranking data.

#### **B.2.1.1 - Eksplorasi Perputaran Koleksi**

**📚 Buku Paling Banyak Dipinjam (Top 30)**
- Menampilkan 30 judul buku yang paling sering dipinjam
- Data: Ranking, Judul Buku, ID Biblio, Total Peminjaman
- Badge emas untuk ranking 1-3

**Kegunaan:**
- Mengetahui buku favorit pemustaka
- Referensi untuk pengadaan buku serupa
- Identifikasi tren bacaan

**📑 Subyek Paling Banyak Dipinjam (Top 30)**
- Menampilkan 30 topik/subyek yang paling diminati
- Data: Ranking, Nama Subyek, ID Topik, Total Peminjaman

**Kegunaan:**
- Mengetahui minat baca pemustaka berdasarkan subjek
- Panduan pengembangan koleksi berdasarkan subjek populer

---

#### **B.2.1.2 - Eksplorasi Pinjaman Per Kapita**

**👥 Anggota Paling Banyak Meminjam (Top 30)**
- Menampilkan 30 anggota yang paling aktif meminjam
- Data: Ranking, ID Anggota, Nama Anggota, Jenis Keanggotaan, Total Peminjaman

**Kegunaan:**
- Identifikasi anggota aktif untuk program loyalitas
- Statistik pemanfaatan perpustakaan per anggota

**🏷️ Jenis Keanggotaan Paling Banyak Meminjam (Top 30)**
- Menampilkan tipe keanggotaan (Siswa, Guru, Mahasiswa, dll) yang paling aktif
- Data: Ranking, Jenis Keanggotaan, ID Tipe, Total Peminjaman

**Kegunaan:**
- Mengetahui segmen pemustaka yang paling aktif
- Perencanaan program perpustakaan per segmen

---

## 🎯 Cara Menggunakan

### Menu Analisis Kinerja Perpustakaan

1. Login ke SLiMS sebagai admin
2. Buka menu **Reporting** → **Analisis Kinerja Perpustakaan**
3. Pilih **Tahun** yang ingin dianalisis
4. Centang **☑️ Termasuk Perpanjangan** jika ingin melihat data perpanjangan (opsional)
5. Pilih opsi **Hanya Anggota Aktif** untuk indikator yang membutuhkan (opsional)
6. Klik **📊 Tampilkan Laporan**
7. Data akan muncul dengan visualisasi grafik dan tabel
8. Klik **🖨️ Cetak Halaman Ini** untuk mencetak laporan

### Menu Eksplorasi Analisis Kinerja Perpustakaan

1. Login ke SLiMS sebagai admin
2. Buka menu **Reporting** → **Eksplorasi Analisis Kinerja Perpustakaan**
3. Pilih **Tahun** yang ingin dianalisis
4. Pilih **Indikator** yang ingin dieksplorasi:
   - B.2.1.1 Perputaran Koleksi (Buku & Subyek Terpopuler)
   - B.2.1.2 Pinjaman Per Kapita (Anggota & Jenis Keanggotaan Teraktif)
5. Klik **📊 Tampilkan Data Eksplorasi**
6. Data ranking akan muncul dalam tabel
7. Badge emas (🥇🥈🥉) menandai ranking 1-3
8. Klik **🖨️ Cetak Halaman Ini** untuk mencetak laporan

---

## 📖 Sumber Data

### Tabel Database yang Digunakan:

1. **loan** - Data transaksi peminjaman
2. **item** - Data eksemplar/item buku
3. **biblio** - Data bibliografi/judul buku
4. **member** - Data anggota perpustakaan
5. **mst_member_type** - Data jenis keanggotaan
6. **visitor_count** - Data kunjungan perpustakaan
7. **mst_topic** - Data subjek/topik buku
8. **biblio_topic** - Relasi buku dengan topik

### Periode Data:
- Data dihitung berdasarkan tahun yang dipilih (format: YYYY)
- Contoh: Tahun 2025 = semua transaksi dari 1 Januari 2025 - 31 Desember 2025

---

## 🎨 Fitur Tampilan

### Visualisasi Data:
- **📊 Bar Chart Horizontal**: Perbandingan antar nilai
- **🍩 Donut Chart**: Komposisi koleksi (eksemplar vs judul)
- **🔵 Progress Ring**: Persentase dengan animasi
- **📈 Metric Bars**: Progress bar untuk metrik tertentu
- **📋 Tabel Data**: Data detail dalam format tabel

### Warna Indikator:
- **Biru (#4A90E2)**: Data utama/positif
- **Hijau (#10b981)**: Data digunakan/kunjungan
- **Oranye (#f59e0b)**: Data populasi/pembanding
- **Merah (#ef4444)**: Data tidak digunakan/warning
- **Abu-abu (#e0e0e0)**: Data netral/background

### Ranking Badge:
- **🥇 Top 3**: Badge oranye untuk ranking 1-3
- **🔵 4-30**: Badge biru untuk ranking 4 ke bawah

---

## 📚 Referensi & Standar

### Pedoman Analisis Kinerja Perpustakaan Indonesia (PAKPI) 2021
- Dokumen resmi dari Perpustakaan Nasional RI
- Link: [https://s.id/pakpi2021](https://s.id/pakpi2021)
- Berisi standar indikator kinerja perpustakaan di Indonesia

### SNI ISO 2789:2013
- Standar Nasional Indonesia untuk Statistik Perpustakaan
- Adopsi dari standar internasional ISO 2789
- Mengatur definisi, rumus, dan metode perhitungan statistik perpustakaan

### Repository Sumber:
- GitLab: [https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/](https://gitlab.com/hendrowicaksono/pedoman-analisis-kinerja-perpustakaan-indonesia/)
- Berisi contoh query SQL untuk setiap indikator PAKPI

---

## ⚠️ Catatan Penting

### Persyaratan Data:
1. **Untuk B.2.1.1, B.2.1.2, B.2.1.3**: Harus ada data peminjaman (`loan`) di tahun yang dipilih
2. **Untuk B.2.2.1**: Harus ada data kunjungan (`visitor_count`) - pastikan sistem pencatatan kunjungan aktif
3. **Untuk Eksplorasi**: Data akan kosong jika tidak ada transaksi di tahun tersebut

### Interpretasi Data:
- Nilai **0** atau **sangat rendah** bisa menunjukkan:
  - Perpustakaan baru berdiri
  - Sistem pencatatan belum berjalan optimal
  - Koleksi belum sesuai kebutuhan pemustaka
  - Perlu promosi dan sosialisasi perpustakaan

- Nilai **sangat tinggi** bisa menunjukkan:
  - Perpustakaan aktif digunakan (positif)
  - Koleksi terbatas namun sangat dibutuhkan
  - Perlu penambahan koleksi untuk memenuhi demand

### Rekomendasi Penggunaan:
1. **Analisis Berkala**: Jalankan analisis setiap semester atau tahunan
2. **Perbandingan Tahun**: Bandingkan data antar tahun untuk melihat tren
3. **Benchmark**: Bandingkan dengan perpustakaan sejenis
4. **Action Plan**: Gunakan hasil analisis untuk perbaikan layanan


