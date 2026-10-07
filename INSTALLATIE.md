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
- Aanmelden op `/pages/admin/` werkt. Stuur een test via het contactformulier
  en kijk of het in de Inbox verschijnt.

## 8. Automatisch uploaden via GitHub

Na de eerste installatie (stappen 1 tot 7: database, `api/config.php`,
`/api/setup`) kan GitHub elke wijziging zelf uploaden. Elke keer dat er iets
op de `main`-branch komt, zet GitHub de website automatisch op de server.
De eerste installatie zelf blijft zoals hierboven beschreven.

### a. Een apart FTP-account maken bij Infomaniak

1. Infomaniak Manager → **Web Hosting** → je hosting → **FTP / SSH**.
2. **Een gebruiker toevoegen** (FTP-gebruiker), bijvoorbeeld `github-deploy`.
3. Kies een sterk, nieuw wachtwoord (gebruik het nergens anders).
4. Beperk de toegang tot **de map van de site**, bv. `sites/nieuw.aegir-gent.be`.
   Zo kan dit account nergens anders aan (geen andere sites, geen databases).
5. Laat **FTPS** (versleutelde verbinding) toe. Gewoon FTP zonder
   versleuteling wordt door de upload nooit gebruikt.

Heb je een **SSH-gebruiker** in plaats van een FTP-gebruiker? Dan kan het ook
via **SFTP** (zie `DEPLOY_PROTOCOL` hieronder).

### b. Servernaam en poort

Infomaniak toont bij het FTP-account de **servernaam** (host), meestal iets als
`xxxx.ftp.infomaniak.com`. Noteer die.

- FTPS: poort **21** (standaard, hoef je niet in te vullen)
- SFTP: poort **22** (standaard, hoef je niet in te vullen)

### c. De gegevens in GitHub zetten

GitHub → de repository → **Settings** → **Secrets and variables** →
**Actions** → **New repository secret**. Maak deze secrets aan (de namen
moeten exact zo geschreven worden):

| Naam | Verplicht? | Wat vul je in |
|---|---|---|
| `DEPLOY_HOST` | ja | de servernaam uit stap b, bv. `xxxx.ftp.infomaniak.com` (zonder `ftp://`) |
| `DEPLOY_USER` | ja | de naam van het FTP-account uit stap a |
| `DEPLOY_PASSWORD` | ja | het wachtwoord van dat FTP-account |
| `DEPLOY_PROTOCOL` | nee | `ftps` (standaard) of `sftp` bij een SSH-gebruiker |
| `DEPLOY_PORT` | nee | enkel als Infomaniak een andere poort toont dan 21/22 |
| `DEPLOY_PATH` | nee | doelmap op de server. Standaard `/`: het FTP-account staat al in de map van de site. Bij een SSH-gebruiker (SFTP) is dit wel nodig, bv. `/sites/nieuw.aegir-gent.be` |
| `SITE_URL` | nee | adres van de site, bv. `https://nieuw.aegir-gent.be`. Dan test GitHub na elke upload of de site werkt. |

De optionele instellingen (`DEPLOY_PROTOCOL`, `DEPLOY_PORT`, `DEPLOY_PATH`,
`SITE_URL`) mogen ook bij het tabblad **Variables** staan, want ze zijn niet
geheim. Wachtwoorden horen nooit in de code of in een bestand in git.

### d. De eerste keer handmatig starten

1. GitHub → tabblad **Actions** → links **Deploy naar Infomaniak**.
2. Klik **Run workflow** → branch `main` → **Run workflow**.
3. Wacht tot er een groen vinkje staat. Klik erop om te zien wat er gebeurd is:
   - "Veiligheidscontrole OK": er zat geen wachtwoord of `config.php` in de upload.
   - "Upload klaar": alles staat op de server.
   - "Rooktest geslaagd" (als `SITE_URL` ingevuld is): homepage, cijfers en
     afscherming van `config.php` werken.
4. Rood kruis? Open de stap met het kruis: de foutmelding zegt wat er
   ontbreekt (bv. een secret die nog niet bestaat). Als de instellingen of de
   veiligheidscontrole niet in orde zijn, wordt er **niets** geüpload.

Daarna gebeurt het vanzelf bij elke wijziging op `main`. Er loopt nooit meer
dan één upload tegelijk.

Liever eerst lokaal proberen zonder iets te wijzigen? Met `lftp` en `rsync`
geïnstalleerd:

```sh
DEPLOY_HOST=xxxx.ftp.infomaniak.com DEPLOY_USER=github-deploy \
DEPLOY_PASSWORD='…' DEPLOY_DRY_RUN=1 ./scripts/deploy.sh
```

### e. Wat er nooit overschreven of verwijderd wordt

- **`api/config.php`** (met het databasewachtwoord): wordt nooit geüpload,
  overschreven of verwijderd. Hetzelfde voor andere `api/config*`-bestanden,
  bv. een reservekopie.
- **`uploads/`**: foto's en documenten die beheerders via de site uploadden
  worden nooit overschreven of verwijderd. Enkel bestanden uit
  `uploads/documenten/` en `uploads/fotos/shop/` die op de server nog **niet**
  bestaan, worden toegevoegd. Alleen `uploads/.htaccess` (de
  beveiligingsregels van die map) wordt altijd bijgewerkt.
- Andere bestanden en mappen in de hoofdmap (bv. oude pagina's zoals
  `agenda.html`, of mappen van Infomaniak) blijven onaangeroerd.
- In `images/`, `documents/` en `data/` worden bestanden bijgewerkt, maar
  nooit verwijderd.
- Wel opgeruimd: in de codemappen `pages/`, `css/`, `js/` en `api/` worden
  bestanden die niet meer in git staan verwijderd, zodat er geen oude
  PHP-code blijft staan.
- Nooit geüpload: `database/`, `supabase/`, `scripts/`, `.github/`,
  `*.md`-bestanden, `.git*`, `.DS_Store` en de oude pagina's in de hoofdmap.

## Beveiliging in het kort

- Databasewachtwoord enkel in `api/config.php` op de server.
- Bezoekers kunnen enkel lezen wat gepubliceerd is en enkel formulieren
  insturen. De server controleert elk veld en zet id, datum en status zelf.
- Beheerders: sessiecookie (HttpOnly, SameSite=Strict) + CSRF-token,
  wachtwoorden met `password_hash`, max. 5 foute logins per kwartier.
- Formulieren: max. 5 inzendingen per 10 minuten per IP.
- Uploads: enkel echte afbeeldingen (op inhoud gecontroleerd), willekeurige
  bestandsnaam, en in `/uploads` wordt nooit code uitgevoerd.
