-- Het nieuwsformulier in /pages/admin/ schrijft foto_url weg; kolom ontbrak.
ALTER TABLE nieuws_items ADD COLUMN IF NOT EXISTS foto_url text;
