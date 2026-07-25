-- betting.ispledger.com — initial schema (accounts + settings only, no domain logic yet)
-- Run once as a MySQL superuser:  mysql -u root -p < db/schema.sql

CREATE DATABASE IF NOT EXISTS betting
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'betting'@'localhost' IDENTIFIED BY 'betting';
GRANT ALL PRIVILEGES ON betting.* TO 'betting'@'localhost';
FLUSH PRIVILEGES;

USE betting;

-- Dashboard operators. Seeded with admin/admin on first boot (Bet\Auth::ensureSeed).
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(64)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(190) NULL DEFAULT NULL,
  email         VARCHAR(190) NULL DEFAULT NULL,
  role          VARCHAR(32)  NOT NULL DEFAULT 'admin',
  lang          VARCHAR(5)   NULL DEFAULT NULL,
  active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard-editable config, flat dot-path keys overlaid onto config/config.php.
CREATE TABLE IF NOT EXISTS settings (
  `key`      VARCHAR(120) NOT NULL,
  `value`    TEXT         NOT NULL,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
