
DROP TABLE IF EXISTS `detail_transaction`;
DROP TABLE IF EXISTS `transaction`;
DROP TABLE IF EXISTS `note_images`;
DROP TABLE IF EXISTS `notes`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `role`;

CREATE TABLE `role` (
    `id_role` BIGINT NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_role`)
);

CREATE TABLE `user` (
    `id_user` BIGINT NOT NULL AUTO_INCREMENT,
    `nama` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `id_role` BIGINT NOT NULL,
    PRIMARY KEY (`id_user`)
);

CREATE TABLE `notes` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `student_id` BIGINT NOT NULL,
    `author_id` BIGINT NULL,
    `author_name_snapshot` VARCHAR(255) NOT NULL,
    `author_role_snapshot` VARCHAR(255) NOT NULL,
    `body` TEXT NULL,
    `note_date` DATE NOT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE `note_images` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `note_id` BIGINT NOT NULL,
    `disk` VARCHAR(255) NOT NULL,
    `path` VARCHAR(255) NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(255) NOT NULL,
    `size_bytes` INT NOT NULL,
    `sort_order` INT NOT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE `transaction` (
    `id_transaksi` BIGINT NOT NULL AUTO_INCREMENT,
    `id_murid` BIGINT NOT NULL,
    `tanggal` DATE NOT NULL,
    `jumlah` INT NOT NULL,
    `created_at` DATETIME NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id_transaksi`)
);

CREATE TABLE `detail_transaction` (
    `id_detail` BIGINT NOT NULL AUTO_INCREMENT,
    `id_transaksi` BIGINT NOT NULL,
    `bulan` TINYINT NOT NULL,
    `tahun` SMALLINT NOT NULL,
    PRIMARY KEY (`id_detail`)
);

INSERT INTO `role` (`id_role`, `nama`) VALUES
    (1, 'super_admin'),
    (2, 'guru'),
    (3, 'murid');

INSERT INTO `user` (`id_user`, `nama`, `password`, `id_role`) VALUES
    (1, 'superadmin', '123', 1);
