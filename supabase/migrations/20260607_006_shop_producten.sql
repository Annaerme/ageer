CREATE TABLE IF NOT EXISTS shop_producten (
  id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
  slug        TEXT        UNIQUE NOT NULL,
  naam        TEXT        NOT NULL,
  beschrijving TEXT,
  prijs       DECIMAL(10,2) NOT NULL DEFAULT 0,
  foto_url    TEXT,
  maten       TEXT[],
  beschikbaar BOOLEAN     NOT NULL DEFAULT true,
  volgorde    INT         NOT NULL DEFAULT 0,
  aangemaakt_op TIMESTAMPTZ NOT NULL DEFAULT now()
);

ALTER TABLE shop_producten ENABLE ROW LEVEL SECURITY;

CREATE POLICY "anon_read" ON shop_producten FOR SELECT USING (true);
CREATE POLICY "auth_all"  ON shop_producten FOR ALL TO authenticated USING (true) WITH CHECK (true);

INSERT INTO shop_producten (slug, naam, beschrijving, prijs, foto_url, maten, volgorde) VALUES
('tshirt-basic',    'T-shirt (basic)',    'Klassiek clubt-shirt met Aegir Gent logo. Comfortabele kwaliteit voor training en vrije tijd.', 15.00, 'https://wlmehcbxeseigxwougwd.supabase.co/storage/v1/object/public/fotos/shop/tshirt.png',   ARRAY['XS','S','M','L','XL','XXL'], 10),
('tshirt-lifeguard','T-shirt LIFEGUARD',  'T-shirt met LIFEGUARD opdruk. Draag je trots je redderstitel.',                               15.00, 'https://wlmehcbxeseigxwougwd.supabase.co/storage/v1/object/public/fotos/shop/tshirt.png',   ARRAY['XS','S','M','L','XL','XXL'], 20),
('hoodie',          'Hoodie',             'Warme hoodie met Aegir Gent borduursels. Perfect na de training.',                            30.00, 'https://wlmehcbxeseigxwougwd.supabase.co/storage/v1/object/public/fotos/shop/hoodie.png',   ARRAY['XS','S','M','L','XL','XXL'], 30),
('short',           'Short',              'Lichte trainingsshort met clublogo. Geschikt voor in en rond het water.',                     15.00, 'https://wlmehcbxeseigxwougwd.supabase.co/storage/v1/object/public/fotos/shop/short.png',   ARRAY['XS','S','M','L','XL','XXL'], 40),
('badmuts',         'Badmuts',            'Siliconen badmuts in clubkleuren. One size fits all.',                                        6.00,  'https://wlmehcbxeseigxwougwd.supabase.co/storage/v1/object/public/fotos/shop/badmuts.png', NULL,                               50),
('usb',             'USB-stick 8GB',      '8GB USB-stick met Aegir Gent logo. Handig voor opleidingsmateriaal.',                        6.00,  'https://wlmehcbxeseigxwougwd.supabase.co/storage/v1/object/public/fotos/shop/usb.jpg',     NULL,                               60),
('fluitje',         'Fluitje FOX-40',     'Professioneel reddersfluitje FOX-40. Krachtig geluid, ook in het water.',                   10.00, 'https://wlmehcbxeseigxwougwd.supabase.co/storage/v1/object/public/fotos/shop/fox40.jpg',   NULL,                               70),
('pocket-mask',     'Pocket mask',        'Pocket mask met ventiel en filter voor veilige beademing bij eerste hulp.',                 12.50, 'https://wlmehcbxeseigxwougwd.supabase.co/storage/v1/object/public/fotos/shop/pocketmask.jpg', NULL,                            80);
