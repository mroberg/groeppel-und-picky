-- Tabelle für die Erinnerung an Band 2 (Double-Opt-in)
CREATE TABLE IF NOT EXISTS erinnerung (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email        VARCHAR(254) NOT NULL UNIQUE,
  token        CHAR(64)     NOT NULL UNIQUE,
  erstellt     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  bestaetigt   DATETIME     NULL,
  ip_hash      CHAR(64)     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bewertungen aus dem Formular. Erscheinen erst, wenn 'freigegeben' gesetzt ist (Konsole).
CREATE TABLE IF NOT EXISTS bewertung (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buch         VARCHAR(20)  NOT NULL DEFAULT 'band1',
  format       VARCHAR(20)  NULL,
  name         VARCHAR(40)  NOT NULL,
  sterne       TINYINT UNSIGNED NOT NULL,
  titel        VARCHAR(80)  NULL,
  text         TEXT         NOT NULL,
  email        VARCHAR(254) NULL,
  erstellt     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  freigegeben  DATETIME     NULL,
  ip_hash      CHAR(64)     NULL,
  INDEX (freigegeben)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
