# Website installeren op Infomaniak

De website bestaat uit gewone HTML-pagina's plus een kleine PHP-API in `/api`
die met een MariaDB-database praat. Er is geen Supabase of Vercel meer nodig.

**Vereisten:** Infomaniak-webhosting met Apache/PHP 8.1 of nieuwer (8.3 aanbevolen)
en een MariaDB-database.

## 1. Pakket maken

```sh
./scripts/maak-upload-zip.sh
```

Je krijgt op je Bureaublad:
- `aegir-website-upload.zip`: de website zelf
- `aegir-database/schema.sql` en `aegir-database/data.sql`: de database

## 2. Database aanmaken

Gebruik een **aparte database** voor de website, niet die van het oude
ledensysteem (`cndh_aegir_gentbe`). Zo kan de website nooit aan de
ledengegevens.

1. Infomaniak Manager → Web Hosting → **Databases** → **Een database toevoegen**,
   bijvoorbeeld `cndh_website`.
2. Maak bij **Gebruikers** een nieuwe databasegebruiker aan met **lees- en
   schrijfrechten** op enkel die database. Noteer naam en wachtwoord veilig.
3. Open **phpMyAdmin**, kies de nieuwe database en ga naar **Importeren**:
   - eerst `schema.sql`
   - daarna `data.sql`

## 3. Bestanden uploaden

1. Web FTP → map van de site (bv. `sites/nieuw.aegir-gent.be`).
2. Upload `aegir-website-upload.zip`, pak uit en verwijder de zip.
   `index.html`, `api/`, `pages/`, … moeten **direct** in die map staan.

## 4. Configuratie invullen

1. Kopieer in Web FTP `api/config.voorbeeld.php` naar `api/config.php`.
2. Open `api/config.php` en vul in:
   - `db_name`, `db_user`, `db_pass`: van stap 2
   - `setup_token`: een lange willekeurige code (min. 24 tekens)

Dit bestand is nooit zichtbaar voor bezoekers en zit niet in git.

## 5. Eerste beheerder aanmaken

1. Surf naar `https://<jouw-domein>/api/setup`.
2. Vul de setup-code, je naam, e-mail en een wachtwoord (min. 12 tekens) in.
3. Maak daarna `setup_token` in `api/config.php` leeg (`''`).

Extra beheerders voeg je toe in het beheer onder **Beheerders**.

## 6. HTTPS

Infomaniak Manager → site → **SSL-certificaat** → Let's Encrypt, en zet
**HTTPS forceren** aan.

## 7. Controleren

- De homepage toont de cijfers, Nieuws toont berichten, Competitie de clubrecords.
- `https://<jouw-domein>/api/config.php` geeft **403** (afgeschermd).
- `https://<jouw-domein>/api/formulieren/ledenfiche.pdf` en `…/medische-fiche.pdf`
  tonen de blanco fiches met het juiste jaar (vanaf 1 november het volgende
  jaar). Ze worden door de site zelf gemaakt (FPDF in `api/lib/fpdf/`); IBAN,
  BIC, polisnummers, adres en e-mail komen uit **Instellingen**.
- Aanmelden op `/pages/admin/` werkt. Stuur een test via het contactformulier
  en kijk of het in de Inbox verschijnt.

## Beveiliging in het kort

- Databasewachtwoord enkel in `api/config.php` op de server.
- Bezoekers kunnen enkel lezen wat gepubliceerd is en enkel formulieren
  insturen. De server controleert elk veld en zet id, datum en status zelf.
- Beheerders: sessiecookie (HttpOnly, SameSite=Strict) + CSRF-token,
  wachtwoorden met `password_hash`, max. 5 foute logins per kwartier.
- Formulieren: max. 5 inzendingen per 10 minuten per IP.
- Uploads: enkel echte afbeeldingen (op inhoud gecontroleerd), willekeurige
  bestandsnaam, en in `/uploads` wordt nooit code uitgevoerd.
