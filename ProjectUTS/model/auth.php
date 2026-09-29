<?php

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

    public function requireRole(string $role): void
    {
        $this->requiredLogin();

        if (($this->currUser()['role'] ?? null) !== $role) {
            header('Location: dashboard.php');
            exit;
        }
    }

}