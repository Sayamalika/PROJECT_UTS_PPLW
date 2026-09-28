<?php
class Mobil {
    private PDO $conn;
    private string $table_name = "mobil";
    public ?int $id_mobil = null;
    public string $nama_mobil;
    public string $plat_nomor;
    public float $harga_sewa_per_hari;
    public string $status = 'tersedia';
    public ?string $gambar = null;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function readAll(): PDOStatement {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id_mobil DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
      
        return $stmt;
    }

    public function getAvailable(): PDOStatement {
        $query = "SELECT * FROM " . $this->table_name . " WHERE status = 'tersedia' ORDER BY nama_mobil ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
      
        return $stmt;
    }

    public function getById($id): array|bool {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_mobil = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
      
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(): bool {
        $query = "INSERT INTO " . $this->table_name . " (nama_mobil, plat_nomor, harga_sewa_per_hari, status, gambar) 
                  VALUES (:nama, :plat, :harga, :status, :gambar)";
                  
        $stmt = $this->conn->prepare($query);

        $plat = strtoupper(trim($this->plat_nomor));

        $stmt->bindParam(":nama", $this->nama_mobil);
        $stmt->bindParam(":plat", $plat);
        $stmt->bindParam(":harga", $this->harga_sewa_per_hari);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":gambar", $this->gambar);

        return $stmt->execute();
    }

    public function update(): bool {
        $query = "UPDATE " . $this->table_name . " 
                  SET nama_mobil = :nama, plat_nomor = :plat, harga_sewa_per_hari = :harga, status = :status, gambar = :gambar 
                  WHERE id_mobil = :id";
                  
        $stmt = $this->conn->prepare($query);

        $plat = strtoupper(trim($this->plat_nomor));

        $stmt->bindParam(":nama", $this->nama_mobil);
        $stmt->bindParam(":plat", $plat);
        $stmt->bindParam(":harga", $this->harga_sewa_per_hari);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":gambar", $this->gambar);
        $stmt->bindParam(":id", $this->id_mobil);

        return $stmt->execute();
    }

    public function updateStatus(int $id, string $status): bool {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id_mobil = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
      
        return $stmt->execute();
    }

    public function delete(): bool {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_mobil = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_mobil);
      
        return $stmt->execute();
    }
}
