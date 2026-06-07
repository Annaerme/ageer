# Aegir Gent — Nieuwe website

Volledig herbouwde website voor Reddingsclub Aegir Gent, met een modern design dat het groen/teal kleurpalet van de originele site behoudt.

## Projectstructuur

```
aegir-gent/
├── index.html          → Homepage
├── nieuws.html         → Nieuwsoverzicht
├── nieuws-detail.html  → Voorbeeld nieuwsartikel
├── agenda.html         → Agendapagina
├── leden.html          → Ledeninformatie
├── structuur.html      → Organisatiestructuur
├── trainingen.html     → Trainingsinfo
├── competitie.html     → Competitie
├── opleiding.html      → Opleidingen & bijscholingen
├── contact.html        → Contactformulier
├── css/
│   └── style.css       → Volledig design system
├── js/
│   ├── main.js         → Nav scroll + reveal animaties
│   ├── nav.js          → Gedeelde navigatie (inject)
│   └── footer.js       → Gedeelde footer (inject)
├── images/             → Zet hier alle afbeeldingen van aegir-gent.be
│   ├── aegirbanner008.jpg
│   ├── aegirbanner009.jpg
│   ├── logo.png
│   ├── aegirevent001.jpg
│   ├── aegirevent004.jpg
│   ├── aegirevent007.jpg
│   ├── aegirevent008.jpg
│   ├── aegirevent010.jpg
│   ├── aegirboei.png
│   ├── aegircomp.png
│   ├── sporta.png
│   └── redfed.jpg
└── documents/
    ├── infobrochure.pdf
    └── privacy.pdf
```

## Openen in VS Code

1. Open VS Code
2. `File → Open Folder` → selecteer de `aegir-gent` map
3. Installeer de extensie **Live Server** (ritwickdey.liveserver) als je die nog niet hebt
4. Rechtermuisklik op `index.html` → **Open with Live Server**
5. De site opent automatisch in je browser

Of gewoon dubbelklikken op `index.html` om te openen in de browser (zonder live reload).

## Foto's toevoegen

Momenteel staan er placeholders voor alle afbeeldingen.  
Download de originele foto's van `https://www.aegir-gent.be/images/` en zet ze in de `images/` map.

### Script om foto's te downloaden (Node.js)

```js
// download-images.js
const https = require('https');
const fs = require('fs');
const path = require('path');

const images = [
  'aegirbanner008.jpg', 'aegirbanner009.jpg', 'logo.png',
  'aegirevent001.jpg', 'aegirevent004.jpg', 'aegirevent007.jpg',
  'aegirevent008.jpg', 'aegirevent010.jpg',
  'aegirboei.png', 'aegircomp.png', 'sporta.png', 'redfed.jpg'
];

const baseUrl = 'https://www.aegir-gent.be/images/';

images.forEach(img => {
  const file = fs.createWriteStream(path.join('images', img));
  https.get(baseUrl + img, res => res.pipe(file));
  console.log('Downloading:', img);
});
```

Voer uit met: `node download-images.js`

### Upload naar Supabase Storage

```js
// upload-to-supabase.js
const { createClient } = require('@supabase/supabase-js');
const fs = require('fs');
const path = require('path');

const supabase = createClient(
  'https://JOUW_PROJECT.supabase.co',
  'JOUW_SERVICE_ROLE_KEY'
);

const images = fs.readdirSync('./images');

async function upload() {
  for (const img of images) {
    const file = fs.readFileSync(path.join('images', img));
    const { error } = await supabase.storage
      .from('aegir-images')
      .upload(img, file, { upsert: true });
    if (error) console.error('Fout bij', img, error.message);
    else console.log('Geüpload:', img);
  }
}

upload();
```

## Design

- **Fonts**: Syne (display/titels) + DM Sans (body)
- **Kleuren**: teal/groen palet van Aegir Gent
- **Animaties**: scroll-reveal voor alle secties
- **Responsive**: volledig mobiel-vriendelijk

## Pagina's

| Pagina | URL | Inhoud |
|--------|-----|--------|
| Home | index.html | Hero, quicklinks, nieuws, agenda, partners |
| Nieuws | nieuws.html | Nieuwsoverzicht met cards |
| Nieuwsdetail | nieuws-detail.html | Voorbeeld artikelpagina |
| Agenda | agenda.html | Kalender + activiteitentypes |
| Leden | leden.html | Groepsindeling, rekeningen, verzekering |
| Structuur | structuur.html | De twee vzw's + contactgegevens |
| Trainingen | trainingen.html | Trainingsinfo + niveaugroepen |
| Competitie | competitie.html | Wedstrijdinfo |
| Opleiding | opleiding.html | Bijscholingen + cursussen |
| Contact | contact.html | Formulier + contactgegevens |
