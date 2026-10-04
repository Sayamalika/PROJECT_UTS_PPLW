<?php

class Database
{
    private static ?Database $instance = null;
    private string $host = 'localhost';
    private string $port = '3306'; // Port diubah ke default MySQL
    private string $dbname = 'persewaan_mobil';
    private string $username = 'root';
    private string $password = '';
    private ?PDO $dbconn = null; // Tipe data diubah ke PDO

    private function __construct()
    {
        $this->init_connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init_connect(): void
    {
        // Mencegah koneksi ganda
        if ($this->dbconn !== null) {
            return;
        }

        try {
            // Membuat DSN untuk koneksi PDO MySQL
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
            $this->dbconn = new PDO($dsn, $this->username, $this->password);
            
            // Konfigurasi PDO untuk menampilkan Exception jika query error
            $this->dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Melempar error ke DatabaseException bawaan aplikasi
            throw new DatabaseException("Koneksi ke database {$this->dbname} tidak dapat dibentuk. Error: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->dbconn;
    }

    public function query(string $sql, array $params = [])
    {
        try {
            // PENTING: Ubah parameter gaya PostgreSQL ($1, $2, dll) menjadi gaya MySQL (?)
            $sql = preg_replace('/\$[0-9]+/', '?', $sql);
            
            // Siapkan dan eksekusi query
            $stmt = $this->dbconn->prepare($sql);
            
            // array_values digunakan untuk memastikan index array berurutan dari 0
            $stmt->execute(array_values($params));
            
            // Mengembalikan statement untuk di-fetch
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException("Query Error: " . $e->getMessage());
        }
    }

    public function fetchOne($result): ?array
    {
        // Pastikan result adalah objek PDOStatement yang valid
        if (!$result instanceof PDOStatement) {
            return null;
        }

        // Ambil satu baris data
        $row = $result->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function fetchAll($result): array
    {
        if (!$result instanceof PDOStatement) {
            return [];
        }

        // Ambil semua baris data
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    public function beginTransaction(): bool
    {
        if (!$this->dbconn) return false;
        return $this->dbconn->beginTransaction();
    }

    public function commit(): bool
    {
        if (!$this->dbconn) return false;
        return $this->dbconn->commit();
    }

    public function rollBack(): bool
    {
        if (!$this->dbconn) return false;
        return $this->dbconn->rollBack();
    }

    public function mulai_transaksi(): void
    {
        if (!$this->dbconn || !$this->beginTransaction()) {
            throw new DatabaseException('Transaksi database tidak dapat dimulai.');
        }
    }
    public function getLastInsertId(): string|false
    {
        if (!$this->dbconn) return false;
        return $this->dbconn->lastInsertId();
    }
    public function close_connection(): void
    {
        if ($this->dbconn !== null) {
            // Di PDO, menutup koneksi cukup dengan memberi nilai null
            $this->dbconn = null;
        }
    }

    public function __destruct()
    {
        $this->close_connection();
    }
}