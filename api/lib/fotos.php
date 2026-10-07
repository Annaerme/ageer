<?php
// Bestanden uploaden, oplijsten en verwijderen (enkel beheerders).
//   fotos      → /uploads/fotos/<map>/   (JPG, PNG, WebP, GIF; max 10 MB)
//   documenten → /uploads/documenten/    (PDF; max 20 MB)
// Het type wordt op de inhoud gecontroleerd, niet op de bestandsnaam, en
// elk bestand krijgt een willekeurige naam.

declare(strict_types=1);

function upload_soorten(): array
{
    return [
        'fotos' => [
            'max'   => 10 * 1024 * 1024,
            'types' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'],
            'fout'  => 'Enkel JPG, PNG, WebP of GIF zijn toegelaten.',
            'mappen' => true,
        ],
        'documenten' => [
            'max'   => 20 * 1024 * 1024,
            'types' => ['application/pdf' => 'pdf'],
            'fout'  => 'Enkel PDF-bestanden zijn toegelaten.',
            'mappen' => false,
        ],
    ];
}

function upload_dir(string $soort): string
{
    $dir = dirname(__DIR__, 2) . '/uploads/' . $soort;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new HttpError(500, 'Uploadmap kan niet aangemaakt worden.');
    }
    return realpath($dir);
}

function upload_url(string $soort, string $relative): string
{
    return "/uploads/$soort/" . implode('/', array_map('rawurlencode', explode('/', $relative)));
}

function is_valid_upload(string $soort, string $mime, string $tmp): bool
{
    if ($soort === 'fotos') {
        return @getimagesize($tmp) !== false;
    }
    $fh = fopen($tmp, 'rb');
    $head = $fh ? fread($fh, 5) : '';
    if ($fh) fclose($fh);
    return $head === '%PDF-';
}

function handle_upload(string $soort): never
{
    require_admin(true);
    $cfg = upload_soorten()[$soort];
    $map = $cfg['mappen'] ? (preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['map'] ?? '')) ?: 'algemeen') : '';
    $file = $_FILES['bestand'] ?? null;
    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new HttpError(400, in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Bestand is te groot.' : 'Geen bestand ontvangen.');
    }
    if ($file['size'] > $cfg['max']) {
        throw new HttpError(413, 'Bestand is te groot (max ' . ($cfg['max'] / 1024 / 1024) . ' MB).');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($cfg['types'][$mime]) || !is_valid_upload($soort, $mime, $file['tmp_name'])) {
        throw new HttpError(415, $cfg['fout']);
    }
    $dir = upload_dir($soort) . ($map !== '' ? "/$map" : '');
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new HttpError(500, 'Uploadmap kan niet aangemaakt worden.');
    }
    $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
    $name = date('Ymd') . '-' . bin2hex(random_bytes(4)) . '-' . substr(trim($base, '-') ?: 'bestand', 0, 40)
        . '.' . $cfg['types'][$mime];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new HttpError(500, 'Opslaan mislukt.');
    }
    chmod("$dir/$name", 0644);
    $rel = ($map !== '' ? "$map/" : '') . $name;
    send_json(['url' => upload_url($soort, $rel), 'name' => $rel], 201);
}

function handle_fotos_list(string $soort): never
{
    require_admin(false);
    $root = upload_dir($soort);
    $exts = upload_soorten()[$soort]['types'];
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || !in_array(strtolower($f->getExtension()), $exts, true)) continue;
        $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
        $out[] = ['name' => $rel, 'url' => upload_url($soort, $rel), 'size' => $f->getSize(), 'mtime' => $f->getMTime()];
    }
    usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    send_json(array_slice($out, 0, 500));
}

function handle_foto_delete(string $soort): never
{
    require_admin(true);
    $root = upload_dir($soort);
    $exts = upload_soorten()[$soort]['types'];
    $rel = (string)($_GET['name'] ?? '');
    $path = realpath($root . '/' . $rel);
    // Enkel bestanden binnen de eigen uploadmap, en enkel toegelaten types.
    if ($rel === '' || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)
        || !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $exts, true)) {
        throw new HttpError(404, 'Bestand niet gevonden.');
    }
    unlink($path);
    send_json(['ok' => true]);
}
