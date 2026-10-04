<?php

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