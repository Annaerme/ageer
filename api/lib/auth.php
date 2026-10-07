<?php
// Inloggen van beheerders: sessiecookie (HttpOnly, SameSite=Strict) plus een
// CSRF-token dat bij elke wijziging als header X-CSRF-Token mee moet.

declare(strict_types=1);

const SESSIE_MAX_INACTIEF = 8 * 3600;

function current_admin(): ?array
{
    start_session();
    $id = $_SESSION['beheerder_id'] ?? null;
    if (!$id) {
        return null;
    }
    if (time() - ($_SESSION['laatste_activiteit'] ?? 0) > SESSIE_MAX_INACTIEF) {
        logout_admin();
        return null;
    }
    $stmt = db()->prepare('SELECT id, email, naam FROM web_beheerders WHERE id = ? AND actief = 1');
    $stmt->execute([$id]);
    $admin = $stmt->fetch();
    if (!$admin) {
        logout_admin();
        return null;
    }
    $_SESSION['laatste_activiteit'] = time();
    return $admin;
}

function require_admin(bool $mutating): array
{
    $admin = current_admin();
    if (!$admin) {
        throw new HttpError(401, 'Je bent niet (meer) aangemeld.');
    }
    if ($mutating) {
        $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
            throw new HttpError(403, 'Ongeldige sessie. Herlaad de pagina en meld opnieuw aan.');
        }
    }
    return $admin;
}

function admin_payload(array $admin): array
{
    return [
        'email' => $admin['email'],
        'naam'  => $admin['naam'],
        'csrf'  => $_SESSION['csrf'],
    ];
}

function login_admin(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));
    $ip = client_ip();
    // Max 5 mislukte pogingen per kwartier, per IP en per e-mailadres.
    rate_limit('login-ip:' . $ip, 5, 900, false);
    rate_limit('login-mail:' . $email, 5, 900, false);

    $stmt = db()->prepare('SELECT id, email, naam, wachtwoord_hash FROM web_beheerders WHERE email = ? AND actief = 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    // Altijd een hash controleren, ook als het account niet bestaat, zodat de
    // responstijd niet verraadt welke e-mailadressen bestaan.
    $hash = $row['wachtwoord_hash'] ?? password_hash(random_bytes(16), PASSWORD_DEFAULT);
    if (!password_verify($password, $hash) || !$row) {
        rate_limit('login-ip:' . $ip, PHP_INT_MAX, 900);
        rate_limit('login-mail:' . $email, PHP_INT_MAX, 900);
        throw new HttpError(401, 'E-mailadres of wachtwoord klopt niet.');
    }

    if (password_needs_rehash($row['wachtwoord_hash'], PASSWORD_DEFAULT)) {
        db()->prepare('UPDATE web_beheerders SET wachtwoord_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    db()->prepare('UPDATE web_beheerders SET laatst_ingelogd = ? WHERE id = ?')
        ->execute([date('Y-m-d H:i:s'), $row['id']]);

    start_session();
    session_regenerate_id(true);
    $_SESSION['beheerder_id'] = (int)$row['id'];
    $_SESSION['laatste_activiteit'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $row;
}

function logout_admin(): void
{
    start_session();
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'],
        'httponly' => true, 'samesite' => 'Strict',
    ]);
    session_destroy();
}

function validate_new_password(string $password): void
{
    if (mb_strlen($password) < 12) {
        throw new HttpError(422, 'Het wachtwoord moet minstens 12 tekens lang zijn.');
    }
    if (strlen($password) > 200) {
        throw new HttpError(422, 'Het wachtwoord is te lang.');
    }
}

function create_admin(string $email, string $naam, string $password): array
{
    $email = mb_strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new HttpError(422, 'Ongeldig e-mailadres.');
    }
    validate_new_password($password);
    $exists = db()->prepare('SELECT COUNT(*) FROM web_beheerders WHERE email = ?');
    $exists->execute([$email]);
    if ((int)$exists->fetchColumn() > 0) {
        throw new HttpError(409, 'Er bestaat al een beheerder met dit e-mailadres.');
    }
    db()->prepare('INSERT INTO web_beheerders (email, naam, wachtwoord_hash, aangemaakt_op) VALUES (?, ?, ?, ?)')
        ->execute([$email, mb_substr(trim($naam), 0, 200), password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
    return ['id' => (int)db()->lastInsertId(), 'email' => $email, 'naam' => trim($naam)];
}
