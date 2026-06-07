-- Bestuur
CREATE TABLE IF NOT EXISTS bestuur (
  id            UUID  PRIMARY KEY DEFAULT gen_random_uuid(),
  naam          TEXT  NOT NULL,
  functie       TEXT,
  extra_functies TEXT,
  email         TEXT,
  telefoon      TEXT,
  volgorde      INT   NOT NULL DEFAULT 0,
  actief        BOOLEAN NOT NULL DEFAULT true,
  aangemaakt_op TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE bestuur ENABLE ROW LEVEL SECURITY;
CREATE POLICY "anon_read" ON bestuur FOR SELECT USING (true);
CREATE POLICY "auth_all"  ON bestuur FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO bestuur (naam, functie, extra_functies, email, telefoon, volgorde) VALUES
('Vincent Vermeylen', 'Voorzitter',                      NULL,                                         NULL,                          NULL,           10),
('Julie Van Laere',   'Ondervoorzitter',                 'Jeugd · Communicatie · Training · Vertrouwenspersoon', NULL,             NULL,           20),
('Marten Vols',       'Secretaris',                     'Public relations · Sponsoring',              NULL,                          NULL,           30),
('Gaétan De Staercke','Penningmeester',                  NULL,                                         'gaetan@aegir-gent.be',        NULL,           40),
('Piet Vanneste',     'Website · Admin · Onthaal',       NULL,                                         'info@aegir-gent.be',          '0465 09 42 68',50);

-- Partners
CREATE TABLE IF NOT EXISTS partners (
  id            UUID  PRIMARY KEY DEFAULT gen_random_uuid(),
  naam          TEXT  NOT NULL,
  website       TEXT,
  beschrijving  TEXT,
  volgorde      INT   NOT NULL DEFAULT 0,
  actief        BOOLEAN NOT NULL DEFAULT true,
  aangemaakt_op TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE partners ENABLE ROW LEVEL SECURITY;
CREATE POLICY "anon_read" ON partners FOR SELECT USING (true);
CREATE POLICY "auth_all"  ON partners FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO partners (naam, website, beschrijving, volgorde) VALUES
('Sportdienst Stad Gent',         'http://www.gent.be/sport',            'Subsidies en infrastructuur',   10),
('Sporta Team',                   'http://www.sportateam.be',            'Verzekering & sporttechnologie',20),
('Vlaamse Reddingsfederatie',     'http://www.redfed.be',                'Koepelorganisatie reddingszwemmen', 30),
('Cafétaria Stop-Gino',           NULL,                                  'Aan het zwembad',               40),
('Farys AquaFit',                 'http://www.farys.be',                 NULL,                            50),
('Zwemshop Geel',                 'http://zwemshopgeel.be',              'Korting voor leden',            60),
('Philouroux Fotografie',         'http://sauvetage.skynetblogs.be',     'Clubfotografie',                70);

-- Ereleden
CREATE TABLE IF NOT EXISTS ereleden (
  id            UUID  PRIMARY KEY DEFAULT gen_random_uuid(),
  naam          TEXT  NOT NULL,
  jaar          INT,
  notitie       TEXT,
  volgorde      INT   NOT NULL DEFAULT 0,
  aangemaakt_op TIMESTAMPTZ NOT NULL DEFAULT now()
);
ALTER TABLE ereleden ENABLE ROW LEVEL SECURITY;
CREATE POLICY "anon_read" ON ereleden FOR SELECT USING (true);
CREATE POLICY "auth_all"  ON ereleden FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO ereleden (naam, jaar, notitie, volgorde) VALUES
('Eddy Lefebvre',    2012, NULL,       10),
('Antoine Lievens',  2012, '† 2015',   20),
('Ignace Mottart',   2015, '† 2016',   30),
('Yves Druyve',      2016, NULL,       40),
('Maurice Struyve',  2016, '† 2017',   50),
('Pascal Lievens',   2023, NULL,       60),
('Piet Vanneste',    2025, NULL,       70);
