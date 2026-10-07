<?php
// Blanco ledenfiche (2 blz.) en medische fiche (1 blz.) als PDF, altijd met
// het juiste jaar. Enkel blanco formulieren: nooit ledengegevens, geen
// queries op ledentabellen. Clubgegevens komen uit web_site_settings.
// Opmaak overgenomen van de oude site (FPDF, eenheid pt, A4).

declare(strict_types=1);

require_once __DIR__ . '/fpdf/fpdf.php';

/**
 * Het jaar op de fiches. Vanaf 1 november wordt het lidgeld al hernieuwd
 * voor het volgende jaar, dus tonen we dan al het volgende jaar.
 */
function formulier_jaar(?int $tijd = null): int
{
    $tijd ??= time();
    $jaar = (int)date('Y', $tijd);
    return (int)date('n', $tijd) >= 11 ? $jaar + 1 : $jaar;
}

/** Clubgegevens uit de instellingen, met de huidige teksten als terugval. */
function formulier_gegevens(): array
{
    $g = [
        'iban_club'       => 'BE17 8907 3409 5021',
        'bic_club'        => 'VDSPBE91',
        'polis_sporta'    => '45.236.716',
        'polis_redfed_lo' => '1.102.192',
        'polis_redfed_ba' => '1.102.193',
        'adres_post'      => 'Poortbilk 5, 9032 Wondelgem',
        'email_info'      => 'info@aegir-gent.be',
    ];
    try {
        $in = implode(',', array_fill(0, count($g), '?'));
        $stmt = db()->prepare("SELECT sleutel, waarde FROM web_site_settings WHERE sleutel IN ($in)");
        $stmt->execute(array_keys($g));
        foreach ($stmt->fetchAll() as $r) {
            if (trim((string)$r['waarde']) !== '') $g[$r['sleutel']] = trim((string)$r['waarde']);
        }
    } catch (Throwable $e) {
        error_log('[aegir-api] formulieren: instellingen niet geladen: ' . $e->getMessage());
    }
    // "Poortbilk 5, 9032 Wondelgem" → "Poortbilk 5 - 9032 Wondelgem" (zoals op de oude fiches)
    $g['adres'] = preg_replace('/,\s*(?=\d{4})/', ' - ', $g['adres_post']);
    return $g;
}

/** FPDF's standaardlettertypes werken in Windows-1252: é, ë, € (= chr(128)) gaan zo goed. */
function pdf_tekst(string $s): string
{
    return iconv('UTF-8', 'Windows-1252//TRANSLIT', $s) ?: mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
}

final class FichePdf extends FPDF
{
    /** $code: kenmerk rechtsboven ("INFO", "GDPR", "MED"); per pagina aan te passen. */
    public function __construct(private string $titel, public string $code, private int $jaar,
                                private int $paginas, private array $g)
    {
        parent::__construct('P', 'pt', 'A4');
        $this->SetRightMargin(90);
        $this->SetAutoPageBreak(false);
        $this->SetTitle(pdf_tekst("$titel $jaar"));
        $this->SetAuthor('Reddingsclub Aegir Gent vzw');
    }

    /** Tekst op een vaste plaats (y = midden van de regel, zoals Cell met hoogte 0). */
    public function t(float $x, float $y, string $tekst, string $stijl = '', float $grootte = 10): void
    {
        $this->SetFont('helvetica', $stijl, $grootte);
        $this->SetXY($x, $y);
        $this->Cell(0, 0, pdf_tekst($tekst));
    }

    /** Sectiekop met lijn eronder. */
    public function kop(float $y, string $tekst): void
    {
        $this->Line(30, $y + 5, 530, $y + 5);
        $this->t(30, $y, $tekst, 'B', 12);
    }

    public function Header(): void
    {
        $img = __DIR__ . '/pdf-images';
        $this->Image("$img/aegirboei.png", 30, 23, 80, 80);
        $this->Image("$img/aegircomp.png", 120, 23, 80, 80);
        $this->Image("$img/aegirboot.png", 210, 23, 80, 80);
        $this->t(320, 30, "{$this->jaar} | {$this->code} | Aegir ID", 'B', 10);
        $this->t(320, 54, $this->titel, 'B', 20);
        $this->t(320, 74, "Jaar: {$this->jaar}", 'B', 20);
        $this->SetXY(90, -55);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(440, 0, "Blz {$this->PageNo()} van {$this->paginas}", 0, 0, 'R');
    }

    public function Footer(): void
    {
        $g = $this->g;
        $regels = [
            ['B', 'Reddingsclub Aegir Gent vzw | Rescue Aegir Gent vzw | Boot Redders Aegir vzw'],
            ['', "www.aegir-gent.be - {$g['email_info']} - {$g['iban_club']} ({$g['bic_club']})"],
            ['', "Verzekering: Sporta Polis nr {$g['polis_sporta']} bij Ethias Verzekeringen/RedFed Polis nr LO {$g['polis_redfed_lo']} - BA {$g['polis_redfed_ba']} Arena nv"],
            ['', "Secretariaat: {$g['adres']}"],
        ];
        foreach ($regels as $i => [$stijl, $tekst]) {
            $this->SetFont('helvetica', $stijl, 8);
            $this->SetXY(60, -42 + 9 * $i);
            $this->Cell(440, 0, pdf_tekst($tekst), 0, 0, 'C');
        }
        $this->Line(30, 795, 530, 795);
    }
}

function ledenfiche_pdf(int $jaar, array $g): FichePdf
{
    $pdf = new FichePdf('Ledenfiche', 'INFO', $jaar, 2, $g);

    // ---- Blz 1: persoonlijke gegevens, brevetten, werk, clubwerking, lidmaatschap, akkoord
    $pdf->AddPage();
    $pdf->kop(125, 'PERSOONLIJKE GEGEVENS');
    $pdf->t(310, 125, "Afzender: Aegir-Gent, {$g['adres']}", 'I', 7);
    $pdf->t(80, 140, 'A-', 'B');
    $pdf->t(125, 140, 'S-');
    $pdf->t(200, 140, 'R-');
    $pdf->t(360, 166, '-');
    $pdf->SetFillColor(250, 226, 47);
    $pdf->Rect(360, 180, 200, 55, 'DF');
    $labels = [
        [30, 140, 'Lidnummers'], [310, 140, 'Naam'], [160, 153, 'UiTPAS'], [310, 153, 'Adres'],
        [30, 166, 'Nationaliteit'], [160, 166, 'Geslacht'], [30, 179, 'Geb.datum'], [160, 179, 'Geb.plaats'],
        [30, 192, 'Telefoon'], [370, 192, 'Klevertje mutualiteit'], [30, 205, 'GSM 1'], [160, 205, 'E-mail 1'],
        [30, 218, 'GSM 2'], [160, 218, 'E-mail 2'],
    ];
    foreach ($labels as [$x, $y, $l]) $pdf->t($x, $y, $l, 'I', 7);

    $pdf->kop(242, "BREVETTEN EN DIPLOMA'S");
    $pdf->t(30, 257, 'Plaats jaartal van behalen naast het type, en aanduiden wat past', 'B');
    $pdf->t(30, 272, 'Al bekend', 'I', 7);
    // [code, organisatie, brevet, keuzetekst, lijn voor bijscholingsjaar?]
    $bij = 'laatste bijscholingsjaar';
    $gzb = 'goud - zilver - brons';
    $brevetten = [
        ['HR', 'VTS', 'Hoger Redder', $bij, true],
        ['ZR', 'RedFed', 'Redder op zee', $bij, true],
        ['DR', 'RedFed', 'Duiker redder', $bij, true],
        ['ZWM', 'VTS', 'Zwembadmeester', $bij, true],
        ['KINE', 'Studies', 'Kinisitherapie', 'graduaat - licenciaat', false],
        ['LO', 'Studies', 'Lichamelijke opvoeding', 'regentaat - licenciaat', false],
        ['DOC', 'VTS', 'Docent Hoger Redder', $bij, true],
        ['INIT', 'VTS', 'Initiator Reddend Zwemmen', $bij, true],
        ['TRB', 'VTS', 'Trainer B Reddend Zwemmen', $bij, true],
        ['RED', 'RedFed', 'Brevet Redden', $gzb, false],
        ['REA', 'RedFed', 'Brevet Reanimatie', $gzb, false],
        ['SURV', 'RedFed', 'Brevet Survival', $gzb, false],
        ['AUTO', 'RedFed', 'Brevet Auto te water', $gzb, false],
        ['BR', 'RedFed', 'Brevet Boot redder', '1 - 2 - 3', false],
        ['EHAW', 'RedFed', 'Brevet EHAW', '', false],
    ];
    foreach ($brevetten as $i => [$code, $org, $naam, $keuze, $lijn]) {
        $y = 287 + 13 * $i;
        $pdf->t(30, $y, $code, 'I', 7);
        $pdf->t(50, $y, $org);
        $pdf->t(90, $y, $naam);
        $pdf->Line(230, $y + 5, 255, $y + 5);
        if ($keuze !== '') $pdf->t(260, $y, $keuze);
        if ($lijn) $pdf->Line(380, $y + 5, 405, $y + 5);
    }
    $pdf->t(30, 482, 'RAG', 'I', 7);
    $pdf->t(50, 482, 'RAG');
    $pdf->t(90, 482, 'Jeugdbrevet');
    $pdf->Line(230, 487, 420, 487);
    $pdf->t(50, 495, 'Andere');
    $pdf->Line(230, 500, 420, 500);

    $pdf->kop(525, 'WERK ALS REDDER');
    $pdf->t(30, 540, 'Ik zoek werk als redder');
    $pdf->t(250, 540, 'Ja - Neen');
    $pdf->t(30, 553, 'En dit voor');
    $pdf->t(250, 553, 'vakantie - interim - vast - gelegenheidswerk');

    $pdf->kop(578, 'CLUBWERKING');
    $pdf->t(30, 593, 'Ik wil af en toe als redder de club meehelpen');
    $pdf->t(250, 593, 'Ja - Neen');
    $pdf->t(30, 606, 'Ik wil graag helpen voor andere taken');
    $pdf->t(250, 606, 'Ja - Neen');
    $pdf->Line(300, 611, 450, 611);

    $pdf->kop(631, 'LIDMAATSCHAP');
    foreach (['Recreant', 'Redder', 'Atleet reddingscompetitie'] as $i => $l) $pdf->t(30, 646 + 13 * $i, "O  $l");

    $pdf->kop(696, 'AKKOORD PRIVACY/GEGEVENSBEHEER (GDPR) & HUISHOUDELIJK REGLEMENT');
    $pdf->t(200, 711, 'Handtekening (indien -18 jaar ook naam en handtekening van de ouders)');
    $pdf->t(30, 711, 'Akkoord met privacy-beleid');
    $pdf->t(30, 721, '>> Zie meer uitleg op pagina 2');
    foreach (['Lidmaatschap', 'Communicatie', 'Publicaties', 'Huishoudelijk reglement'] as $i => $l) {
        $pdf->t(30, 740 + 12 * $i, "O $l");
    }

    // ---- Blz 2: privacy (GDPR) en uittreksel huishoudelijk reglement
    $pdf->code = 'GDPR';
    $pdf->AddPage();
    $pdf->kop(125, 'PRIVACY/GEGEVENSBEHEER (GDPR)');
    $mail = $g['email_info'];
    // Elke regel: [x, tekst, vet?]; lege string = witregel. Regels van 10 pt.
    $gdpr = [
        [30, 'Je persoonlijke gegevens. Je laat ze bij ons achter, want dat moet nu eenmaal als je bij een club wil sporten. Maar we kunnen'],
        [30, 'ons voorstellen dat je graag wilt weten waarom wij ze vragen en wat wij ermee doen. Op www.aegir-gent.be/documents/privacy.pdf'],
        [30, 'lees je de volledige en actuele tekst. Heb je na het lezen hiervan nog vragen, laat het ons dan weten via onze vertrouwenspersoon.'],
        '',
        [30, 'Wie is verantwoordelijk voor de verwerking van je gegevens?', true],
        [30, "De club bestaat uit drie vzw's die de gegevens gezamenlijk verwerken. Zij zijn samen de verantwoordelijken voor de verwerking"],
        [30, 'van gegevens zoals beschreven in deze privacyverklaring. Hierna worden deze samen kortweg Aegir of Aegir Gent genoemd.'],
        [35, '- Reddingsclub Aegir Gent vzw p/a Poortbilk 5, 9032 Gent (Wondelgem)'],
        [35, '- Rescue Aegir Gent vzw p/a Poortbilk 5, 9032 Gent (Wondelgem)'],
        [35, '- Boot Redders Aegir vzw, p/a E. Pecherstraat 2, 9050 Gentbrugge'],
        '',
        [30, 'Dit brengt met zich mee dat wij in ieder geval:', true],
        [35, '- je persoonsgegevens verwerken in overeenstemming met het doel waarvoor deze zijn verstrekt, deze doelen en type persoons-'],
        [35, '  gegevens zijn beschreven in deze Privacyverklaring;'],
        [35, '- verwerking van je persoonsgegevens beperkt is tot enkel die gegevens welke nodig zijn voor de doeleinden waarvoor ze'],
        [35, '  worden verwerkt;'],
        [35, '- vragen om je uitdrukkelijke toestemming als wij deze nodig hebben voor de verwerking van je persoonsgegevens;'],
        [35, '- passende technische en organisatorische maatregelen hebben genomen zodat de beveiliging van je persoonsgegevens'],
        [35, '  gewaarborgd is;'],
        [35, '- geen persoonsgegevens doorgeven aan andere partijen, tenzij dit nodig is voor uitvoering van de doeleinden waarvoor ze'],
        [35, '  zijn verstrekt;'],
        [35, '- op de hoogte zijn van je rechten als betrokken persoon omtrent je persoonsgegevens, je hierop willen attent maken en deze'],
        [35, '  willen respecteren.'],
        '',
        [30, '* LIDMAATSCHAP: Voor je lidmaatschap hebben we je gegevens nodig. Onze sportfederaties en Aegir zijn via het decreet van'],
        [30, '  10/06/2016 gemachtigd om het rijksregisternummer van de aangesloten leden op te vragen en te bewaren in het ledenbestand'],
        [30, '  (Art. 11 Par1 4e). We geven je gegevens enkel door aan anderen als dat nodig is voor je lidmaatschap. Bv Sporta Federatie'],
        [30, '  en Reddingsfederatie die de lidmaatschappen verwerken en aan de verzekeringsmaatschappij.'],
        [30, '  Op de ledenpagina van aegir-gent.be kan je aanmelden en de gegevens van je lidmaatschap terugvinden en aanpassen.'],
        [30, '* COMMUNICATIE: Als lid is het belangrijk op de hoogte te blijven van alle nieuws binnen de club. Afhankelijk van de gege-'],
        [30, '  vens die je ons doorgeeft zal je meer of minder informatie ontvangen. Bij het doorgeven van je e-mailadres wordt dit gebruikt'],
        [30, '  om je op te hoogte te houden van onze activiteiten en ons nieuws. Wil je geen nieuwsbrieven van ons meer ontvangen, meld'],
        [30, "  je dan af via mail aan $mail."],
        [30, '* PUBLICATIES: Onze activiteiten en trainingen geven het ideale kader om een beeld te maken van de clubsfeer. Daarom'],
        [30, "  worden af en toe foto's genomen voor gebuikt in gedrukte en elektronische publicaties van de club. Om iedereen de kans te"],
        [30, '  geven na te genieten van een activiteit, wordt een selectie ervan voor de leden toegankelijk gemaakt via de gesloten facebook'],
        [30, "  groep voor de leden. Foto's worden niet doorgegeven aan anderen."],
        '',
        [30, 'Wat doen we niet met je gegevens?', true],
        [30, 'Wij gebruiken je gegevens nooit om aan jou andere info te tonen dan aan andere leden. Al onze leden zien hetzelfde. Je gege-'],
        [30, 'vens verkopen aan anderen, dat doen we ook nooit.'],
        '',
        [30, 'Kloppen je gegevens niet of heb je andere vragen?', true],
        [30, 'Klopt er iets niet of wil je je gegevens inkijken of laten verwijderen? Dat kan via je account, of neem dan gerust contact op via'],
        [30, "mail $mail, aan de kassa of via de vertrouwenspersoon. We helpen je zo snel mogelijk verder."],
    ];
    foreach ($gdpr as $i => $r) {
        if ($r !== '') $pdf->t($r[0], 140 + 10 * $i, $r[1], ($r[2] ?? false) ? 'B' : '', 9);
    }

    $pdf->kop(606, 'UITTREKSEL HUISHOUDELIJK REGLEMENT');
    $reglement = [
        "* Alle leden dienen mee te werken aan de doelstellingen beschreven in de statuten, met als voornaamste zin 'het promoten",
        '  van de reddingssport in de breedste zin van het woord.',
        "* De leden stralen het 'reddend gebaar' uit in het bad en houden zich aan de reglementeringen opgelegd door de zwembad-",
        '  directie.',
        '* De clubredder blijft de persoon die het laatste woord heeft aan en rond het bad naar de clubleden toe. Hij kan supplementaire',
        '  richtlijnen uitvaardigen bovenop deze van de zwembaddirectie.',
        '* De trainers maken een badindeling met medegoedkeuring van het clubbestuur. De clubleden dienen deze richtlijnen te',
        '  respecteren.',
        '* Minderjarigen dienen steeds onder begeleiding te zwemmen zowel in het klein als het groot bad, hetzij onder begeleiding van',
        '  hun coach, hetzij door een van de ouders wanneer zij geen opgelegde training volgen.',
        '* Aegir Gent onderschrijft een aantal charters: Panathlon verklaring, Geestig sporten, Time out tegen pesten.',
        '* Aegir Gent wil daarnaast een kwalitatieve jeugdwerking bieden, laagdrempelig en inclusief zijn.',
        'Lees de volledige tekst in onze clubfolder.',
    ];
    foreach ($reglement as $i => $l) $pdf->t(30, 621 + 10 * $i, $l, '', 9);

    return $pdf;
}

function medische_fiche_pdf(int $jaar, array $g): FichePdf
{
    $pdf = new FichePdf('Medische fiche', 'MED', $jaar, 1, $g);
    $pdf->AddPage();
    $pdf->t(30, 18, 'MF', 'B');
    $pdf->kop(125, 'ATTEST VAN MEDISCH ONDERZOEK');
    $pdf->t(30, 140, 'Ik, ondergetekende, doctor in de geneeskunde, bevestig dat de sportbeoefenaar,');
    foreach (['Naam', 'Geb.datum', 'Geslacht'] as $i => $l) $pdf->t(30, 155 + 15 * $i, $l, 'I', 7);
    $pdf->t(30, 200, 'aan een medisch onderzoek werd onderworpen, en geschikt bevonden werd voor het beoefenen');
    $pdf->t(30, 215, 'van de reddingszwemsport in recreatief/competitief verband.');
    $pdf->t(30, 245, 'Datum: _ _ / _ _ / 2 0 _ _');
    $pdf->t(30, 320, 'Handtekening en stempel geneesheer');
    return $pdf;
}

/** GET /api/formulieren/<soort>.pdf — publiek, blanco formulier. */
function handle_formulier(string $soort): never
{
    $jaar = formulier_jaar();
    $g = formulier_gegevens();
    [$pdf, $naam] = match ($soort) {
        'ledenfiche'     => [ledenfiche_pdf($jaar, $g), "ledenfiche-$jaar.pdf"],
        'medische-fiche' => [medische_fiche_pdf($jaar, $g), "medische-fiche-$jaar.pdf"],
        default          => throw new HttpError(404, 'Niet gevonden.'),
    };
    $data = $pdf->Output('S');
    header('Content-Type: application/pdf');
    header("Content-Disposition: inline; filename=\"$naam\"");
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: public, max-age=86400');
    echo $data;
    exit;
}
