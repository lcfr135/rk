-- =====================================================================
--  DATABASE: casino
--  Dibuat berdasarkan hasil TELUSUR LANGSUNG ke seluruh kode PHP:
--    - config/database.php   (nama DB: "casino", pakai mysqli)
--    - auth/register.php, auth/login.php, auth/heartbeat.php,
--      auth/update-password.php, auth/update-profile.php,
--      auth/upload-avatar.php
--    - member/member.php, member/profile.php, member/game.php,
--      member/game-list.php, member/favorite.php, member/promosi.php,
--      member/logout.php
--    - rtp/rtp_update.php
--
--  CATATAN: admin.php TIDAK menyentuh database sama sekali (semua data
--  member/transaksi di admin.php memakai localStorage browser, murni
--  front-end/mock). Jadi admin.php tidak butuh tabel tambahan apapun
--  supaya bisa dibuka tanpa error.
--
--  Hanya ADA 4 TABEL yang benar-benar dipakai lewat query SQL
--  ($conn->query / $conn->prepare) di seluruh project:
--    1. users
--    2. rtp_live
--    3. user_favorites
--    4. user_activity
-- =====================================================================

CREATE DATABASE IF NOT EXISTS casino
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE casino;

-- =====================================================================
-- 1. TABEL users
--    Dipakai di: register.php, login.php, heartbeat.php, logout.php,
--    update-password.php, update-profile.php, upload-avatar.php,
--    member.php, profile.php, game.php, game-list.php, promosi.php
--
--    - username & email HARUS unique (register.php mengecek error
--      "Duplicate" dari MySQL saat insert)
--    - kolom last_seen, last_active, last_login SEMUA dipakai terpisah
--      di file berbeda -> ketiganya wajib ada walau agak tumpang tindih,
--      supaya tidak ada query yang gagal karena kolom tidak ditemukan
-- =====================================================================
CREATE TABLE users (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username              VARCHAR(50)   NOT NULL,
    email                 VARCHAR(100)  NOT NULL,
    password              VARCHAR(255)  NOT NULL,      -- hasil password_hash() PHP
    referral              VARCHAR(50)   DEFAULT NULL,
    fullname              VARCHAR(100)  DEFAULT NULL,
    phone                 VARCHAR(20)   DEFAULT NULL,
    avatar                VARCHAR(255)  DEFAULT NULL,   -- nama file di uploads/avatars/
    balance               DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    level                 VARCHAR(20)   NOT NULL DEFAULT 'Bronze', -- Bronze/Silver/Gold/Platinum/VIP
    points                INT           NOT NULL DEFAULT 0,
    total_deposit         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_withdraw        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login            DATETIME      DEFAULT NULL,   -- diisi login.php
    last_seen             DATETIME      DEFAULT NULL,   -- diisi heartbeat.php
    last_active           DATETIME      DEFAULT NULL,   -- diisi logout.php
    password_updated_at   DATETIME      DEFAULT NULL,   -- diisi update-password.php
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 2. TABEL rtp_live
--    Dipakai di: rtp_update.php (auto update tiap game), member.php,
--    profile.php, promosi.php, game.php, game-list.php
--
--    - category yang dipakai di filter: slots, live, crash, table
--    - status yang dipakai di filter: HOT, NORMAL, COLD, NEW
--    - trend disimpan sebagai angka (desimal) hasil perubahan RTP
-- =====================================================================
CREATE TABLE rtp_live (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    game_name     VARCHAR(100)  NOT NULL,
    provider      VARCHAR(100)  DEFAULT NULL,
    category      VARCHAR(30)   DEFAULT NULL,
    rtp           DECIMAL(5,2)  NOT NULL DEFAULT 85.00,
    status        VARCHAR(20)   NOT NULL DEFAULT 'NORMAL',
    trend         DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    volatility    VARCHAR(20)   NOT NULL DEFAULT 'Medium',
    last_update   DATETIME      DEFAULT NULL,
    next_update   DATETIME      DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 3. TABEL user_favorites
--    Dipakai di: favorite.php, game.php, game-list.php
-- =====================================================================
CREATE TABLE user_favorites (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    game_id     INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_game (user_id, game_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_game FOREIGN KEY (game_id) REFERENCES rtp_live(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 4. TABEL user_activity
--    Dipakai di: update-password.php, update-profile.php,
--    upload-avatar.php, profile.php (ditampilkan di "Aktivitas Terbaru")
--
--    - kolom icon menyimpan EMOJI (🔑 👤 📷), makanya charset tabel
--      WAJIB utf8mb4 (utf8 biasa di MySQL cuma 3-byte, emoji butuh 4-byte)
-- =====================================================================
CREATE TABLE user_activity (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    icon        VARCHAR(20)   DEFAULT NULL,
    activity    VARCHAR(255)  NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  ISI DATA (SEED) — supaya begitu di-import, web-nya langsung bisa
--  dipakai (login, lihat game, favorite, dll) tanpa tabel kosong.
-- =====================================================================

-- ---------------------------------------------------------------------
-- USERS
-- Password DEMO (sudah di-hash pakai bcrypt, kompatibel dgn
-- password_verify() PHP):
--   user: demo        | pass: demo123
--   user: royalvip     | pass: royalvip123
-- ---------------------------------------------------------------------
INSERT INTO users
(username, email, password, referral, fullname, phone, balance, level, points, total_deposit, total_withdraw, created_at, last_login)
VALUES
('demo', 'demo@royalknight.test',
 '$2b$12$TGGPV8ZQCPvmtivCLfuWtucUg5/yNvIjuboqIaAWVJih3BBuKDnqW',
 '', 'Demo User', '081234567890',
 2500000.00, 'Bronze', 150, 3000000.00, 500000.00,
 NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 1 DAY),

('royalvip', 'vip@royalknight.test',
 '$2b$12$kC6ZqiKu67/RLGQ8CddMAuYHV16YzOh2pquto2vrQDMTFuAWhdoKu',
 'demo', 'Royal VIP', '081298765432',
 15750000.00, 'VIP', 9800, 25000000.00, 9250000.00,
 NOW() - INTERVAL 90 DAY, NOW());


-- ---------------------------------------------------------------------
-- RTP_LIVE  (katalog game — nama diambil dari daftar $gameImages
-- yang ada di game.php & game-list.php, plus asset gambar yang ada
-- di assets/game/ seperti bacc.png, black.png, roulette.jpg, crazy.png)
-- ---------------------------------------------------------------------
INSERT INTO rtp_live
(game_name, provider, category, rtp, status, trend, volatility, last_update, next_update)
VALUES
('Sweet Bonanza',            'Pragmatic Play',      'slots', 96.51, 'HOT',    1.25,  'High',   NOW(), DATE_ADD(NOW(), INTERVAL 35 MINUTE)),
('Gates of Olympus',         'Pragmatic Play',      'slots', 96.50, 'HOT',    0.80,  'High',   NOW(), DATE_ADD(NOW(), INTERVAL 40 MINUTE)),
('Big Bass Bonanza',         'Pragmatic Play',      'slots', 96.71, 'HOT',    0.45,  'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 45 MINUTE)),
('The Dog House Megaways',   'Pragmatic Play',      'slots', 96.55, 'NORMAL', -0.30, 'High',   NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE)),
('Fruit Party',               'Pragmatic Play',      'slots', 96.47, 'NORMAL', 0.10,  'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 50 MINUTE)),
('Dragon Gold',               'Pragmatic Play',      'slots', 95.80, 'NORMAL', -0.65, 'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 55 MINUTE)),
('Starlight Princess',        'Pragmatic Play',      'slots', 96.50, 'NEW',    0.00,  'High',   NOW(), DATE_ADD(NOW(), INTERVAL 60 MINUTE)),
('Buffalo King',              'Pragmatic Play',      'slots', 96.06, 'NORMAL', -0.20, 'High',   NOW(), DATE_ADD(NOW(), INTERVAL 35 MINUTE)),
('Sugar Rush',                'Pragmatic Play',      'slots', 96.50, 'HOT',    1.10,  'High',   NOW(), DATE_ADD(NOW(), INTERVAL 40 MINUTE)),
('Wild Bandito',              'PG Soft',             'slots', 96.61, 'NORMAL', 0.35,  'High',   NOW(), DATE_ADD(NOW(), INTERVAL 45 MINUTE)),
('Mahjong Ways 2',            'PG Soft',             'slots', 96.93, 'HOT',    1.50,  'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE)),
('Lucky Neko',                'PG Soft',             'slots', 96.78, 'NORMAL', 0.55,  'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 50 MINUTE)),
('Wanted Dead or a Wild',     'Hacksaw Gaming',      'slots', 96.38, 'NEW',    0.00,  'High',   NOW(), DATE_ADD(NOW(), INTERVAL 60 MINUTE)),
('Aviator',                   'Spribe',              'crash', 97.00, 'HOT',    2.00,  'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 35 MINUTE)),
('JetX',                      'SmartSoft Gaming',    'crash', 96.00, 'NORMAL', -0.50, 'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 45 MINUTE)),
('Plinko',                    'Spribe',              'crash', 97.30, 'NORMAL', 0.20,  'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 55 MINUTE)),
('Mines',                     'Spribe',              'crash', 97.60, 'NORMAL', 0.10,  'Low',    NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE)),
('Spaceman',                  'Pragmatic Play',      'crash', 96.90, 'HOT',    1.75,  'Medium', NOW(), DATE_ADD(NOW(), INTERVAL 40 MINUTE)),
('Baccarat',                  'Pragmatic Play Live', 'table', 98.76, 'NORMAL', 0.05,  'Low',    NOW(), DATE_ADD(NOW(), INTERVAL 50 MINUTE)),
('Blackjack',                 'Pragmatic Play Live', 'table', 99.28, 'NORMAL', 0.02,  'Low',    NOW(), DATE_ADD(NOW(), INTERVAL 55 MINUTE)),
('Roulette European',         'Pragmatic Play Live', 'table', 97.30, 'NORMAL', -0.10, 'Low',    NOW(), DATE_ADD(NOW(), INTERVAL 60 MINUTE)),
('Crazy Time',                'Evolution Gaming',    'live',  96.08, 'HOT',    1.30,  'High',   NOW(), DATE_ADD(NOW(), INTERVAL 35 MINUTE));


-- ---------------------------------------------------------------------
-- USER_FAVORITES
-- (id user & id game mengikuti urutan AUTO_INCREMENT di atas:
--  demo=1, royalvip=2 | Sweet Bonanza=1, Gates of Olympus=2, dst.)
-- ---------------------------------------------------------------------
INSERT INTO user_favorites (user_id, game_id) VALUES
(1, 1),   -- demo suka Sweet Bonanza
(1, 9),   -- demo suka Sugar Rush
(1, 14),  -- demo suka Aviator
(2, 2),   -- royalvip suka Gates of Olympus
(2, 11),  -- royalvip suka Mahjong Ways 2
(2, 19);  -- royalvip suka Baccarat


-- ---------------------------------------------------------------------
-- USER_ACTIVITY
-- ---------------------------------------------------------------------
INSERT INTO user_activity (user_id, icon, activity, created_at) VALUES
(1, '🎉', 'Akun berhasil dibuat',            NOW() - INTERVAL 30 DAY),
(1, '🔑', 'Password berhasil diubah',        NOW() - INTERVAL 6 DAY),
(1, '👤', 'Profil berhasil diperbarui',      NOW() - INTERVAL 2 DAY),
(2, '📷', 'Mengubah foto profil',            NOW() - INTERVAL 4 DAY),
(2, '🔑', 'Password berhasil diubah',        NOW() - INTERVAL 8 DAY);
