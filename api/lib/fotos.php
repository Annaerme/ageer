<?php
// Foto's uploaden, oplijsten en verwijderen (enkel beheerders).
// Bestanden komen in /uploads/fotos/<map>/ met een willekeurige naam; enkel
// echte afbeeldingen (gecontroleerd op inhoud, niet op naam) worden aanvaard.

declare(strict_types=1);

const FOTO_MAX_BYTES = 10 * 1024 * 1024;
const FOTO_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

function fotos_dir(): string
{
    $dir = dirname(__DIR__, 2) . '/uploads/fotos';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new HttpError(500, 'Uploadmap kan niet aangemaakt worden.');
    }
    return realpath($dir);
}

function foto_url(string $relative): string
{
    return '/uploads/fotos/' . implode('/', array_map('rawurlencode', explode('/', $relative)));
}

function handle_upload(): never
{
    require_admin(true);
    $map = preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['map'] ?? '')) ?: 'algemeen';
    $file = $_FILES['bestand'] ?? null;
    if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new HttpError(400, in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Bestand is te groot.' : 'Geen bestand ontvangen.');
    }
    if ($file['size'] > FOTO_MAX_BYTES) {
        throw new HttpError(413, 'Bestand is te groot (max 10 MB).');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(FOTO_TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new HttpError(415, 'Enkel JPG, PNG, WebP of GIF zijn toegelaten.');
    }
    $dir = fotos_dir() . '/' . $map;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new HttpError(500, 'Uploadmap kan niet aangemaakt worden.');
    }
    $base = preg_replace('/[^a-z0-9]+/', '-', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
    $name = date('Ymd') . '-' . bin2hex(random_bytes(4)) . '-' . substr(trim($base, '-') ?: 'foto', 0, 40)
        . '.' . FOTO_TYPES[$mime];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new HttpError(500, 'Opslaan mislukt.');
    }
    chmod("$dir/$name", 0644);
    send_json(['url' => foto_url("$map/$name"), 'name' => "$map/$name"], 201);
}

function handle_fotos_list(): never
{
    require_admin(false);
    $root = fotos_dir();
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || !in_array(strtolower($f->getExtension()), FOTO_TYPES, true)) continue;
        $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
        $out[] = ['name' => $rel, 'url' => foto_url($rel), 'size' => $f->getSize(), 'mtime' => $f->getMTime()];
    }
    usort($out, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
    send_json(array_slice($out, 0, 500));
}

function handle_foto_delete(): never
{
    require_admin(true);
    $root = fotos_dir();
    $rel = (string)($_GET['name'] ?? '');
    $path = realpath($root . '/' . $rel);
    // Enkel bestanden binnen uploads/fotos, en enkel afbeeldingen.
    if ($rel === '' || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)
        || !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), FOTO_TYPES, true)) {
        throw new HttpError(404, 'Foto niet gevonden.');
    }
    unlink($path);
    send_json(['ok' => true]);
}
