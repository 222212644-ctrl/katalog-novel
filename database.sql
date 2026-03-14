-- ============================================================
-- DATABASE SETUP untuk Website M. Tansiswo Siagian
-- Jalankan di phpMyAdmin: Import file ini
-- Ganti `nama_database_anda` dengan nama database Anda
-- ============================================================

-- Tabel: novels (Katalog Buku / Novel)
CREATE TABLE IF NOT EXISTS `novels` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(255)  NOT NULL,
  `synopsis`    TEXT          NOT NULL,
  `cover_image` VARCHAR(500)  DEFAULT NULL,
  `buy_link`    VARCHAR(500)  DEFAULT NULL,
  `year`        YEAR          NOT NULL,
  `color`       VARCHAR(7)    DEFAULT '#6B4423',
  `genre`       VARCHAR(100)  DEFAULT 'novel',
  `status`      ENUM('draft','published') DEFAULT 'draft',
  `created_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel: posts (Blog, Cerpen, Artikel)
CREATE TABLE IF NOT EXISTS `posts` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `type`        ENUM('blog','cerpen','artikel') NOT NULL,
  `title`       VARCHAR(255)  NOT NULL,
  `content`     LONGTEXT      NOT NULL,
  `cover_image` VARCHAR(500)  DEFAULT NULL,
  `slug`        VARCHAR(255)  NOT NULL UNIQUE,
  `status`      ENUM('draft','published') DEFAULT 'draft',
  `published_at` DATETIME     DEFAULT NULL,
  `created_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index untuk performa query
CREATE INDEX idx_posts_type_status ON `posts` (`type`, `status`);
CREATE INDEX idx_posts_published_at ON `posts` (`published_at`);
CREATE INDEX idx_novels_status ON `novels` (`status`);

-- ============================================================
-- DATA AWAL: Masukkan novel yang sudah ada
-- ============================================================
INSERT INTO `novels` (`title`, `synopsis`, `cover_image`, `buy_link`, `year`, `color`, `genre`, `status`) VALUES
(
  'Sortauli Putri Sang Pendeta',
  'Sortauli gadis cantik, energik, pintar dan berpendidikan tinggi, punya kepribadian yang tangguh. Putri seorang pendeta, kakaknya juga istri pendeta, adiknya kelak pasti akan menjadi pendeta pula. Entah mengapa, ia tak tertarik jika didekati seorang pendeta. Sudah banyak pendeta mendekatinya, atau bahkan dijodoh kepadanya, mengapa ia tidak tertarik sedikitpun? Semua orang menilainya bodoh, sombong. Itu wajar sebab saat ini, banyak anak gadis yang berharap menjadi istri pendeta. Banyak orangtua yang punya anak gadis, berharap ada pendeta yang mendekati anak gadisnya. Bagi mereka menjadi istri pendeta adalah anugerah besar. Orangtuanya yang pendeta, menyimpulkan percuma ia berpendidikan tinggi. Hanya karena ia tidak mau menjadi istri pendeta. Mereka murka. Padahal sikapnya adalah sebuah hak asasi yang tidak bisa diganggu gugat siapapun. Tetapi ia bergeming, ia tetap ingat pengalamannya sejak kecil. la sudah rasakan sejak ia punya pikiran. la telah perhatikan dengan mata kepalanya sendiri. la fahami secara saksama dan mendalam. la punya penilaian tersendiri kepada mereka.',
  'Sortauli.jpeg',
  'https://tk.tokopedia.com/ZSuB73QN6/',
  2024,
  '#6B4423',
  'novel',
  'published'
),
(
  'Amangbao Parsinuan',
  'Di awal pergaulan Rosinta pada pariban kandungnya Parulian, sudah diwanti-wanti bapaknya agar mereka jangan menikah. Menurut bapaknya, kawin dengan pariban begitu banyak resikonya. Baik secara kedokteran karena hubungan darah mereka cukup dekat. Resiko yg paling berat, jika oleh sesuatu hal misalnya akhirnya cerai. Bakal terjadi huru-hara antara yg bersaudara kandung. Dan kekwatiran itu pun tiba. Rosinta tidak juga hamil walau sudah lebih 2 tahun menikah dengan Parulian. Sementara Parulian adalah anak satu-satunya laki-laki dari orangtuanya. Terbesitlah sebuah rencana orangtua Parulian, agar anaknya kawin lagi sebab menganggap Rosinta tidak akan bisa punya anak.',
  'Amangbao.jpeg',
  'https://tk.tokopedia.com/ZSuBctEWc/',
  2019,
  '#2D5A4A',
  'novel',
  'published'
);
