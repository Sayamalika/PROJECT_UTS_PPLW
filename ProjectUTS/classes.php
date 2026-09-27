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
    private string $dbname = 'persewaan_mobil';
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

class User{
    private $dbconn;

    public function __construct()
    {
        $this->dbconn = Database::getInstance();
    }

    public function register(string $nama, string $email, string $password) : bool {
        $nama = trim($nama);
        $email = trim($email);
    
        if ($nama === '' || $email === ''||$password === '') {
            throw new InvalidArgumentException("semua informasi harus diisi");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("format email tidak sesuai!");
        }

        if ($this->findByEmail($email)){
            throw new InvalidArgumentException("email anda sudah terdaftar");
            }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $result = $this->dbconn->query( 'INSERT INTO users (nama, email, password, role) VALUES ($1, $2, $3, $4)', [$nama, $email, $hash, 'customer']);

        return $result !== false;
    }

    public function login(string $email, string $password) : ?array {
        $user = $this->findByEmail(trim($email));

        if (!$user) return null;

        if(!password_verify($password, $user['password'])) return null;

        return $user;
    }

    public function findByEmail(string $email):?array {
        $result = $this->dbconn->query('SELECT * from users where email=$1' , [trim($email)]);
        return $this->dbconn->fetchOne($result);
    }

    public function getByID(int $id): ?array {
        $result = $this->dbconn->query('SELECT * from users where id_user=$1', [$id]);
        return $this->dbconn->fetchAll($result);
    }

    public function getAll(): array {
        $result = $this->dbconn->query('SELECT * FROM users ORDER BY created_at DESC');
        return $this->dbconn->fetchAll($result);
    }
}
