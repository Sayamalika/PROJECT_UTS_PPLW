<?php

class DatabaseException extends Exception
{
}

class Respon
{
    public bool $success;
    public string $message;
    public array $data;

    public function __construct(bool $success, string $message = '', array $data = [])
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
    }
}

class Database
{
    private static ?Database $instance = null;
    private string $host = 'localhost';
    private string $port = '5432';
    private string $dbname = 'persewaan_mobil_pak_rama';
    private string $username = 'postgres';
    private string $password = 'codename0';
    private $dbconn = null;

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
        global $dbconn;

        if (isset($dbconn) && $dbconn) {
            $this->dbconn = $dbconn;
            return;
        }

        $conn_string = "host={$this->host} port={$this->port} dbname={$this->dbname} user={$this->username} password={$this->password}";
        $this->dbconn = @pg_connect($conn_string);

        if (!$this->dbconn) {
            throw new DatabaseException("Koneksi ke database {$this->dbname} tidak dapat dibentuk.");
        }
    }

    public function getConnection()
    {
        return $this->dbconn;
    }

    public function query(string $sql, array $params = [])
    {
        if (empty($params)) {
            return pg_query($this->dbconn, $sql);
        }

        return pg_query_params($this->dbconn, $sql, $params);
    }

    public function fetchOne($result): ?array
    {
        if ($result === false) {
            return null;
        }

        $row = pg_fetch_assoc($result);
        return $row ?: null;
    }

    public function fetchAll($result): array
    {
        if ($result === false) {
            return [];
        }

        $rows = [];

        while ($row = pg_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function beginTransaction(): bool
    {
        $result = @pg_query($this->dbconn, 'BEGIN');
        return $result !== false;
    }

    public function commit(): bool
    {
        $result = @pg_query($this->dbconn, 'COMMIT');
        return $result !== false;
    }

    public function rollBack(): bool
    {
        $result = @pg_query($this->dbconn, 'ROLLBACK');
        return $result !== false;
    }

    public function mulai_transaksi(): void
    {
        if (!$this->dbconn || !$this->beginTransaction()) {
            throw new DatabaseException('Transaksi database tidak dapat dimulai.');
        }
    }

    public function close_connection(): void
    {
        if ($this->dbconn) {
            pg_close($this->dbconn);
            $this->dbconn = null;
        }
    }

    public function __destruct()
    {
        $this->close_connection();
    }
}

class User
{
    private $dbconn;

    public function __construct()
    {
        $this->dbconn = Database::getInstance();
    }

    public function register(string $nama, string $email, string $password, string $role = 'customer'): bool
    {
        $email = trim($email);
        $nama = trim($nama);
        $role = strtolower(trim($role));

        if (!in_array($role, ['admin', 'customer'], true)) {
            throw new InvalidArgumentException('Role tidak valid.');
        }

        if ($nama === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Semua field wajib diisi.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Format email tidak valid.');
        }

        if ($this->findByEmail($email)) {
            throw new RuntimeException('Email sudah terdaftar.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->dbconn->query(
            'INSERT INTO users (nama, email, password, role) VALUES ($1, $2, $3, $4)',
            [$nama, $email, $hash, $role]
        );

        return $result !== false;
    }

    public function login(string $email, string $password, string $role = 'customer'): ?array
    {
        $user = $this->findByEmail(trim($email), $role);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        return $user;
    }

    public function findByEmail(string $email, ?string $role = null): ?array
    {
        if ($role !== null && $role !== '') {
            $result = $this->dbconn->query(
                'SELECT * FROM users WHERE email = $1 AND role = $2 LIMIT 1',
                [trim($email), $role]
            );
            return $this->dbconn->fetchOne($result);
        }

        $result = $this->dbconn->query('SELECT * FROM users WHERE email = $1 LIMIT 1', [trim($email)]);
        return $this->dbconn->fetchOne($result);
    }

    public function getById(int $id): ?array
    {
        $result = $this->dbconn->query('SELECT * FROM users WHERE id_user = $1 LIMIT 1', [$id]);
        return $this->dbconn->fetchOne($result);
    }

    public function getAll(): array
    {
        $result = $this->dbconn->query('SELECT * FROM users ORDER BY created_at DESC');
        return $this->dbconn->fetchAll($result);
    }
}

class Auth {
    public function isLogin() {
        return isset($_SESSION['user']);
    }

    public function currUser() {
        return $_SESSION['user'] ?? null;
    }

    public function login(string $email, string $password, string $role = 'customer'): bool {
        $userModel = new User();
        $user = $userModel->login($email, $password, $role);

        if (!$user) {
            return false;
        }

        $_SESSION['user'] = [
            'id_user' => $user['id_user'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        return true;
    }

    public function logout() {
        session_unset();
        session_destroy();
        header('location: login.php');
        exit;
    }

    public function requiredLogin () {
        if (!$this->isLogin()) {
            header('location: login.php');
            exit;
        }
    }

}

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

class Admin extends User {
    public function getAllUsers(): array
    {
        return $this->getAll();
    }

    public function getAllMobil(): array
    {
        $mobil = new Mobil();
        return $mobil->getAll();
    }

    public function tambahMobil(array $data): bool
    {
        $mobil = new Mobil();
        return $mobil->tambahMobil($data);
    }

    public function ubahMobil(int $id, array $data): bool
    {
        $mobil = new Mobil();
        return $mobil->update($id, $data);
    }

    public function hapusMobil(int $id): bool
    {
        $mobil = new Mobil();
        return $mobil->delete($id);
    }
}