<?php


class Mobil {
    private $dbconn;

    public function __construct()
    {
        $this->dbconn = Database::getInstance();
    }

    public function tambahMobil(array $data) : bool {
        $result = $this->dbconn->query('INSERT INTO mobil (nama_mobil, plat_nomor, harga_sewa_per_hari, status, gambar) VALUES ($1, $2, $3, $4, $5)',
            [
                $data['nama_mobil'],
                strtoupper(trim($data['plat_nomor'])),
                $data['harga_sewa_per_hari'],
                $data['status'] ?? 'tersedia',
                $data['gambar'] ?? null,
            ]
        );

        return $result !== false;
    }

    public function update(int $id, array $data): bool {
        $result = $this->dbconn->query ('UPDATE mobil SET nama_mobil = $1, plat_nomor = $2, harga_sewa_per_hari = $3, status = $4, gambar = $5 WHERE id_mobil = $6',
            [
                $data['nama_mobil'],
                strtoupper(trim($data['plat_nomor'])),
                $data['harga_sewa_per_hari'],
                $data['status'],
                $data['gambar'] ?? null,
                $id,
            ]);

        return $result !== false;
    }

    public function delete(int $id): bool
    {
        $result = $this->dbconn->query('DELETE FROM mobil WHERE id_mobil = $1', [$id]);
        return $result !== false;
    }

    public function getAll(): array
    {
        $result = $this->dbconn->query('SELECT * FROM mobil ORDER BY created_at DESC');
        return $this->dbconn->fetchAll($result);
    }

    public function getAvailable(): array
    {
        $result = $this->dbconn->query('SELECT * FROM mobil WHERE status = $1 ORDER BY nama_mobil ASC', ['tersedia']);
        return $this->dbconn->fetchAll($result);
    }

    public function getById(int $id): ?array
    {
        $result = $this->dbconn->query('SELECT * FROM mobil WHERE id_mobil = $1 LIMIT 1', [$id]);
        return $this->dbconn->fetchOne($result);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $result = $this->dbconn->query('UPDATE mobil SET status = $1 WHERE id_mobil = $2', [$status, $id]);
        return $result !== false;
    }
}