-- Activity log table to capture per-request user actions
CREATE TABLE IF NOT EXISTS `tbl_activity_log` (
    `id_log` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NULL,
    `username` VARCHAR(120) NULL,
    `nama` VARCHAR(190) NULL,
    `role_level` SMALLINT NULL,
    `action` VARCHAR(200) NULL,
    `detail` TEXT NULL,
    `page` VARCHAR(60) NULL,
    `act` VARCHAR(60) NULL,
    `sub` VARCHAR(100) NULL,
    `request_method` VARCHAR(10) NOT NULL DEFAULT 'GET',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_log`),
    INDEX `idx_activity_created_at` (`created_at`),
    INDEX `idx_activity_user` (`id_user`),
    INDEX `idx_activity_page` (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
