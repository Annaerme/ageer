CREATE TABLE IF NOT EXISTS clubrecords (
  id         UUID  PRIMARY KEY DEFAULT gen_random_uuid(),
  code       TEXT  NOT NULL,
  discipline TEXT  NOT NULL,
  type       TEXT  NOT NULL DEFAULT 'ind',
  bad        INT   NOT NULL DEFAULT 25,
  geslacht   TEXT  NOT NULL DEFAULT 'M',
  categorie  TEXT  NOT NULL,
  tijd       TEXT  NOT NULL,
  atleet     TEXT,
  datum      TEXT,
  wedstrijd  TEXT,
  aangemaakt_op TIMESTAMPTZ NOT NULL DEFAULT now()
);

ALTER TABLE clubrecords ENABLE ROW LEVEL SECURITY;
CREATE POLICY "anon_read" ON clubrecords FOR SELECT USING (true);
CREATE POLICY "auth_all"  ON clubrecords FOR ALL TO authenticated USING (true) WITH CHECK (true);

-- Records worden geïmporteerd via de beheerdersfunctie "Importeer uit JSON"
-- op de adminpagina (/pages/admin/). De brondata staat in /data/clubrecords.json.
