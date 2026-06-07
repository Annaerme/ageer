CREATE TABLE IF NOT EXISTS agenda_items (
  id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  titel       TEXT        NOT NULL,
  datum       DATE        NOT NULL,
  datum_einde DATE,
  tijdstip    TEXT,
  type        TEXT        NOT NULL DEFAULT 'training',
  locatie     TEXT,
  beschrijving TEXT,
  doelgroep   TEXT,
  inschrijven_url TEXT,
  gepubliceerd BOOLEAN    NOT NULL DEFAULT true,
  aangemaakt_op TIMESTAMPTZ NOT NULL DEFAULT now()
);

ALTER TABLE agenda_items ENABLE ROW LEVEL SECURITY;

CREATE POLICY "anon_read_published" ON agenda_items
  FOR SELECT TO anon USING (gepubliceerd = true);

CREATE POLICY "auth_all" ON agenda_items
  FOR ALL TO authenticated USING (true) WITH CHECK (true);

-- Seed: trainingen
INSERT INTO agenda_items (titel, datum, tijdstip, type, locatie, beschrijving, doelgroep) VALUES
('Training', '2026-06-10', '19:30–21:00', 'training', 'Zwembad Strop, Gent', 'Wekelijkse training. Aanmelden via persoonlijke QR-code of link.', 'Nemo, Otter, Haai, Dolfijn, Orka, Pinguin, Octopus'),
('Training', '2026-06-17', '19:30–21:00', 'training', 'Zwembad Strop, Gent', 'Wekelijkse training. Aanmelden via persoonlijke QR-code of link.', 'Nemo, Otter, Haai, Dolfijn, Orka, Pinguin, Octopus'),
('Training', '2026-06-24', '19:30–21:00', 'training', 'Zwembad Strop, Gent', 'Wekelijkse training. Aanmelden via persoonlijke QR-code of link.', 'Nemo, Otter, Haai, Dolfijn, Orka, Pinguin, Octopus'),
('Bijscholing redder', '2026-06-26', NULL, 'opleiding', 'Zwembad Strop, Gent', 'Bijscholing georganiseerd door Aegir Gent. Inschrijven verplicht via redfed.be. Aegir-leden met redderslidmaatschap betalen €45 i.p.v. €65. Inschrijven: stap 1 via redfed.be, stap 2 betaling uitvoeren — beide verplicht.', NULL),
('Bijscholing redder', '2026-07-10', NULL, 'opleiding', 'Zwembad Strop, Gent', 'Bijscholing georganiseerd door Aegir Gent. Inschrijven verplicht via redfed.be. Aegir-leden met redderslidmaatschap betalen €45 i.p.v. €65.', NULL),
('Bijscholing redder', '2026-08-28', NULL, 'opleiding', 'Zwembad Strop, Gent', 'Bijscholing georganiseerd door Aegir Gent. Inschrijven verplicht via redfed.be. Aegir-leden met redderslidmaatschap betalen €45 i.p.v. €65.', NULL);
