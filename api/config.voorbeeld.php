<?php
// Aegir Gent — configuratie van de website-API
//
// 1. Kopieer dit bestand naar `config.php` in dezelfde map (api/config.php).
// 2. Vul de gegevens in van de database en databasegebruiker van de website
//    (Infomaniak Manager → Web Hosting → Databases). Gebruik een APARTE
//    database en gebruiker, niet die van het oude ledensysteem: zo kan de
//    website nooit aan de ledengegevens (rijksregisternummers, adressen).
// 3. Kies een lange, willekeurige setup_token. Die heb je één keer nodig om
//    op /api/setup de eerste beheerder aan te maken.
//
// config.php wordt nooit naar de browser gestuurd (zie api/.htaccess) en
// staat niet in git.

return [
    'db_host'     => 'cndh.myd.infomaniak.com',
    'db_name'     => 'cndh_VUL_IN',
    'db_user'     => 'VUL_IN',
    'db_pass'     => 'VUL_IN',

    // Minstens 24 tekens. Na het aanmaken van de eerste beheerder mag je
    // dit leegmaken ('') om /api/setup volledig uit te schakelen.
    'setup_token' => 'VUL_IN_EEN_LANGE_WILLEKEURIGE_CODE',

    // Zet op true om foutdetails te tonen tijdens het testen. Nooit live.
    'debug'       => false,
];
