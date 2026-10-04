<?php

class Transaksi {
    private $dbconn;

    public function __construct()
    {
        $this->dbconn = Database::getInstance();
    }

    public function mulaiTransaksi(int $id_user, int $id_mobil, string $tanggal_sewa, string $tanggal_kembali, string $metode_pembayaran) {
        // --- Validasi tanggal ---
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal_sewa);
        $end   = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal_kembali);

        if (!$start || !$end) {
            throw new InvalidArgumentException('Format tanggal tidak valid.');
        }
        if ($start < new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('Tanggal sewa tidak boleh sebelum hari ini.');
        }
        if ($end <= $start) {
            throw new InvalidArgumentException('harus menyewa untuk 1 hari kedepan atau lebih');
        }

        $hari = (int) $start->diff($end)->days;

        try {
            $this->dbconn->beginTransaction();

            // Kunci baris mobil: dua pemesanan bersamaan untuk mobil yang sama
            // diproses berurutan, sehingga cek bentrok di bawah selalu akurat.
            $result = $this->dbconn->query('SELECT * FROM mobil WHERE id_mobil = $1 FOR UPDATE', [$id_mobil]);
            $mobil = $this->dbconn->fetchOne($result);

            if (!$mobil) throw new RuntimeException('mobil tidak ada');

            if ($mobil['status'] === 'perbaikan') throw new RuntimeException('mobil sedang dalam perbaikan dan tidak tersedia untuk disewa');

            if ($this->isMobilBooked($id_mobil, $tanggal_sewa, $tanggal_kembali)) {
                throw new RuntimeException('Mobil sudah dipesan pada rentang tanggal yang Anda pilih.');
            }

            $totalBiaya = (float) $mobil['harga_sewa_per_hari'] * $hari;

            $this->dbconn->query(
                'INSERT INTO transaksi (
                    id_user,
                    id_mobil,
                    tanggal_sewa,
                    tanggal_kembali,
                    total_biaya,
                    status_transaksi,
                    metode_pembayaran,
                    status_pembayaran
                ) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)',
                [
                    $id_user,
                    $id_mobil,
                    $tanggal_sewa,
                    $tanggal_kembali,
                    number_format($totalBiaya, 2, '.', ''),
                    'menunggu',
                    $metode_pembayaran,
                    'belum_bayar'
                ]
            );

            $idTransaksi = $this->dbconn->getLastInsertId();

            if ($idTransaksi === false) {
                throw new RuntimeException('Gagal menyimpan transaksi.');
            }

            $this->dbconn->commit();

            return [
                'success' => true,
                'id_transaksi' => (int) $idTransaksi,
                'total_biaya' => number_format($totalBiaya, 2, '.', ''),
            ];
        } catch (Exception $e) {
            $this->dbconn->rollBack();
            throw $e;
        }
    }

    public function getByUser(int $idUser): array
    {
        $result = $this->dbconn->query(
            'SELECT t.*, m.nama_mobil, m.harga_sewa_per_hari FROM transaksi t JOIN mobil m ON m.id_mobil = t.id_mobil WHERE t.id_user = $1 ORDER BY t.created_at DESC',
            [$idUser]
        );

        return $this->dbconn->fetchAll($result);
    }

    public function getAll(): array
    {
        $result = $this->dbconn->query(
            'SELECT t.*, u.nama, m.nama_mobil FROM transaksi t JOIN users u ON u.id_user = t.id_user JOIN mobil m ON m.id_mobil = t.id_mobil ORDER BY t.created_at DESC'
        );

        return $this->dbconn->fetchAll($result);
    }

    public function isMobilBooked(int $id_mobil, string $tanggal_sewa, string $tanggal_kembali): bool
    {
        $result = $this->dbconn->query(
            'SELECT COUNT(*) AS total FROM transaksi WHERE id_mobil = $1 AND status_transaksi != $2 AND tanggal_sewa < $3 AND tanggal_kembali > $4',
            [$id_mobil, 'ditolak', $tanggal_kembali, $tanggal_sewa]
        );

        $data = $this->dbconn->fetchOne($result);
        return (int) ($data['total'] ?? 0) > 0;
    }

    /**
     * Setujui transaksi. Status mobil TIDAK lagi diubah menjadi 'disewa':
     * ketersediaan sekarang ditentukan per rentang tanggal lewat tabel transaksi.
     */
    public function approve(int $idTransaksi): bool
    {
        $result = $this->dbconn->query(
            'SELECT id_transaksi FROM transaksi WHERE id_transaksi = $1',
            [$idTransaksi]
        );

        if (!$this->dbconn->fetchOne($result)) {
            return false;
        }

        $update = $this->dbconn->query(
            "UPDATE transaksi SET status_transaksi='disetujui' WHERE id_transaksi=$1",
            [$idTransaksi]
        );

        return $update !== false;
    }

    /**
     * Tolak transaksi. Status mobil TIDAK diubah; transaksi 'ditolak'
     * otomatis tidak lagi memblokir tanggal (lihat isMobilBooked).
     */
    public function reject(int $idTransaksi): bool
    {
        $result = $this->dbconn->query(
            'SELECT id_transaksi FROM transaksi WHERE id_transaksi = $1',
            [$idTransaksi]
        );

        if (!$this->dbconn->fetchOne($result)) {
            return false;
        }

        $update = $this->dbconn->query(
            "UPDATE transaksi SET status_transaksi='ditolak' WHERE id_transaksi=$1",
            [$idTransaksi]
        );

        return $update !== false;
    }
}
