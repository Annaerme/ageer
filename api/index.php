<?php
// Aegir Gent — website-API (enige toegangspoort; zie api/.htaccess)
//
//   /api/rest/v1/<tabel>     lezen (bezoekers/beheer), insturen, wijzigen, verwijderen
//   /api/auth/login|logout|me|wachtwoord
//   /api/beheerders          beheerders oplijsten, toevoegen, verwijderen
//   /api/fotos               foto's oplijsten (GET), uploaden (POST), verwijderen (DELETE)
//   /api/setup               eenmalig de eerste beheerder aanmaken

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/tables.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/rest.php';
require __DIR__ . '/lib/fotos.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = trim(preg_replace('#^.*?/api/#', '', $path), '/');

try {
    if (preg_match('#^rest/v1/([a-z_]+)$#', $path, $m)) {
        handle_rest($m[1], $method);
    }

    switch ("$method $path") {
        case 'POST auth/login':
            $body = request_json();
            $admin = login_admin((string)($body['email'] ?? ''), (string)($body['password'] ?? ''));
            send_json(admin_payload($admin));

        case 'GET auth/me':
            $admin = current_admin() ?? throw new HttpError(401, 'Niet aangemeld.');
            send_json(admin_payload($admin));

        case 'POST auth/logout':
            logout_admin();
            send_json(['ok' => true]);

        case 'POST auth/wachtwoord':
            $admin = require_admin(true);
            $body = request_json();
            $stmt = db()->prepare('SELECT wachtwoord_hash FROM web_beheerders WHERE id = ?');
            $stmt->execute([$admin['id']]);
            if (!password_verify((string)($body['huidig'] ?? ''), (string)$stmt->fetchColumn())) {
                throw new HttpError(403, 'Je huidige wachtwoord klopt niet.');
            }
            validate_new_password((string)($body['nieuw'] ?? ''));
            db()->prepare('UPDATE web_beheerders SET wachtwoord_hash = ? WHERE id = ?')
                ->execute([password_hash((string)$body['nieuw'], PASSWORD_DEFAULT), $admin['id']]);
            send_json(['ok' => true]);

        case 'GET beheerders':
            require_admin(false);
            $rows = db()->query('SELECT id, email, naam, actief, aangemaakt_op, laatst_ingelogd FROM web_beheerders ORDER BY email')->fetchAll();
            send_json(array_map(fn($r) => [...$r, 'id' => (int)$r['id'], 'actief' => (bool)$r['actief']], $rows));

        case 'POST beheerders':
            require_admin(true);
            $body = request_json();
            send_json(create_admin((string)($body['email'] ?? ''), (string)($body['naam'] ?? ''), (string)($body['wachtwoord'] ?? '')), 201);

        case 'DELETE beheerders':
            $admin = require_admin(true);
            $id = (int)($_GET['id'] ?? 0);
            if ($id === (int)$admin['id']) throw new HttpError(422, 'Je kan jezelf niet verwijderen.');
            db()->prepare('DELETE FROM web_beheerders WHERE id = ?')->execute([$id]);
            send_json(['ok' => true]);

        case 'GET fotos':
            handle_fotos_list();
        case 'POST fotos':
            handle_upload();
        case 'DELETE fotos':
            handle_foto_delete();

        case 'GET setup':
        case 'POST setup':
            require __DIR__ . '/lib/setup.php';
            exit;
    }

    throw new HttpError(404, 'Niet gevonden.');
} catch (HttpError $e) {
    send_json(['message' => $e->getMessage()], $e->status);
} catch (Throwable $e) {
    error_log('[aegir-api] ' . $e);
    $debug = false;
    try { $debug = (bool)(config()['debug'] ?? false); } catch (Throwable) {}
    send_json(['message' => $debug ? $e->getMessage() : 'Er ging iets mis op de server. Probeer later opnieuw.'], 500);
}
