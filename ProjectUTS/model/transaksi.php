<?php

class Transaksi {
    private $dbconn;

    public function __construct()
    {
        $this->dbconn = Database::getInstance();
    }

    public function mulaiTransaksi(int $id_user, int $id_mobil, string $tanggal_sewa, string $tanggal_kembali, string $metode_pembayaran) {
        if ($tanggal_kembali <= $tanggal_sewa) {
            throw new InvalidArgumentException('harus menyewa untuk 1 hari kedepan atau lebih');
        }

        $mobilModel = new Mobil();
        $mobil = $mobilModel->getById($id_mobil);

        if (!$mobil) throw new RuntimeException('mobil tidak ada');

        if ($mobil ['status'] !== 'tersedia') throw new RuntimeException('mobil sedang tidak tersedia untuk disewa');

        if ($this->isMobilBooked($id_mobil, $tanggal_sewa, $tanggal_kembali)) {
            throw new RuntimeException('Mobil sudah dipesan pada rentang tanggal yang Anda pilih.');
        }

        $start = new DateTimeImmutable($tanggal_sewa);
        $end = new DateTimeImmutable($tanggal_kembali);
        $hari = (int) $start->diff($end)->format('%r%a');

        if ($hari <= 0) {
            throw new InvalidArgumentException('Jumlah hari sewa harus lebih dari 0.');
        }

        $totalBiaya = (float) $mobil['harga_sewa_per_hari'] * $hari;

        try {
            $this->dbconn->beginTransaction();

            $result = $this->dbconn->query(
    'INSERT INTO transaksi (
        id_user,
        id_mobil,
        tanggal_sewa,
        tanggal_kembali,
        total_biaya,
        status_transaksi,
        metode_pembayaran,
        status_pembayaran
    ) VALUES ($1,$2,$3,$4,$5,$6,$7,$8)
    RETURNING id_transaksi',
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

            if ($result === false) {
                throw new RuntimeException('Gagal menyimpan transaksi.');
            }

            $inserted = $this->dbconn->fetchOne($result);
            $this->dbconn->commit();

            return [
                'success' => true,
                'id_transaksi' => (int) $inserted['id_transaksi'],
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
            [$id_mobil, 'batal', $tanggal_kembali, $tanggal_sewa]
        );

        $data = $this->dbconn->fetchOne($result);
        return (int) ($data['total'] ?? 0) > 0;
    }

    public function approve(int $idTransaksi): bool
{
    $result = $this->dbconn->query(
        "SELECT * FROM transaksi WHERE id_transaksi = $1",[$idTransaksi]
    );

    $data = $this->dbconn->fetchOne($result);

    if (!$data) {
        return false;
    }

    $this->dbconn->beginTransaction();

    try {

        $this->dbconn->query(
            "UPDATE transaksi SET status_transaksi='disetujui' WHERE id_transaksi=$1",[$idTransaksi]
        );

        $mobil = new Mobil();

        $mobil->updateStatus(
            (int)$data['id_mobil'],
            'disewa'
        );

        $this->dbconn->commit();

        return true;

    } catch (Exception $e) {

        $this->dbconn->rollBack();

        throw $e;
    }
}

public function reject(int $idTransaksi): bool {
    $result = $this->dbconn->query(
        "UPDATE transaksi SET status_transaksi='ditolak' WHERE id_transaksi=$1",[$idTransaksi]
        );

    return $result !== false;
    }
}