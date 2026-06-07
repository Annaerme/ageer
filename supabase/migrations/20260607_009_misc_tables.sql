-- ════════════════════════════════════════════════════════════════
-- Migration 009: site_settings, homepage_stats, disciplines,
--                niveaugroepen, locaties
--                + RLS lees/schrijf voor inbox-tabellen
--                + Supabase Storage bucket 'fotos'
-- ════════════════════════════════════════════════════════════════

-- ── site_settings ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS site_settings (
  sleutel  TEXT PRIMARY KEY,
  waarde   TEXT,
  label    TEXT
);

ALTER TABLE site_settings ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Settings leesbaar voor iedereen"
  ON site_settings FOR SELECT USING (true);
CREATE POLICY "Beheerders beheren settings"
  ON site_settings FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO site_settings (sleutel, waarde, label) VALUES
  ('iban_club',           'BE17 8907 3409 5021',          'IBAN – Reddingsclub Aegir Gent vzw'),
  ('bic_club',            'VDSPBE91',                     'BIC – Reddingsclub Aegir Gent vzw'),
  ('iban_rescue',         'BE58 8917 0402 3279',          'IBAN – Rescue Aegir Gent vzw'),
  ('bic_rescue',          'VDSPBE91',                     'BIC – Rescue Aegir Gent vzw'),
  ('email_info',          'info@aegir-gent.be',           'E-mail – Algemeen'),
  ('email_competitie',    'competitie@aegir-gent.be',     'E-mail – Competitie'),
  ('email_opleiding',     'gaetan@aegir-gent.be',         'E-mail – Opleiding & bijscholingen'),
  ('tel_piet',            '0465 09 42 68',                'Telefoon – Piet (algemeen & onthaal)'),
  ('adres_post',          'Poortbilk 5, 9032 Wondelgem',  'Postadres'),
  ('lidgeld_vervaldag',   '31 december',                  'Lidgeld – vervaldatum'),
  ('lidgeld_herinnering', 'november',                     'Lidgeld – maand van herinnering'),
  ('korting_familie',     '€15',                          'Korting – 2e familielid'),
  ('korting_vt',          '€11',                          'Korting – verhoogde tegemoetkoming'),
  ('polis_sporta',        '45.236.716',                   'Polis – Sporta'),
  ('polis_redfed_lo',     '1.102.192',                    'Polis – RedFed LO'),
  ('polis_redfed_ba',     '1.102.193',                    'Polis – RedFed BA'),
  ('copyright_tekst',     '© 2026 Reddingsclub Aegir Gent vzw & Rescue Aegir Gent vzw', 'Footer – copyright tekst'),
  ('site_beschrijving',   'Reddingszwemmen, trainingen en competitie in Gent.',          'Footer – sitebeschrijving')
ON CONFLICT (sleutel) DO NOTHING;

-- ── homepage_stats ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS homepage_stats (
  id        UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  waarde    TEXT NOT NULL,
  label     TEXT NOT NULL,
  volgorde  INTEGER DEFAULT 0
);

ALTER TABLE homepage_stats ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Stats leesbaar voor iedereen"
  ON homepage_stats FOR SELECT USING (true);
CREATE POLICY "Beheerders beheren stats"
  ON homepage_stats FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO homepage_stats (waarde, label, volgorde) VALUES
  ('40+',  'Jaar actief',   1),
  ('100+', 'Actieve leden', 2),
  ('7',    'Niveaugroepen', 3),
  ('10+',  'Disciplines',   4);

-- ── disciplines ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS disciplines (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code         TEXT NOT NULL,
  naam         TEXT NOT NULL,
  reeks        TEXT NOT NULL DEFAULT 'A',
  beschrijving TEXT,
  volgorde     INTEGER DEFAULT 0
);

ALTER TABLE disciplines ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Disciplines leesbaar voor iedereen"
  ON disciplines FOR SELECT USING (true);
CREATE POLICY "Beheerders beheren disciplines"
  ON disciplines FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO disciplines (code, naam, reeks, beschrijving, volgorde) VALUES
  ('A1', 'Beach sprint',             'A', '90 m sprint op het strand. Snelheid en explosiviteit.',                       10),
  ('A2', 'Wade',                     'A', 'Waden door ondiep water. Kracht en techniek bepalen het verschil.',           20),
  ('A3', 'Beach flags',              'A', 'Liggend starten, 20 m sprint en grip op een stok. Reflexen.',                 30),
  ('A4', 'Rescue tube rescue',       'A', 'Zwemmen met rescuetube, slachtoffer redden en terugbrengen.',                 40),
  ('A5', 'Board rescue',             'A', 'Rescue board gebruiken om een slachtoffer te redden.',                        50),
  ('A6', 'Surf ski rescue',          'A', 'Kajakachtige surf ski inzetten voor een reddingsactie.',                      60),
  ('B1', '50 m obstacle race',       'B', 'Zwemmen over en onder obstakels. Wendbaarheid en snelheid.',                 10),
  ('B2', '100 m manikin carry',      'B', 'Duiken, dummy ophalen van de bodem en 100 m slepen.',                        20),
  ('B3', '100 m manikin carry with fins','B','Zelfde als B2 maar met flippers. Snelheid en kracht.',                    30),
  ('B4', '200 m super lifesaver',    'B', 'Combinatie van zwemmen, duiken, ophalen en slepen over 200 m.',               40);

-- ── niveaugroepen ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS niveaugroepen (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  naam         TEXT NOT NULL,
  emoji        TEXT,
  baan         TEXT,
  beschrijving TEXT,
  trainer      TEXT,
  volgorde     INTEGER DEFAULT 0,
  actief       BOOLEAN DEFAULT true
);

ALTER TABLE niveaugroepen ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Niveaugroepen leesbaar voor iedereen"
  ON niveaugroepen FOR SELECT USING (true);
CREATE POLICY "Beheerders beheren niveaugroepen"
  ON niveaugroepen FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO niveaugroepen (naam, emoji, baan, beschrijving, trainer, volgorde) VALUES
  ('Pinguïn', '🐧', 'Baan 1',               'Samenzijn en eigen tempo. Ideaal voor wie van het sociale aspect houdt.', null,             10),
  ('Otter',   '🦦', 'Baan 2',               'Techniek verfijnen staat centraal.',                                       'Pascal',         20),
  ('Dolfijn', '🐬', 'Baan 3 (met Orka)',    'Zelfdiscipline en samenwerking staan centraal.',                           null,             30),
  ('Orka',    '🐋', 'Baan 3 (met Dolfijn)', 'Rustig en op eigen tempo. Conditie op peil houden.',                      null,             40),
  ('Haai',    '🦈', 'Baan 4–5',             'Snel, durft uitdagingen aan.',                                             'Ralph',          50),
  ('Nemo',    '🐠', 'Baan 6',               'Eerste technieken van het reddend zwemmen.',                               'Julie & Ben',    60),
  ('Octopus', '🐙', 'Reddersopleiding',     'Cursisten die de reddersopleiding volgen.',                                'Pascal & Ralph', 70);

-- ── locaties ──────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS locaties (
  id        UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  naam      TEXT NOT NULL,
  adres     TEXT,
  dag       TEXT,
  tijdstip  TEXT,
  seizoen   TEXT,
  gebruik   TEXT,
  info      TEXT,
  volgorde  INTEGER DEFAULT 0,
  actief    BOOLEAN DEFAULT true
);

ALTER TABLE locaties ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Locaties leesbaar voor iedereen"
  ON locaties FOR SELECT USING (true);
CREATE POLICY "Beheerders beheren locaties"
  ON locaties FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO locaties (naam, adres, dag, tijdstip, seizoen, gebruik, info, volgorde) VALUES
  ('Zwembad Strop',
   'Stropstraat, 9000 Gent',
   'Woensdag', '19:30 – 21:00', 'September – juni',
   'Wekelijkse training',
   'Gratis parking naast het zwembad. Straatparkeren ook mogelijk.',
   10),
  ('Zwembad Rozebroeken',
   'Victor Braeckmanstraat, Sint-Amandsberg, Gent',
   null, null, null,
   'Incidenteel / inhaaltraining',
   'Kijk de agenda na voor eventuele trainingen op Rozebroeken.',
   20);

-- ── RLS voor inbox-tabellen (beheerder leest & bewerkt) ───────────
DO $$ BEGIN
  CREATE POLICY "Beheerders lezen berichten"
    ON contact_berichten FOR SELECT TO authenticated USING (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Beheerders updaten berichten"
    ON contact_berichten FOR UPDATE TO authenticated USING (true) WITH CHECK (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Beheerders verwijderen berichten"
    ON contact_berichten FOR DELETE TO authenticated USING (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Beheerders lezen inschrijvingen"
    ON inschrijvingen FOR SELECT TO authenticated USING (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Beheerders updaten inschrijvingen"
    ON inschrijvingen FOR UPDATE TO authenticated USING (true) WITH CHECK (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Beheerders verwijderen inschrijvingen"
    ON inschrijvingen FOR DELETE TO authenticated USING (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Beheerders lezen bestellingen"
    ON shop_bestellingen FOR SELECT TO authenticated USING (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Beheerders updaten bestellingen"
    ON shop_bestellingen FOR UPDATE TO authenticated USING (true) WITH CHECK (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Beheerders verwijderen bestellingen"
    ON shop_bestellingen FOR DELETE TO authenticated USING (true);
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

-- ── Storage bucket: fotos ─────────────────────────────────────────
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
  'fotos', 'fotos', true, 10485760,
  ARRAY['image/jpeg','image/png','image/webp','image/gif','image/svg+xml']
)
ON CONFLICT (id) DO UPDATE
  SET public = true, file_size_limit = 10485760;

DO $$ BEGIN
  CREATE POLICY "Fotos public read"
    ON storage.objects FOR SELECT USING (bucket_id = 'fotos');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Fotos auth upload"
    ON storage.objects FOR INSERT TO authenticated WITH CHECK (bucket_id = 'fotos');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Fotos auth update"
    ON storage.objects FOR UPDATE TO authenticated USING (bucket_id = 'fotos');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
  CREATE POLICY "Fotos auth delete"
    ON storage.objects FOR DELETE TO authenticated USING (bucket_id = 'fotos');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
