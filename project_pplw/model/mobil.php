<?php


class Mobil {
    private $dbconn;

    public function __construct()
    {
        $this->dbconn = Database::getInstance();
    }

    /**
     * Kondisi mobil hanya dua: 'tersedia' (aktif, bisa disewa) dan 'perbaikan'.
     * Status "sedang disewa" TIDAK disimpan di sini, tapi dihitung dari tabel transaksi.
     * Nilai lain (mis. 'disewa' dari data lama) dianggap 'tersedia'.
     */
    private function normalisasiStatus($status): string
    {
        return $status === 'perbaikan' ? 'perbaikan' : 'tersedia';
    }

    public function tambahMobil(array $data) : bool {
        $result = $this->dbconn->query('INSERT INTO mobil (nama_mobil, plat_nomor, harga_sewa_per_hari, status, gambar) VALUES ($1, $2, $3, $4, $5)',
            [
                $data['nama_mobil'],
                strtoupper(trim($data['plat_nomor'])),
                $data['harga_sewa_per_hari'],
                $this->normalisasiStatus($data['status'] ?? 'tersedia'),
                $data['gambar'] ?? null,
            ]
        );

        return $result !== false;
    }

    public function update(int $id, array $data): bool {
        $statusBaru = $this->normalisasiStatus($data['status'] ?? 'tersedia');

        try {
            $this->dbconn->beginTransaction();

            // Kunci baris mobil (sama dengan yang dikunci Transaksi::mulaiTransaksi),
            // sehingga pengecekan pesanan di bawah tidak bisa tersalip pemesanan baru.
            $lama = $this->dbconn->fetchOne(
                $this->dbconn->query('SELECT * FROM mobil WHERE id_mobil = $1 FOR UPDATE', [$id])
            );
            if (!$lama) {
                throw new RuntimeException('Mobil tidak ditemukan.');
            }

            // Hanya diblokir saat berpindah ke Perbaikan.
            if ($statusBaru === 'perbaikan' && $lama['status'] !== 'perbaikan') {
                $aktif = $this->countPesananAktif($id);
                if ($aktif > 0) {
                    throw new RuntimeException(
                        "Mobil ini punya {$aktif} pesanan aktif atau mendatang, jadi belum bisa diubah ke Perbaikan. "
                        . 'Selesaikan atau tolak pesanannya dulu.'
                    );
                }
            }

            $result = $this->dbconn->query ('UPDATE mobil SET nama_mobil = $1, plat_nomor = $2, harga_sewa_per_hari = $3, status = $4, gambar = $5 WHERE id_mobil = $6',
                [
                    $data['nama_mobil'],
                    strtoupper(trim($data['plat_nomor'])),
                    $data['harga_sewa_per_hari'],
                    $statusBaru,
                    $data['gambar'] ?? null,
                    $id,
                ]);

            $this->dbconn->commit();
            return $result !== false;
        } catch (Exception $e) {
            $this->dbconn->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $aktif = $this->countPesananAktif($id);
        if ($aktif > 0) {
            throw new RuntimeException("Mobil ini masih punya {$aktif} pesanan aktif atau mendatang, jadi tidak bisa dihapus.");
        }

        $total = $this->countTransaksi($id);
        if ($total > 0) {
            throw new RuntimeException(
                "Mobil ini punya {$total} riwayat transaksi, jadi tidak bisa dihapus agar riwayatnya tetap utuh. "
                . 'Ubah kondisinya ke Perbaikan kalau tidak ingin ditampilkan lagi.'
            );
        }

        $result = $this->dbconn->query('DELETE FROM mobil WHERE id_mobil = $1', [$id]);
        return $result !== false;
    }

    /** Jumlah pesanan yang masih berjalan atau akan datang (selain yang ditolak). */
    public function countPesananAktif(int $id): int
    {
        $result = $this->dbconn->query(
            'SELECT COUNT(*) AS total FROM transaksi WHERE id_mobil = $1 AND status_transaksi != $2 AND tanggal_kembali > $3',
            [$id, 'ditolak', date('Y-m-d')]
        );
        $row = $this->dbconn->fetchOne($result);
        return (int) ($row['total'] ?? 0);
    }

    /** Jumlah seluruh transaksi (termasuk riwayat) untuk mobil ini. */
    public function countTransaksi(int $id): int
    {
        $result = $this->dbconn->query('SELECT COUNT(*) AS total FROM transaksi WHERE id_mobil = $1', [$id]);
        $row = $this->dbconn->fetchOne($result);
        return (int) ($row['total'] ?? 0);
    }

    public function getAll(): array
    {
        $result = $this->dbconn->query('SELECT * FROM mobil ORDER BY created_at DESC');
        return $this->dbconn->fetchAll($result);
    }

    public function getAvailable(): array
    {
        $result = $this->dbconn->query('SELECT * FROM mobil WHERE status <> $1 ORDER BY nama_mobil ASC', ['perbaikan']);
        return $this->dbconn->fetchAll($result);
    }

    /**
     * Mobil yang bisa disewa pada rentang tanggal tertentu.
     * Syarat: tidak sedang 'perbaikan' DAN tidak ada transaksi (selain 'ditolak')
     * yang rentang tanggalnya tumpang tindih. Aturan overlap sama dengan
     * Transaksi::isMobilBooked().
     */
    public function getAvailableByDate(string $tanggal_sewa, string $tanggal_kembali): array
    {
        $result = $this->dbconn->query(
            'SELECT m.* FROM mobil m
             WHERE m.status <> $1
               AND NOT EXISTS (
                   SELECT 1 FROM transaksi t
                   WHERE t.id_mobil = m.id_mobil
                     AND t.status_transaksi != $2
                     AND t.tanggal_sewa < $3
                     AND t.tanggal_kembali > $4
               )
             ORDER BY m.nama_mobil ASC',
            ['perbaikan', 'ditolak', $tanggal_kembali, $tanggal_sewa]
        );
        return $this->dbconn->fetchAll($result);
    }

    public function getById(int $id): ?array
    {
        $result = $this->dbconn->query('SELECT * FROM mobil WHERE id_mobil = $1 LIMIT 1', [$id]);
        return $this->dbconn->fetchOne($result);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $result = $this->dbconn->query('UPDATE mobil SET status = $1 WHERE id_mobil = $2', [$this->normalisasiStatus($status), $id]);
        return $result !== false;
    }
}
