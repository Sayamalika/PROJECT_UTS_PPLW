USE persewaan_mobil;

CREATE TABLE IF NOT EXISTS users (
    id_user SERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer' CHECK (role IN ('admin', 'customer')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mobil (
    id_mobil SERIAL PRIMARY KEY,
    nama_mobil VARCHAR(100) NOT NULL,
    plat_nomor VARCHAR(20) UNIQUE NOT NULL,
    harga_sewa_per_hari NUMERIC(10,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'tersedia' CHECK (status IN ('tersedia', 'disewa', 'perbaikan')),
    gambar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transaksi (
    id_transaksi SERIAL PRIMARY KEY,
    id_user BIGINT UNSIGNED NOT NULL,
    id_mobil BIGINT UNSIGNED NOT NULL,
    tanggal_sewa DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    total_biaya NUMERIC(10,2) NOT NULL,
    status_transaksi VARCHAR(20) DEFAULT 'menunggu' CHECK (status_transaksi IN ('menunggu', 'disetujui', 'ditolak', 'selesai')),
    metode_pembayaran VARCHAR(50),
    status_pembayaran VARCHAR(30) DEFAULT 'belum_bayar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user),
    FOREIGN KEY (id_mobil) REFERENCES mobil(id_mobil)
);

INSERT INTO users (nama, email, password, role)
VALUES (
    'Administrator',
    'admin@demo.com',
    'admin1234',
    'admin'
);

INSERT INTO mobil (nama_mobil, plat_nomor, harga_sewa_per_hari, status, gambar)
VALUES
    ('Toyota Avanza', 'L 1234 ABC', 350000, 'tersedia', 'avanza.jpg'),
    ('Honda Brio', 'L 5678 DEF', 300000, 'tersedia', 'brio.jpg'),
    ('Suzuki Ertiga', 'L 9012 GHI', 400000, 'tersedia', 'ertiga.jpg');
    
    SELECT *
    FROM users;
    
    ALTER TABLE transaksi
ADD COLUMN metode_pembayaran VARCHAR(50),
ADD COLUMN status_pembayaran VARCHAR(30) DEFAULT 'belum_bayar';

ALTER TABLE transaksi DROP CONSTRAINT transaksi_chk_1;

ALTER TABLE transaksi ADD CONSTRAINT transaksi_chk_new 
CHECK (status_transaksi IN ('menunggu', 'disetujui', 'ditolak', 'selesai', 'berjalan', 'batal'));