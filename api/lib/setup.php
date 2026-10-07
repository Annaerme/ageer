<?php
// Eenmalige pagina om de eerste beheerder aan te maken. Werkt enkel zolang er
// nog geen enkele beheerder bestaat én met de setup_token uit config.php.

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'");

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$token = (string)(config()['setup_token'] ?? '');
$count = (int)db()->query('SELECT COUNT(*) FROM web_beheerders')->fetchColumn();
$melding = '';
$ok = false;

if ($count > 0 || strlen($token) < 24 || str_starts_with($token, 'VUL_IN')) {
    http_response_code(404);
    $melding = $count > 0
        ? 'Er bestaat al een beheerder. Nieuwe beheerders voeg je toe via de beheerpagina.'
        : 'Setup is uitgeschakeld: stel eerst een setup_token van minstens 24 tekens in api/config.php in.';
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        rate_limit('setup:' . client_ip(), 5, 900);
        if (!hash_equals($token, (string)($_POST['token'] ?? ''))) {
            throw new HttpError(403, 'De setup-code klopt niet.');
        }
        if (($_POST['wachtwoord'] ?? '') !== ($_POST['wachtwoord2'] ?? '')) {
            throw new HttpError(422, 'De wachtwoorden zijn niet gelijk.');
        }
        create_admin((string)($_POST['email'] ?? ''), (string)($_POST['naam'] ?? ''), (string)($_POST['wachtwoord'] ?? ''));
        $ok = true;
        $melding = 'Beheerder aangemaakt. Je kan nu aanmelden. Maak de setup_token in config.php daarna leeg.';
    } catch (HttpError $e) {
        http_response_code($e->status);
        $melding = $e->getMessage();
    }
}
$open = !$ok && $count === 0 && strlen($token) >= 24 && !str_starts_with($token, 'VUL_IN');
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Eerste beheerder | Aegir Gent</title>
  <style>
    body{font-family:system-ui,sans-serif;background:#F4F6FA;color:#1A2030;margin:0;padding:2rem 1rem}
    main{max-width:420px;margin:0 auto;background:#fff;border-radius:12px;padding:2rem;box-shadow:0 8px 32px rgba(0,0,0,.08)}
    h1{font-size:1.25rem;color:#1A2B50;margin:0 0 1rem}
    label{display:block;font-size:.85rem;font-weight:600;margin:.9rem 0 .3rem}
    input{width:100%;box-sizing:border-box;padding:.6rem .75rem;border:1.5px solid #E2E5EC;border-radius:7px;font-size:16px}
    button{margin-top:1.25rem;width:100%;padding:.75rem;border:0;border-radius:7px;background:#1A2B50;color:#F0B429;font-weight:700;font-size:1rem;cursor:pointer}
    .m{padding:.75rem 1rem;border-radius:7px;font-size:.9rem;margin-bottom:1rem;background:#FFF5F5;color:#C53030}
    .ok{background:#F0FFF4;color:#276749}
    a{color:#1A2B50}
  </style>
</head>
<body>
<main>
  <h1>Eerste beheerder aanmaken</h1>
  <?php if ($melding): ?><div class="m <?= $ok ? 'ok' : '' ?>"><?= $h($melding) ?></div><?php endif; ?>
  <?php if ($ok): ?><p><a href="/pages/admin/">Naar de beheerpagina →</a></p><?php endif; ?>
  <?php if ($open): ?>
  <form method="post" autocomplete="off">
    <label for="token">Setup-code (uit config.php)</label>
    <input id="token" name="token" type="password" required>
    <label for="naam">Naam</label>
    <input id="naam" name="naam" required value="<?= $h($_POST['naam'] ?? '') ?>">
    <label for="email">E-mailadres</label>
    <input id="email" name="email" type="email" required value="<?= $h($_POST['email'] ?? '') ?>">
    <label for="wachtwoord">Wachtwoord (min. 12 tekens)</label>
    <input id="wachtwoord" name="wachtwoord" type="password" minlength="12" required autocomplete="new-password">
    <label for="wachtwoord2">Herhaal wachtwoord</label>
    <input id="wachtwoord2" name="wachtwoord2" type="password" minlength="12" required autocomplete="new-password">
    <button type="submit">Beheerder aanmaken</button>
  </form>
  <?php endif; ?>
</main>
</body>
</html>
