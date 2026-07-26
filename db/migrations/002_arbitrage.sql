-- Arbitrage scanner core: bookmakers, events, odds snapshots, detected surebets.
-- Deliberately source-agnostic: rows land here from an odds API, a scraper, or
-- the seeder. Nothing below assumes where prices came from.
--
--   mysql betting < db/migrations/002_arbitrage.sql

USE betting;

-- The operators we monitor. `parent` groups skins that share a price feed
-- (Lottomatica -> Goldbet/Planetwin365, Sisal -> Snai/Pokerstar): two skins of
-- the same parent are NOT an arbitrage, they are the same book twice.
CREATE TABLE IF NOT EXISTS bookmakers (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug       VARCHAR(64)  NOT NULL,
  name       VARCHAR(120) NOT NULL,
  parent     VARCHAR(64)  NULL DEFAULT NULL,
  bet_url    VARCHAR(255) NULL DEFAULT NULL,
  active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_bm_slug (slug),
  KEY idx_bm_parent (parent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A sporting fixture, deduplicated across books. Books spell teams differently
-- ("Inter" / "Internazionale"), so match_key is the normalised join handle.
CREATE TABLE IF NOT EXISTS events (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  match_key  VARCHAR(190) NOT NULL,
  sport      VARCHAR(32)  NOT NULL DEFAULT 'football',
  league     VARCHAR(120) NULL DEFAULT NULL,
  home       VARCHAR(120) NOT NULL,
  away       VARCHAR(120) NOT NULL,
  starts_at  DATETIME     NOT NULL,
  status     VARCHAR(16)  NOT NULL DEFAULT 'prematch', -- prematch|live|finished
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_ev_key (match_key),
  KEY idx_ev_start (starts_at),
  KEY idx_ev_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Latest price per (event, bookmaker, market, selection). One row per quote,
-- overwritten as prices move — the scanner only ever needs the current price.
--
-- market:    '1x2' | 'ou_1.5' | 'ou_2.5' | 'ou_3.5'
-- selection: 1x2 -> '1'|'X'|'2';  ou_* -> 'over'|'under'
CREATE TABLE IF NOT EXISTS odds (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id     INT UNSIGNED NOT NULL,
  bookmaker_id INT UNSIGNED NOT NULL,
  market       VARCHAR(16)  NOT NULL,
  selection    VARCHAR(8)   NOT NULL,
  price        DECIMAL(7,3) NOT NULL,
  updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_odds_quote (event_id, bookmaker_id, market, selection),
  KEY idx_odds_scan (event_id, market),
  CONSTRAINT fk_odds_event FOREIGN KEY (event_id)     REFERENCES events (id)     ON DELETE CASCADE,
  CONSTRAINT fk_odds_bm    FOREIGN KEY (bookmaker_id) REFERENCES bookmakers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A detected opportunity. `legs` is the JSON breakdown (selection, book, price,
-- stake share) so the UI and the future bet-slip prefill read one row.
-- margin = sum(1/price) across the covered outcomes; < 1 means arbitrage.
-- roi_pct = (1/margin - 1) * 100.
CREATE TABLE IF NOT EXISTS surebets (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_id   INT UNSIGNED NOT NULL,
  market     VARCHAR(16)   NOT NULL,
  kind       VARCHAR(16)   NOT NULL DEFAULT 'surebet', -- surebet|valuebet
  margin     DECIMAL(8,5)  NOT NULL,
  roi_pct    DECIMAL(7,3)  NOT NULL,
  legs       JSON          NOT NULL,
  signature  VARCHAR(190)  NOT NULL, -- dedupe handle: event+market+books+selections
  first_seen TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  active     TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_sb_sig (signature),
  KEY idx_sb_roi (active, roi_pct),
  KEY idx_sb_seen (last_seen),
  CONSTRAINT fk_sb_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Placed positions. One row per surebet taken; legs mirrors the surebet legs
-- plus what was actually staked and the settled result.
CREATE TABLE IF NOT EXISTS bets (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  surebet_id  INT UNSIGNED NULL DEFAULT NULL,
  event_id    INT UNSIGNED NULL DEFAULT NULL,
  user_id     INT UNSIGNED NULL DEFAULT NULL,
  market      VARCHAR(16)   NOT NULL,
  total_stake DECIMAL(12,2) NOT NULL DEFAULT 0,
  expected_roi DECIMAL(7,3) NOT NULL DEFAULT 0,
  legs        JSON          NOT NULL,
  status      VARCHAR(16)   NOT NULL DEFAULT 'open', -- open|won|lost|void
  profit      DECIMAL(12,2) NULL DEFAULT NULL,
  note        VARCHAR(255)  NULL DEFAULT NULL,
  placed_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  settled_at  DATETIME      NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_bets_status (status),
  KEY idx_bets_placed (placed_at),
  CONSTRAINT fk_bets_sb FOREIGN KEY (surebet_id) REFERENCES surebets (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the operators from the client's monitored-sites list, with skin parents.
INSERT INTO bookmakers (slug, name, parent, bet_url) VALUES
  ('bet365',       'Bet365',              NULL,           'https://www.bet365.it'),
  ('eurobet',      'Eurobet',             NULL,           'https://www.eurobet.it'),
  ('sisal',        'Sisal',               NULL,           'https://www.sisal.it'),
  ('snai',         'Snai',                'sisal',        'https://www.snai.it'),
  ('pokerstar',    'PokerStars Sports',   'sisal',        NULL),
  ('lottomatica',  'Lottomatica',         NULL,           'https://www.lottomatica.it'),
  ('goldbet',      'Goldbet',             'lottomatica',  'https://www.goldbet.it'),
  ('planetwin365', 'Planetwin365',        'lottomatica',  NULL),
  ('betflag',      'Betflag Sportsbook',  'lottomatica',  NULL),
  ('888',          '888sport',            NULL,           NULL),
  ('leovegas',     'LeoVegas',            '888',          NULL),
  ('unibet',       'Unibet',              '888',          NULL),
  ('admiralbet',   'Admiralbet',          NULL,           NULL),
  ('starvegas',    'StarVegas',           'admiralbet',   NULL),
  ('betfair_ex',   'Betfair Exchange',    NULL,           NULL),
  ('betfair_sb',   'Betfair Sportsbook',  NULL,           NULL),
  ('pinnacle',     'Pinnacle',            NULL,           NULL),
  ('williamhill',  'William Hill',        NULL,           NULL),
  ('marathonbet',  'Marathonbet',         NULL,           NULL),
  ('betsson',      'Betsson',             NULL,           NULL),
  ('bwin',         'Bwin',                NULL,           NULL),
  ('giocodigitale','GiocoDigitale',       'bwin',         NULL),
  ('netwin',       'Netwin',              NULL,           NULL),
  ('stanleybet',   'Stanleybet',          NULL,           NULL),
  ('domusbet',     'Domusbet',            NULL,           NULL),
  ('betpassion',   'Betpassion',          NULL,           NULL),
  ('daznbet',      'DAZN Bet',            NULL,           NULL),
  ('sportbet',     'Sportbet',            NULL,           NULL),
  ('stakeit',      'Stake.it',            NULL,           NULL),
  ('totosi',       'Totosi',              NULL,           NULL),
  ('starcasino',   'StarCasinò',     NULL,           NULL),
  ('staryes',      'Staryes',             NULL,           NULL),
  ('eplay24',      'Eplay24',             NULL,           NULL)
ON DUPLICATE KEY UPDATE name = VALUES(name), parent = VALUES(parent);
