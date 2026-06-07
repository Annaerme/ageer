create table if not exists contact_berichten (
  id          uuid primary key default gen_random_uuid(),
  voornaam    text not null,
  naam        text not null,
  email       text not null,
  onderwerp   text,
  bericht     text not null,
  ingediend_op timestamptz default now(),
  gelezen     boolean default false
);

alter table contact_berichten enable row level security;

-- Allow anonymous inserts (contact form submissions)
create policy "Iedereen kan een bericht insturen"
  on contact_berichten for insert
  to anon
  with check (true);
