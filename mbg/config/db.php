<?php
/**
 * config/db.php — Penyimpanan data FILE JSON (tanpa database)
 * Semua data di folder data/*.json
 */

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

/** Path file JSON untuk satu tabel */
function db_path(string $table): string {
    global $dataDir;
    return $dataDir . '/' . $table . '.json';
}

/** Baca seluruh baris tabel */
function db_all(string $table): array {
    $file = db_path($table);
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Simpan seluruh tabel */
function db_save(string $table, array $rows): void {
    $file = db_path($table);
    file_put_contents(
        $file,
        json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

/** ID berikutnya */
function db_next_id(array $rows): int {
    $max = 0;
    foreach ($rows as $r) {
        $id = (int)($r['id'] ?? 0);
        if ($id > $max) $max = $id;
    }
    return $max + 1;
}

/** Cari by id */
function db_find(string $table, $id): ?array {
    foreach (db_all($table) as $row) {
        if ((int)$row['id'] === (int)$id) return $row;
    }
    return null;
}

/** Filter dengan callback, return array */
function db_filter(string $table, callable $fn): array {
    return array_values(array_filter(db_all($table), $fn));
}

/** Insert, return id baru */
function db_insert(string $table, array $row): int {
    $rows = db_all($table);
    $id = db_next_id($rows);
    $row['id'] = $id;
    $rows[] = $row;
    db_save($table, $rows);
    return $id;
}

/** Update by id */
function db_update(string $table, $id, array $patch): bool {
    $rows = db_all($table);
    $found = false;
    foreach ($rows as &$row) {
        if ((int)$row['id'] === (int)$id) {
            $row = array_merge($row, $patch);
            $row['id'] = (int)$id;
            $found = true;
            break;
        }
    }
    unset($row);
    if ($found) db_save($table, $rows);
    return $found;
}

/** Delete by id */
function db_delete(string $table, $id): bool {
    $rows = db_all($table);
    $new = [];
    $found = false;
    foreach ($rows as $row) {
        if ((int)$row['id'] === (int)$id) {
            $found = true;
            continue;
        }
        $new[] = $row;
    }
    if ($found) db_save($table, $new);
    return $found;
}

/** Delete where callback true */
function db_delete_where(string $table, callable $fn): int {
    $rows = db_all($table);
    $new = [];
    $count = 0;
    foreach ($rows as $row) {
        if ($fn($row)) { $count++; continue; }
        $new[] = $row;
    }
    if ($count) db_save($table, $new);
    return $count;
}

/** Count rows (optional filter) */
function db_count(string $table, ?callable $fn = null): int {
    $rows = db_all($table);
    if (!$fn) return count($rows);
    return count(array_filter($rows, $fn));
}

/** Seed data awal jika belum ada file users */
function db_seed_if_empty(): void {
    if (file_exists(db_path('users'))) return;

    $hash = function ($p) { return password_hash($p, PASSWORD_DEFAULT); };

    db_save('users', [
        ['id'=>1,'username'=>'admin','password'=>$hash('admin123'),'role'=>'admin','nama'=>'Administrator','created_at'=>date('Y-m-d H:i:s')],
        ['id'=>2,'username'=>'petugas','password'=>$hash('petugas123'),'role'=>'petugas','nama'=>'Petugas MBG','created_at'=>date('Y-m-d H:i:s')],
        ['id'=>3,'username'=>'guru','password'=>$hash('guru123'),'role'=>'guru','nama'=>'Guru Pembimbing','created_at'=>date('Y-m-d H:i:s')],
        ['id'=>4,'username'=>'siswa','password'=>$hash('siswa123'),'role'=>'siswa','nama'=>'Andi Pratama','created_at'=>date('Y-m-d H:i:s')],
    ]);

    $siswa = [
        ['id'=>1,'nis'=>'2024001','nama'=>'Andi Pratama','kelas'=>'X','jurusan'=>'RPL','ttl'=>'Jakarta, 12 Jan 2009','alamat'=>'Jl. Merdeka 1','status_ambil'=>0,'status_kembali'=>0],
        ['id'=>2,'nis'=>'2024002','nama'=>'Budi Santoso','kelas'=>'X','jurusan'=>'AKL','ttl'=>'Bandung, 5 Mar 2009','alamat'=>'Jl. Melati 2','status_ambil'=>1,'status_kembali'=>0],
        ['id'=>3,'nis'=>'2024003','nama'=>'Citra Lestari','kelas'=>'XI','jurusan'=>'MPLB','ttl'=>'Surabaya, 20 Jul 2008','alamat'=>'Jl. Mawar 3','status_ambil'=>1,'status_kembali'=>1],
        ['id'=>4,'nis'=>'2024004','nama'=>'Dewi Anggraini','kelas'=>'XI','jurusan'=>'RPL','ttl'=>'Medan, 8 Sep 2008','alamat'=>'Jl. Kenanga 4','status_ambil'=>0,'status_kembali'=>0],
        ['id'=>5,'nis'=>'2024005','nama'=>'Eko Wijaya','kelas'=>'XII','jurusan'=>'AKL','ttl'=>'Semarang, 15 Feb 2007','alamat'=>'Jl. Anggrek 5','status_ambil'=>1,'status_kembali'=>0],
        ['id'=>6,'nis'=>'2024006','nama'=>'Fitri Handayani','kelas'=>'XII','jurusan'=>'MPLB','ttl'=>'Yogyakarta, 30 Nov 2007','alamat'=>'Jl. Dahlia 6','status_ambil'=>0,'status_kembali'=>0],
        ['id'=>7,'nis'=>'2024007','nama'=>'Gilang Ramadhan','kelas'=>'X','jurusan'=>'RPL','ttl'=>'Bekasi, 3 Apr 2009','alamat'=>'Jl. Flamboyan 7','status_ambil'=>1,'status_kembali'=>1],
        ['id'=>8,'nis'=>'2024008','nama'=>'Hana Putri','kelas'=>'XI','jurusan'=>'AKL','ttl'=>'Depok, 18 Jun 2008','alamat'=>'Jl. Cempaka 8','status_ambil'=>0,'status_kembali'=>0],
    ];
    db_save('siswa', $siswa);

    db_save('pengambilan', [
        ['id'=>1,'siswa_id'=>2,'waktu'=>date('Y-m-d').' 07:30','petugas'=>'Petugas MBG','status'=>'Selesai'],
        ['id'=>2,'siswa_id'=>3,'waktu'=>date('Y-m-d').' 07:35','petugas'=>'Petugas MBG','status'=>'Selesai'],
        ['id'=>3,'siswa_id'=>5,'waktu'=>date('Y-m-d').' 07:40','petugas'=>'Petugas MBG','status'=>'Selesai'],
        ['id'=>4,'siswa_id'=>7,'waktu'=>date('Y-m-d').' 07:45','petugas'=>'Petugas MBG','status'=>'Selesai'],
    ]);

    db_save('pengembalian', [
        ['id'=>1,'siswa_id'=>3,'waktu_ambil'=>date('Y-m-d').' 07:35','status_pengembalian'=>'Sudah Dikembalikan','kondisi_wadah'=>'Baik','petugas'=>'Petugas MBG'],
        ['id'=>2,'siswa_id'=>7,'waktu_ambil'=>date('Y-m-d').' 07:45','status_pengembalian'=>'Sudah Dikembalikan','kondisi_wadah'=>'Baik','petugas'=>'Petugas MBG'],
    ]);

    $today = date('Y-m-d');
    $stok = [];
    for ($i = 6; $i >= 0; $i--) {
        $t = date('Y-m-d', strtotime("-$i days"));
        $stok[] = [
            'id' => 7 - $i,
            'tanggal' => $t,
            'disediakan' => 120,
            'dibagikan' => 90 + ($i % 5) * 3,
            'jumlah_wadah' => 120,
            'wadah_kembali' => 70 + ($i % 4) * 5,
            'wadah_belum_kembali' => 20 + ($i % 3) * 2,
        ];
    }
    db_save('stok', $stok);

    db_save('jadwal', [
        ['id'=>1,'tanggal'=>$today,'jam_mulai'=>'07:00','jam_selesai'=>'08:00','kelas'=>'X','lokasi'=>'Aula Utama','petugas'=>'Petugas MBG'],
        ['id'=>2,'tanggal'=>$today,'jam_mulai'=>'08:00','jam_selesai'=>'09:00','kelas'=>'XI','lokasi'=>'Aula Utama','petugas'=>'Petugas MBG'],
        ['id'=>3,'tanggal'=>date('Y-m-d', strtotime('+1 day')),'jam_mulai'=>'07:00','jam_selesai'=>'08:00','kelas'=>'XII','lokasi'=>'Aula Utama','petugas'=>'Petugas MBG'],
    ]);

    db_save('notifikasi', [
        ['id'=>1,'judul'=>'Stok MBG Hari Ini','pesan'=>'Stok MBG telah disiapkan 120 porsi.','waktu'=>date('Y-m-d H:i'),'dibaca'=>0,'tipe'=>'info'],
        ['id'=>2,'judul'=>'Pengambilan Dimulai','pesan'=>'Siswa kelas X dapat mengambil MBG mulai pukul 07:00.','waktu'=>date('Y-m-d H:i'),'dibaca'=>0,'tipe'=>'sukses'],
        ['id'=>3,'judul'=>'Pengingat Wadah','pesan'=>'Mohon kembalikan wadah setelah selesai makan.','waktu'=>date('Y-m-d H:i'),'dibaca'=>1,'tipe'=>'warning'],
    ]);

    db_save('aktivitas', [
        ['id'=>1,'waktu'=>'07:00','pengguna'=>'Sistem','aktivitas'=>'Seed','keterangan'=>'Data awal dimuat (JSON)'],
        ['id'=>2,'waktu'=>'07:30','pengguna'=>'Petugas MBG','aktivitas'=>'Pengambilan','keterangan'=>'Budi Santoso mengambil MBG'],
        ['id'=>3,'waktu'=>'07:35','pengguna'=>'Petugas MBG','aktivitas'=>'Pengambilan','keterangan'=>'Citra Lestari mengambil MBG'],
    ]);
}

db_seed_if_empty();

// Hapus sqlite lama jika masih ada (opsional, biar tidak membingungkan)
$sqlite = $dataDir . '/mbg.sqlite';
if (file_exists($sqlite)) {
    @unlink($sqlite);
}
