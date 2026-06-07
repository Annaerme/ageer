create table if not exists shop_bestellingen (
  id           uuid primary key default gen_random_uuid(),
  voornaam     text not null,
  naam         text not null,
  email        text not null,
  telefoon     text,
  opmerking    text,
  regels       jsonb not null,  -- array of {product_id, naam, maat, aantal, eenheidsprijs, totaal}
  totaal       numeric(8,2) not null,
  status       text default 'nieuw',  -- 'nieuw' | 'bevestigd' | 'afgehaald'
  ingediend_op timestamptz default now()
);

alter table shop_bestellingen enable row level security;

create policy "Iedereen kan een bestelling plaatsen"
  on shop_bestellingen for insert
  to anon
  with check (true);
