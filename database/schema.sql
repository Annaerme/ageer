-- Aegir Gent — tabellen voor de nieuwe website (MariaDB 10.6+ / MySQL 8+)
--
-- Alle tabellen hebben het voorvoegsel `web_`, zodat ze naast de tabellen van
-- het oude ledensysteem (news, events, members, …) in dezelfde database kunnen
-- staan zonder er iets aan te veranderen. Dit script raakt geen bestaande
-- tabellen aan en kan veilig opnieuw uitgevoerd worden (IF NOT EXISTS).

SET NAMES utf8mb4;

-- ── Inhoud ──────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS web_agenda_items (
  id              CHAR(36)     NOT NULL PRIMARY KEY,
  titel           VARCHAR(200) NOT NULL,
  datum           DATE         NOT NULL,
  datum_einde     DATE         NULL,
  tijdstip        VARCHAR(100) NULL,
  type            VARCHAR(30)  NOT NULL DEFAULT 'training',
  locatie         VARCHAR(200) NULL,
  beschrijving    TEXT         NULL,
  doelgroep       VARCHAR(200) NULL,
  inschrijven_url VARCHAR(500) NULL,
  gepubliceerd    TINYINT(1)   NOT NULL DEFAULT 1,
  aangemaakt_op   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_datum (datum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_nieuws_items (
  id            CHAR(36)     NOT NULL PRIMARY KEY,
  titel         VARCHAR(200) NOT NULL,
  datum         DATE         NOT NULL,
  categorie     VARCHAR(30)  NOT NULL DEFAULT 'club',
  samenvatting  TEXT         NULL,
  inhoud        MEDIUMTEXT   NULL,
  foto_url      VARCHAR(500) NULL,
  gepubliceerd  TINYINT(1)   NOT NULL DEFAULT 1,
  aangemaakt_op DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_datum (datum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_shop_producten (
  id            CHAR(36)      NOT NULL PRIMARY KEY,
  slug          VARCHAR(120)  NOT NULL,
  naam          VARCHAR(200)  NOT NULL,
  beschrijving  TEXT          NULL,
  prijs         DECIMAL(10,2) NOT NULL DEFAULT 0,
  foto_url      VARCHAR(500)  NULL,
  maten         TEXT          NULL COMMENT 'JSON-lijst, bv. ["S","M","L"]',
  beschikbaar   TINYINT(1)    NOT NULL DEFAULT 1,
  volgorde      INT           NOT NULL DEFAULT 0,
  aangemaakt_op DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_bestuur (
  id             CHAR(36)     NOT NULL PRIMARY KEY,
  naam           VARCHAR(200) NOT NULL,
  functie        VARCHAR(200) NULL,
  extra_functies VARCHAR(300) NULL,
  email          VARCHAR(200) NULL,
  telefoon       VARCHAR(50)  NULL,
  volgorde       INT          NOT NULL DEFAULT 0,
  actief         TINYINT(1)   NOT NULL DEFAULT 1,
  aangemaakt_op  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_partners (
  id            CHAR(36)     NOT NULL PRIMARY KEY,
  naam          VARCHAR(200) NOT NULL,
  website       VARCHAR(500) NULL,
  beschrijving  TEXT         NULL,
  volgorde      INT          NOT NULL DEFAULT 0,
  actief        TINYINT(1)   NOT NULL DEFAULT 1,
  aangemaakt_op DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_ereleden (
  id            CHAR(36)     NOT NULL PRIMARY KEY,
  naam          VARCHAR(200) NOT NULL,
  jaar          INT          NULL,
  notitie       TEXT         NULL,
  volgorde      INT          NOT NULL DEFAULT 0,
  aangemaakt_op DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_clubrecords (
  id            CHAR(36)     NOT NULL PRIMARY KEY,
  code          VARCHAR(20)  NOT NULL,
  discipline    VARCHAR(200) NOT NULL,
  type          VARCHAR(10)  NOT NULL DEFAULT 'ind',
  bad           INT          NOT NULL DEFAULT 25,
  geslacht      CHAR(1)      NOT NULL DEFAULT 'M',
  categorie     VARCHAR(50)  NOT NULL,
  tijd          VARCHAR(20)  NOT NULL,
  atleet        VARCHAR(300) NULL,
  datum         VARCHAR(30)  NULL,
  wedstrijd     VARCHAR(300) NULL,
  aangemaakt_op DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_disciplines (
  id           CHAR(36)     NOT NULL PRIMARY KEY,
  code         VARCHAR(20)  NOT NULL,
  naam         VARCHAR(200) NOT NULL,
  reeks        CHAR(1)      NOT NULL DEFAULT 'A',
  beschrijving TEXT         NULL,
  volgorde     INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_niveaugroepen (
  id           CHAR(36)     NOT NULL PRIMARY KEY,
  naam         VARCHAR(100) NOT NULL,
  emoji        VARCHAR(20)  NULL,
  baan         VARCHAR(50)  NULL,
  beschrijving TEXT         NULL,
  trainer      VARCHAR(200) NULL,
  volgorde     INT          NOT NULL DEFAULT 0,
  actief       TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_locaties (
  id       CHAR(36)     NOT NULL PRIMARY KEY,
  naam     VARCHAR(200) NOT NULL,
  adres    VARCHAR(300) NULL,
  dag      VARCHAR(100) NULL,
  tijdstip VARCHAR(100) NULL,
  seizoen  VARCHAR(100) NULL,
  gebruik  VARCHAR(200) NULL,
  info     TEXT         NULL,
  volgorde INT          NOT NULL DEFAULT 0,
  actief   TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_homepage_stats (
  id       CHAR(36)     NOT NULL PRIMARY KEY,
  waarde   VARCHAR(50)  NOT NULL,
  label    VARCHAR(100) NOT NULL,
  volgorde INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_site_settings (
  sleutel VARCHAR(100) NOT NULL PRIMARY KEY,
  waarde  TEXT         NULL,
  label   VARCHAR(200) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Inbox (ingestuurd via de website, enkel leesbaar voor beheerders) ────

CREATE TABLE IF NOT EXISTS web_contact_berichten (
  id           CHAR(36)     NOT NULL PRIMARY KEY,
  voornaam     VARCHAR(100) NOT NULL,
  naam         VARCHAR(100) NOT NULL,
  email        VARCHAR(200) NOT NULL,
  onderwerp    VARCHAR(200) NULL,
  bericht      TEXT         NOT NULL,
  ingediend_op DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  gelezen      TINYINT(1)   NOT NULL DEFAULT 0,
  KEY idx_ingediend (ingediend_op)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_inschrijvingen (
  id            CHAR(36)     NOT NULL PRIMARY KEY,
  voornaam      VARCHAR(100) NOT NULL,
  naam          VARCHAR(100) NOT NULL,
  geboortedatum DATE         NULL,
  email         VARCHAR(200) NOT NULL,
  telefoon      VARCHAR(50)  NULL,
  adres         VARCHAR(300) NULL,
  type_lid      VARCHAR(50)  NULL,
  niveau        VARCHAR(100) NULL,
  opmerking     TEXT         NULL,
  ingediend_op  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status        VARCHAR(20)  NOT NULL DEFAULT 'nieuw',
  KEY idx_ingediend (ingediend_op)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_shop_bestellingen (
  id           CHAR(36)      NOT NULL PRIMARY KEY,
  voornaam     VARCHAR(100)  NOT NULL,
  naam         VARCHAR(100)  NOT NULL,
  email        VARCHAR(200)  NOT NULL,
  telefoon     VARCHAR(50)   NULL,
  opmerking    TEXT          NULL,
  regels       TEXT          NOT NULL COMMENT 'JSON-lijst van bestelregels',
  totaal       DECIMAL(10,2) NOT NULL,
  status       VARCHAR(20)   NOT NULL DEFAULT 'nieuw',
  ingediend_op DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ingediend (ingediend_op)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Beheer ──────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS web_beheerders (
  id               INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email            VARCHAR(200) NOT NULL UNIQUE,
  naam             VARCHAR(200) NULL,
  wachtwoord_hash  VARCHAR(255) NOT NULL,
  actief           TINYINT(1)   NOT NULL DEFAULT 1,
  aangemaakt_op    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  laatst_ingelogd  DATETIME     NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teller tegen misbruik: mislukte logins en formulierinzendingen per IP.
CREATE TABLE IF NOT EXISTS web_ratelimit (
  sleutel  VARCHAR(190) NOT NULL,
  tijdstip DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sleutel_tijd (sleutel, tijdstip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
