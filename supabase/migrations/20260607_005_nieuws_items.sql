CREATE TABLE IF NOT EXISTS nieuws_items (
  id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  titel       TEXT        NOT NULL,
  datum       DATE        NOT NULL,
  categorie   TEXT        NOT NULL DEFAULT 'club',
  samenvatting TEXT,
  inhoud      TEXT,
  gepubliceerd BOOLEAN    NOT NULL DEFAULT true,
  aangemaakt_op TIMESTAMPTZ NOT NULL DEFAULT now()
);

ALTER TABLE nieuws_items ENABLE ROW LEVEL SECURITY;

CREATE POLICY "anon_read_published" ON nieuws_items
  FOR SELECT TO anon USING (gepubliceerd = true);

CREATE POLICY "auth_all" ON nieuws_items
  FOR ALL TO authenticated USING (true) WITH CHECK (true);

-- Seed: real news from old site
INSERT INTO nieuws_items (titel, datum, categorie, samenvatting, inhoud) VALUES
(
  'Bijscholingen redder 2026',
  '2026-03-25',
  'opleiding',
  'De zomerbijscholingen voor 2026 zijn gekend en inschrijvingen staan open. Aegir-leden betalen €45 i.p.v. €65.',
  'De bijscholingen georganiseerd door onze club voor 2026 zijn gekend en de inschrijvingen staan open. Schrijf je zo snel mogelijk in zodat je zeker bent van jouw plaatsje — de aantallen zijn beperkt.

Data bijscholingen Aegir Gent 2026:
• Vrijdag 26/06/2026
• Vrijdag 10/07/2026
• Vrijdag 28/08/2026

Inschrijven via de website van RedFed (redfed.be). Als Aegir-lid met redderslidmaatschap betaal je €45 i.p.v. €65.

Let op: inschrijving is pas officieel als je BEIDE stappen voltooit:
1. Inschrijven op de pagina van de bijscholing op redfed.be
2. De betaling uitvoeren via de link op diezelfde pagina

Vragen? Contacteer Gaétan via gaetan@aegir-gent.be.'
),
(
  'Baanindeling trainingsgroepen',
  '2022-10-19',
  'trainingen',
  'Nieuwe baanindeling van kracht. Twijfel je in welke groep je zit? Kijk na via je aanmeldlink of QR-code.',
  'Kwaliteitsvol zwemmen staat bovenaan in onze club. Onze trainers hebben een plan gemaakt voor de herindeling van het bad en de banen.

Huidige baanindeling:
• Baan 1 (kant raam): Pinguin
• Baan 2: Otter
• Baan 3: Orka
• Baan 4: Dolfijn
• Baan 5: Haai
• Baan 6 (kant tribune): Nemo

Twijfel je in welke groep je zit? Je vindt je trainingsgroep wanneer je je aanmeldt via je persoonlijke QR-code op je lidkaart of de persoonlijke link in je mail.

Foutje gemerkt? Geef gerust een seintje aan de kassa.'
),
(
  'Seizoen 2025–2026 van start',
  '2025-10-03',
  'trainingen',
  'Het nieuwe trainingsseizoen is van start gegaan. We verwelkomden verschillende nieuwe leden in Zwembad Strop.',
  'Het nieuwe trainingsseizoen is van start gegaan. We verwelkomden verschillende nieuwe leden in Zwembad Strop — fijn om zo veel enthousiasme te zien!

Trainingen gaan door elke woensdag van 19:30 tot 21:00 in Zwembad Strop. Aanmelden doe je via je persoonlijke QR-code of de link in je lidmaatschapsmail.

Welkom aan alle nieuwe leden, en welkom terug aan de vaste gezichten!'
);
