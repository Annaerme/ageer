create table if not exists inschrijvingen (
  id            uuid primary key default gen_random_uuid(),
  voornaam      text not null,
  naam          text not null,
  geboortedatum date,
  email         text not null,
  telefoon      text,
  adres         text,
  type_lid      text,   -- 'recreatief' | 'competitie' | 'jeugd'
  niveau        text,   -- groepsnaam
  opmerking     text,
  ingediend_op  timestamptz default now(),
  status        text default 'nieuw'  -- 'nieuw' | 'behandeld' | 'afgewezen'
);

alter table inschrijvingen enable row level security;

create policy "Iedereen kan een inschrijving indienen"
  on inschrijvingen for insert
  to anon
  with check (true);
