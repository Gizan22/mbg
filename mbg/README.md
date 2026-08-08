# Sistem Monitoring MBG (Makan Bergizi Gratis) — v2.1

PHP native + SQLite. UI modern, dark mode, role-based access.

## Fitur

| Fitur | Admin | Petugas | Guru | Siswa |
|-------|:-----:|:-------:|:----:|:-----:|
| Dashboard (khusus peran) | ✓ | ✓ | ✓ | ✓ |
| Data Siswa (CRUD + Import CSV) | ✓ | ✓ | lihat | — |
| Pengambilan MBG + Bulk | ✓ | ✓ | — | lihat |
| Pengembalian MBG | ✓ | ✓ | — | — |
| Stok MBG | ✓ | ✓ | — | — |
| Jadwal Pembagian | ✓ | ✓ | ✓ | ✓ |
| Laporan + Export CSV | ✓ | ✓ | ✓ | — |
| **Kelola Pengguna** | ✓ | — | — | — |
| Notifikasi (buat + tandai dibaca) | ✓ | ✓ | ✓ | ✓ |
| Pencarian | ✓ | ✓ | ✓ | — |
| Riwayat Aktivitas | ✓ | ✓ | — | — |
| Pengaturan / Ganti Password | ✓ | ✓ | ✓ | ✓ |

## Grafik Dashboard
- Tren distribusi 7 hari (line chart)
- Pengambilan per kelas (bar chart)
- Status distribusi (doughnut chart)

Chart.js dimuat dari jsDelivr CDN.

## Cara Menjalankan
```bash
cd mbg
php -S localhost:8000
```
Buka http://localhost:8000

Database `data/mbg.sqlite` dibuat otomatis (hapus file tersebut jika ingin reset data contoh).

## Akun Demo
| Peran | Username | Password |
|-------|----------|----------|
| Admin | admin | admin123 |
| Petugas | petugas | petugas123 |
| Guru | guru | guru123 |
| Siswa | siswa | siswa123 |

## Catatan
- Butuh PHP 8+ dengan ekstensi **pdo_sqlite**
- Password di-hash bcrypt
- Dark mode tersimpan di localStorage


## Penyimpanan data
Aplikasi ini **tidak memakai database** (MySQL/SQLite).
Semua data disimpan sebagai file JSON di folder `data/`:
- `users.json`, `siswa.json`, `stok.json`, `jadwal.json`, dll.

Cukup jalankan dengan PHP built-in server:
```bash
php -S localhost:8000
```
Pastikan folder `data/` dapat ditulis (writable).
