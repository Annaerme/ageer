<?php
// Tabel-API met dezelfde query-notatie die de site al gebruikte
// (?kolom=eq.waarde&order=datum.desc&limit=10). Tabel- en kolomnamen komen
// uitsluitend uit tables.php; waarden gaan altijd via prepared statements.

declare(strict_types=1);

const MAX_STR  = 500;
const MAX_TEXT = 100_000;

/** Leest de querystring zelf in, zodat dubbele sleutels (datum=gte.…&datum=lte.…) behouden blijven. */
function query_params(): array
{
    $out = [];
    foreach (explode('&', $_SERVER['QUERY_STRING'] ?? '') as $pair) {
        if ($pair === '') {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
        $out[] = [urldecode($k), urldecode($v)];
    }
    return $out;
}

function to_db_value(string $type, mixed $value, string $col): mixed
{
    if ($value === null || $value === '') {
        return $type === 'str' || $type === 'text' ? ($value === '' ? '' : null) : null;
    }
    switch ($type) {
        case 'bool':
            if (is_bool($value)) return $value ? 1 : 0;
            if ($value === 'true' || $value === '1' || $value === 1) return 1;
            if ($value === 'false' || $value === '0' || $value === 0) return 0;
            break;
        case 'int':
            if (is_int($value) || (is_string($value) && preg_match('/^-?\d{1,9}$/', $value))) return (int)$value;
            break;
        case 'num':
            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) return round((float)$value, 2);
            break;
        case 'date':
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                [$y, $m, $d] = array_map('intval', explode('-', $value));
                if (checkdate($m, $d, $y)) return $value;
            }
            break;
        case 'datetime':
            if (is_string($value) && ($t = strtotime($value)) !== false) return date('Y-m-d H:i:s', $t);
            break;
        case 'uuid':
            if (is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) return strtolower($value);
            break;
        case 'json':
            if (is_string($value)) {
                json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE && strlen($value) <= MAX_TEXT) return $value;
            } elseif (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            break;
        case 'str':
        case 'text':
            if (is_string($value) || is_int($value) || is_float($value)) {
                $s = (string)$value;
                $max = $type === 'str' ? MAX_STR : MAX_TEXT;
                if (mb_strlen($s) <= $max) return $s;
                throw new HttpError(422, "Veld '$col' is te lang.");
            }
            break;
    }
    throw new HttpError(422, "Ongeldige waarde voor '$col'.");
}

function from_db_row(array $def, array $row): array
{
    foreach ($row as $col => $v) {
        if ($v === null) continue;
        switch ($def['cols'][$col] ?? 'str') {
            case 'bool': $row[$col] = (bool)$v; break;
            case 'int':  $row[$col] = (int)$v; break;
            case 'num':  $row[$col] = (float)$v; break;
            case 'json': $row[$col] = json_decode($v, true); break;
            case 'datetime': $row[$col] = date('c', strtotime($v)); break;
        }
    }
    return $row;
}

/** Zet ?kolom=op.waarde om naar een WHERE-clausule. */
function build_where(array $def, array $params, array &$args): array
{
    $ops = ['eq' => '=', 'neq' => '<>', 'gt' => '>', 'gte' => '>=', 'lt' => '<', 'lte' => '<='];
    $parts = [];
    foreach ($params as [$col, $expr]) {
        if (in_array($col, ['select', 'order', 'limit', 'offset'], true)) continue;
        if (!isset($def['cols'][$col])) {
            throw new HttpError(400, "Onbekende kolom '$col'.");
        }
        $type = $def['cols'][$col];
        if (!preg_match('/^(eq|neq|gt|gte|lt|lte|in|is)\.(.*)$/s', $expr, $m)) {
            throw new HttpError(400, "Ongeldige filter voor '$col'.");
        }
        [, $op, $raw] = $m;
        if ($op === 'is') {
            $parts[] = match ($raw) {
                'null' => "`$col` IS NULL",
                'true' => "`$col` = 1",
                'false' => "`$col` = 0",
                default => throw new HttpError(400, "Ongeldige filter voor '$col'."),
            };
        } elseif ($op === 'in') {
            if (!preg_match('/^\((.*)\)$/s', $raw, $mm) || $mm[1] === '') {
                throw new HttpError(400, "Ongeldige lijst voor '$col'.");
            }
            $vals = array_slice(str_getcsv($mm[1], ',', '"', '\\'), 0, 200);
            $parts[] = "`$col` IN (" . implode(',', array_fill(0, count($vals), '?')) . ')';
            foreach ($vals as $v) $args[] = to_db_value($type, $v, $col);
        } else {
            $parts[] = "`$col` {$ops[$op]} ?";
            $args[] = to_db_value($type, $raw, $col);
        }
    }
    return $parts;
}

function build_order(array $def, array $params): string
{
    foreach ($params as [$k, $v]) {
        if ($k !== 'order') continue;
        $out = [];
        foreach (explode(',', $v) as $part) {
            $bits = explode('.', trim($part));
            $col = $bits[0];
            if (!isset($def['cols'][$col])) throw new HttpError(400, "Onbekende kolom '$col'.");
            $dir = strtolower($bits[1] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
            $out[] = "`$col` $dir";
        }
        return $out ? ' ORDER BY ' . implode(', ', $out) : '';
    }
    return '';
}

function param(array $params, string $name): ?string
{
    foreach ($params as [$k, $v]) if ($k === $name) return $v;
    return null;
}

function rest_select(array $def, array $params, bool $isAdmin): array
{
    $args = [];
    $where = build_where($def, $params, $args);
    if (!$isAdmin) {
        foreach ($def['public_where'] ?? [] as $col => $val) {
            $where[] = "`$col` = ?";
            $args[] = $val;
        }
    }
    $sql = "SELECT * FROM `{$def['table']}`"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . build_order($def, $params);
    $limit = param($params, 'limit');
    $offset = param($params, 'offset');
    $limit = $limit !== null && ctype_digit($limit) ? min((int)$limit, 1000) : 1000;
    $sql .= " LIMIT $limit";
    if ($offset !== null && ctype_digit($offset)) $sql .= ' OFFSET ' . (int)$offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return array_map(fn($r) => from_db_row($def, $r), $stmt->fetchAll());
}

function prepare_row(array $def, array $input, bool $isInsert): array
{
    $row = [];
    foreach ($input as $col => $value) {
        if (!is_string($col) || !isset($def['cols'][$col])) {
            throw new HttpError(400, "Onbekend veld '$col'.");
        }
        $row[$col] = to_db_value($def['cols'][$col], $value, $col);
    }
    if ($isInsert) {
        if ($def['cols'][$def['pk']] === 'uuid' && empty($row[$def['pk']])) {
            $row[$def['pk']] = uuid4();
        }
        foreach (['aangemaakt_op', 'ingediend_op'] as $ts) {
            if (isset($def['cols'][$ts]) && empty($row[$ts])) $row[$ts] = date('Y-m-d H:i:s');
        }
        foreach ($def['required'] ?? [] as $req) {
            if (!isset($row[$req]) || $row[$req] === '') {
                throw new HttpError(422, "Veld '$req' is verplicht.");
            }
        }
    } else {
        unset($row[$def['pk']]);
        foreach ($def['required'] ?? [] as $req) {
            if (array_key_exists($req, $row) && ($row[$req] === null || $row[$req] === '')) {
                throw new HttpError(422, "Veld '$req' mag niet leeg zijn.");
            }
        }
    }
    return $row;
}

function insert_row(array $def, array $row): void
{
    $cols = array_keys($row);
    $sql = "INSERT INTO `{$def['table']}` (`" . implode('`,`', $cols) . '`) VALUES ('
        . implode(',', array_fill(0, count($cols), '?')) . ')';
    db()->prepare($sql)->execute(array_values($row));
}

function select_by_pks(array $def, array $pks): array
{
    if (!$pks) return [];
    $stmt = db()->prepare("SELECT * FROM `{$def['table']}` WHERE `{$def['pk']}` IN ("
        . implode(',', array_fill(0, count($pks), '?')) . ')');
    $stmt->execute(array_values($pks));
    return array_map(fn($r) => from_db_row($def, $r), $stmt->fetchAll());
}

function matching_pks(array $def, array $params): array
{
    $args = [];
    $where = build_where($def, $params, $args);
    if (!$where) {
        throw new HttpError(400, 'Een filter is verplicht bij wijzigen of verwijderen.');
    }
    $stmt = db()->prepare("SELECT `{$def['pk']}` FROM `{$def['table']}` WHERE " . implode(' AND ', $where));
    $stmt->execute($args);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function handle_rest(string $name, string $method): never
{
    $def = table_def($name);
    $params = query_params();

    if ($method === 'GET') {
        $isAdmin = current_admin() !== null;
        if (!$isAdmin && empty($def['public_read'])) {
            throw new HttpError(401, 'Je bent niet (meer) aangemeld.');
        }
        send_json(rest_select($def, $params, $isAdmin));
    }

    if ($method === 'POST') {
        $body = request_json();
        $admin = current_admin();
        if (!$admin) {
            if (empty($def['public_insert'])) {
                throw new HttpError(401, 'Je bent niet (meer) aangemeld.');
            }
            public_insert($name, $def, $body);
        }
        require_admin(true);
        $rows = is_array($body) && array_is_list($body) ? $body : [$body];
        if (!$rows || count($rows) > 500) throw new HttpError(400, 'Ongeldig aantal rijen.');
        $pks = [];
        db()->beginTransaction();
        try {
            foreach ($rows as $input) {
                if (!is_array($input)) throw new HttpError(400, 'Ongeldige rij.');
                $row = prepare_row($def, $input, true);
                insert_row($def, $row);
                $pks[] = $row[$def['pk']];
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }
        send_json(select_by_pks($def, $pks), 201);
    }

    if ($method === 'PATCH') {
        require_admin(true);
        $body = request_json();
        if (!is_array($body) || array_is_list($body) || !$body) throw new HttpError(400, 'Ongeldige gegevens.');
        $row = prepare_row($def, $body, false);
        $pks = matching_pks($def, $params);
        if ($pks && $row) {
            $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($row)));
            $sql = "UPDATE `{$def['table']}` SET $set WHERE `{$def['pk']}` IN ("
                . implode(',', array_fill(0, count($pks), '?')) . ')';
            db()->prepare($sql)->execute([...array_values($row), ...$pks]);
        }
        send_json(select_by_pks($def, $pks));
    }

    if ($method === 'DELETE') {
        require_admin(true);
        $pks = matching_pks($def, $params);
        if ($pks) {
            db()->prepare("DELETE FROM `{$def['table']}` WHERE `{$def['pk']}` IN ("
                . implode(',', array_fill(0, count($pks), '?')) . ')')->execute($pks);
        }
        send_json([]);
    }

    throw new HttpError(405, 'Methode niet toegestaan.');
}

/** Formulier van een bezoeker: enkel toegelaten velden, server bepaalt de rest. */
function public_insert(string $name, array $def, mixed $body): never
{
    if (!is_array($body) || array_is_list($body)) throw new HttpError(400, 'Ongeldige gegevens.');
    rate_limit("form:$name:" . client_ip(), 5, 600);

    $input = array_intersect_key($body, array_flip($def['public_insert']));
    foreach ($input as $k => $v) {
        if (is_string($v)) $input[$k] = trim($v);
    }
    if (!filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
        throw new HttpError(422, 'Ongeldig e-mailadres.');
    }

    if ($name === 'shop_bestellingen') {
        [$input['regels'], $input['totaal']] = clean_order_lines($input['regels'] ?? null);
        $input['status'] = 'nieuw';
    } elseif ($name === 'inschrijvingen') {
        $input['status'] = 'nieuw';
    } elseif ($name === 'contact_berichten') {
        $input['gelezen'] = false;
        if (mb_strlen($input['bericht'] ?? '') > 5000) throw new HttpError(422, 'Je bericht is te lang.');
    }
    foreach ($input as $k => $v) {
        if (is_string($v) && $def['cols'][$k] === 'str' && mb_strlen($v) > 300) {
            throw new HttpError(422, "Veld '$k' is te lang.");
        }
    }

    insert_row($def, prepare_row($def, $input, true));
    send_json(['ok' => true], 201);
}

/** Controleert bestelregels en berekent het totaal opnieuw op de server. */
function clean_order_lines(mixed $regels): array
{
    if (is_string($regels)) $regels = json_decode($regels, true);
    if (!is_array($regels) || !array_is_list($regels) || !$regels || count($regels) > 30) {
        throw new HttpError(422, 'Je bestelling is leeg of ongeldig.');
    }
    $clean = [];
    $totaal = 0.0;
    foreach ($regels as $r) {
        if (!is_array($r)) throw new HttpError(422, 'Ongeldige bestelregel.');
        $aantal = filter_var($r['aantal'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
        $prijs = filter_var($r['eenheidsprijs'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($aantal === false || $prijs === false || $prijs < 0 || $prijs > 1000) {
            throw new HttpError(422, 'Ongeldige bestelregel.');
        }
        $lijn = round($aantal * $prijs, 2);
        $clean[] = [
            'product_id'    => mb_substr((string)($r['product_id'] ?? ''), 0, 60),
            'naam'          => mb_substr((string)($r['naam'] ?? ''), 0, 200),
            'maat'          => isset($r['maat']) && $r['maat'] !== null ? mb_substr((string)$r['maat'], 0, 20) : null,
            'aantal'        => $aantal,
            'eenheidsprijs' => round($prijs, 2),
            'totaal'        => $lijn,
        ];
        $totaal += $lijn;
    }
    return [json_encode($clean, JSON_UNESCAPED_UNICODE), round($totaal, 2)];
}
