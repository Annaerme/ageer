<?php
// Gedeelde basis: configuratie, databaseverbinding, JSON-antwoorden, sessie.

declare(strict_types=1);

date_default_timezone_set('Europe/Brussels');

final class HttpError extends Exception
{
    public function __construct(public int $status, string $message)
    {
        parent::__construct($message);
    }
}

function config(): array
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__ . '/../config.php';
        if (!is_file($file)) {
            throw new HttpError(500, 'De website is nog niet geconfigureerd (api/config.php ontbreekt).');
        }
        $config = require $file;
        foreach (['db_name', 'db_user', 'db_pass'] as $key) {
            if (str_contains((string)($config[$key] ?? ''), 'VUL_IN')) {
                throw new HttpError(500, 'De website is nog niet geconfigureerd: vul api/config.php in.');
            }
        }
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config();
        $pdo = new PDO(
            "mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4",
            $c['db_user'],
            $c['db_pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

function send_json(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_json(): mixed
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return null;
    }
    if (strlen($raw) > 2_000_000) {
        throw new HttpError(413, 'Verzoek te groot.');
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new HttpError(400, 'Ongeldige JSON.');
    }
    return $data;
}

function uuid4(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('aegir_beheer');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '28800');
    session_start();
}

/**
 * Telt pogingen per sleutel binnen een tijdvenster. Gooit 429 als de limiet
 * bereikt is; registreert anders een poging (tenzij $register false is).
 */
function rate_limit(string $key, int $max, int $windowSeconds, bool $register = true): void
{
    $since = date('Y-m-d H:i:s', time() - $windowSeconds);
    $stmt = db()->prepare('SELECT COUNT(*) FROM web_ratelimit WHERE sleutel = ? AND tijdstip > ?');
    $stmt->execute([$key, $since]);
    if ((int)$stmt->fetchColumn() >= $max) {
        throw new HttpError(429, 'Te veel pogingen. Probeer het over enkele minuten opnieuw.');
    }
    if ($register) {
        db()->prepare('INSERT INTO web_ratelimit (sleutel, tijdstip) VALUES (?, ?)')
            ->execute([$key, date('Y-m-d H:i:s')]);
    }
    // Opruimen van oude regels, af en toe.
    if (random_int(1, 50) === 1) {
        db()->prepare('DELETE FROM web_ratelimit WHERE tijdstip < ?')
            ->execute([date('Y-m-d H:i:s', time() - 86400)]);
    }
}
