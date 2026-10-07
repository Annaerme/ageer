<?php
// Welke tabellen de API kent en wat bezoekers ermee mogen.
//
// Kolomtypes: uuid, str (≤ 500 tekens), text, date, datetime, bool, int, num, json.
//
// - public_read:   bezoekers mogen lezen. public_where wordt dan altijd
//                  toegevoegd (bv. enkel gepubliceerd nieuws).
// - public_insert: bezoekers mogen één rij insturen met enkel deze kolommen.
//                  De rest (id, datum, status) zet de server zelf.
// - Alles wat hier niet staat, is enkel voor ingelogde beheerders.

declare(strict_types=1);

function table_defs(): array
{
    return [
        'agenda_items' => [
            'table' => 'web_agenda_items', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'titel' => 'str', 'datum' => 'date', 'datum_einde' => 'date',
                'tijdstip' => 'str', 'type' => 'str', 'locatie' => 'str', 'beschrijving' => 'text',
                'doelgroep' => 'str', 'inschrijven_url' => 'str', 'gepubliceerd' => 'bool',
                'aangemaakt_op' => 'datetime',
            ],
            'required' => ['titel', 'datum'],
            'public_read' => true, 'public_where' => ['gepubliceerd' => 1],
        ],
        'nieuws_items' => [
            'table' => 'web_nieuws_items', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'titel' => 'str', 'datum' => 'date', 'categorie' => 'str',
                'samenvatting' => 'text', 'inhoud' => 'text', 'foto_url' => 'str',
                'gepubliceerd' => 'bool', 'aangemaakt_op' => 'datetime',
            ],
            'required' => ['titel', 'datum'],
            'public_read' => true, 'public_where' => ['gepubliceerd' => 1],
        ],
        'shop_producten' => [
            'table' => 'web_shop_producten', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'slug' => 'str', 'naam' => 'str', 'beschrijving' => 'text',
                'prijs' => 'num', 'foto_url' => 'str', 'maten' => 'json', 'beschikbaar' => 'bool',
                'volgorde' => 'int', 'aangemaakt_op' => 'datetime',
            ],
            'required' => ['naam', 'slug'],
            'public_read' => true, 'public_where' => ['beschikbaar' => 1],
        ],
        'bestuur' => [
            'table' => 'web_bestuur', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'naam' => 'str', 'functie' => 'str', 'extra_functies' => 'str',
                'email' => 'str', 'telefoon' => 'str', 'volgorde' => 'int', 'actief' => 'bool',
                'aangemaakt_op' => 'datetime',
            ],
            'required' => ['naam'],
            'public_read' => true, 'public_where' => ['actief' => 1],
        ],
        'partners' => [
            'table' => 'web_partners', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'naam' => 'str', 'website' => 'str', 'beschrijving' => 'text',
                'volgorde' => 'int', 'actief' => 'bool', 'aangemaakt_op' => 'datetime',
            ],
            'required' => ['naam'],
            'public_read' => true, 'public_where' => ['actief' => 1],
        ],
        'ereleden' => [
            'table' => 'web_ereleden', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'naam' => 'str', 'jaar' => 'int', 'notitie' => 'text',
                'volgorde' => 'int', 'aangemaakt_op' => 'datetime',
            ],
            'required' => ['naam'],
            'public_read' => true,
        ],
        'clubrecords' => [
            'table' => 'web_clubrecords', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'code' => 'str', 'discipline' => 'str', 'type' => 'str', 'bad' => 'int',
                'geslacht' => 'str', 'categorie' => 'str', 'tijd' => 'str', 'atleet' => 'str',
                'datum' => 'str', 'wedstrijd' => 'str', 'aangemaakt_op' => 'datetime',
            ],
            'required' => ['code', 'discipline', 'categorie', 'tijd'],
            'public_read' => true,
        ],
        'disciplines' => [
            'table' => 'web_disciplines', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'code' => 'str', 'naam' => 'str', 'reeks' => 'str',
                'beschrijving' => 'text', 'volgorde' => 'int',
            ],
            'required' => ['code', 'naam'],
            'public_read' => true,
        ],
        'niveaugroepen' => [
            'table' => 'web_niveaugroepen', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'naam' => 'str', 'emoji' => 'str', 'baan' => 'str',
                'beschrijving' => 'text', 'trainer' => 'str', 'volgorde' => 'int', 'actief' => 'bool',
            ],
            'required' => ['naam'],
            'public_read' => true, 'public_where' => ['actief' => 1],
        ],
        'locaties' => [
            'table' => 'web_locaties', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'naam' => 'str', 'adres' => 'str', 'dag' => 'str', 'tijdstip' => 'str',
                'seizoen' => 'str', 'gebruik' => 'str', 'info' => 'text', 'volgorde' => 'int',
                'actief' => 'bool',
            ],
            'required' => ['naam'],
            'public_read' => true, 'public_where' => ['actief' => 1],
        ],
        'homepage_stats' => [
            'table' => 'web_homepage_stats', 'pk' => 'id',
            'cols' => ['id' => 'uuid', 'waarde' => 'str', 'label' => 'str', 'volgorde' => 'int'],
            'required' => ['waarde', 'label'],
            'public_read' => true,
        ],
        'site_settings' => [
            'table' => 'web_site_settings', 'pk' => 'sleutel',
            'cols' => ['sleutel' => 'str', 'waarde' => 'text', 'label' => 'str'],
            'required' => ['sleutel'],
            'public_read' => true,
        ],

        // ── Inbox: bezoekers mogen insturen, enkel beheerders mogen lezen ──
        'contact_berichten' => [
            'table' => 'web_contact_berichten', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'voornaam' => 'str', 'naam' => 'str', 'email' => 'str',
                'onderwerp' => 'str', 'bericht' => 'text', 'ingediend_op' => 'datetime',
                'gelezen' => 'bool',
            ],
            'required' => ['voornaam', 'naam', 'email', 'bericht'],
            'public_insert' => ['voornaam', 'naam', 'email', 'onderwerp', 'bericht'],
        ],
        'inschrijvingen' => [
            'table' => 'web_inschrijvingen', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'voornaam' => 'str', 'naam' => 'str', 'geboortedatum' => 'date',
                'email' => 'str', 'telefoon' => 'str', 'adres' => 'str', 'type_lid' => 'str',
                'niveau' => 'str', 'opmerking' => 'text', 'ingediend_op' => 'datetime',
                'status' => 'str',
            ],
            'required' => ['voornaam', 'naam', 'email'],
            'public_insert' => [
                'voornaam', 'naam', 'geboortedatum', 'email', 'telefoon', 'adres',
                'type_lid', 'niveau', 'opmerking',
            ],
        ],
        'shop_bestellingen' => [
            'table' => 'web_shop_bestellingen', 'pk' => 'id',
            'cols' => [
                'id' => 'uuid', 'voornaam' => 'str', 'naam' => 'str', 'email' => 'str',
                'telefoon' => 'str', 'opmerking' => 'text', 'regels' => 'json', 'totaal' => 'num',
                'status' => 'str', 'ingediend_op' => 'datetime',
            ],
            'required' => ['voornaam', 'naam', 'email', 'regels'],
            'public_insert' => ['voornaam', 'naam', 'email', 'telefoon', 'opmerking', 'regels'],
        ],
    ];
}

function table_def(string $name): array
{
    $defs = table_defs();
    if (!isset($defs[$name])) {
        throw new HttpError(404, 'Onbekende tabel.');
    }
    return $defs[$name];
}
