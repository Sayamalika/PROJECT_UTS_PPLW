<?php
class User
{
    private $dbconn;

    public function __construct()
    {
        $this->dbconn = Database::getInstance();
    }

    public function register(string $nama, string $email, string $password, string $role = 'customer', ?string $retypePassword = null): bool
    {
        $email = trim($email);
        $nama = trim($nama);
        $role = strtolower(trim($role));
        $retypePassword = $retypePassword ?? '';

        if (!in_array($role, ['admin', 'customer'], true)) {
            throw new InvalidArgumentException('Role tidak valid.');
        }

        if ($password !== $retypePassword) {
            throw new InvalidArgumentException('Password dan konfirmasi password tidak sama.');
        }

        if ($nama === '' || $email === '' || $password === '' || $retypePassword === '') {
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