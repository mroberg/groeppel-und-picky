-- Tabelle für die Erinnerung an Band 2 (Double-Opt-in)
CREATE TABLE IF NOT EXISTS erinnerung (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(254) NOT NULL UNIQUE,
  token        CHAR(64)     NOT NULL UNIQUE,
  erstellt     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  bestaetigt   DATETIME     NULL,
  ip_hash      CHAR(64)     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
