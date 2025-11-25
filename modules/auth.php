<?php
declare(strict_types=1);

$AUTH_USER = null;

function load_env_from_document_root(string $filename = '/.env'): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $path = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . $filename;
    if ($path === '' || !is_file($path) || !is_readable($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) return;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') continue;

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        $value = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $value);

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env(string $key, ?string $default = null): string {
    load_env_from_document_root();
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') return (string)($default ?? '');
    return (string)$v;
}

function env_int(string $key, int $default): int {
    $v = env($key, (string)$default);
    return is_numeric($v) ? (int)$v : $default;
}

function is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
    return false;
}

function db(): mysqli {
    static $conn = null;
    if ($conn instanceof mysqli) return $conn;

    $host = env('DB_HOST');
    $user = env('DB_USER');
    $pass = env('DB_PASS');
    $name = env('DB_NAME');

    if ($host === '' || $user === '' || $name === '') {
        throw new RuntimeException('Missing DB_* values in /.env');
    }

    $conn = new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        throw new RuntimeException('DB connect failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function b64url_encode(string $bin): string {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function b64url_decode(string $txt): string|false {
    $pad = strlen($txt) % 4;
    if ($pad) $txt .= str_repeat('=', 4 - $pad);
    return base64_decode(strtr($txt, '-_', '+/'), true);
}

function auth_cookie_name(): string {
    return env('AUTH_COOKIE_NAME', 'sylvamy_auth');
}

function auth_cookie_days(): int {
    return env_int('AUTH_COOKIE_DAYS', 365);
}

function set_auth_cookie(string $selector, string $validatorB64, int $expiresTs): void {
    $value = $selector . ':' . $validatorB64;
    setcookie(auth_cookie_name(), $value, [
        'expires'  => $expiresTs,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_auth_cookie(): void {
    setcookie(auth_cookie_name(), '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function fetch_user_by_id(int $userId): ?array {
    $stmt = db()->prepare("SELECT id, email, created_at FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function login_user(int $userId): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function try_cookie_login(): ?array {
    $cookieName = auth_cookie_name();
    if (empty($_COOKIE[$cookieName])) return null;

    $raw = (string)$_COOKIE[$cookieName];
    if (!str_contains($raw, ':')) {
        clear_auth_cookie();
        return null;
    }

    [$selector, $validatorB64] = explode(':', $raw, 2);
    $selector = trim($selector);
    $validatorB64 = trim($validatorB64);

    if ($selector === '' || $validatorB64 === '') {
        clear_auth_cookie();
        return null;
    }

    $validatorRaw = b64url_decode($validatorB64);
    if ($validatorRaw === false) {
        clear_auth_cookie();
        return null;
    }

    $stmt = db()->prepare("SELECT id, user_id, token_hash, expires_at FROM auth_tokens WHERE selector = ? LIMIT 1");
    $stmt->bind_param('s', $selector);
    $stmt->execute();
    $res = $stmt->get_result();
    $tokenRow = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$tokenRow) {
        clear_auth_cookie();
        return null;
    }

    $tokenId = (int)$tokenRow['id'];
    $userId  = (int)$tokenRow['user_id'];
    $expires = strtotime((string)$tokenRow['expires_at']) ?: 0;

    if ($expires < time()) {
        $del = db()->prepare("DELETE FROM auth_tokens WHERE id = ?");
        $del->bind_param('i', $tokenId);
        $del->execute();
        $del->close();
        clear_auth_cookie();
        return null;
    }

    $calcHash = hash('sha256', $validatorRaw);
    if (!hash_equals((string)$tokenRow['token_hash'], $calcHash)) {
        $del = db()->prepare("DELETE FROM auth_tokens WHERE id = ?");
        $del->bind_param('i', $tokenId);
        $del->execute();
        $del->close();
        clear_auth_cookie();
        return null;
    }

    $user = fetch_user_by_id($userId);
    if (!$user) {
        $del = db()->prepare("DELETE FROM auth_tokens WHERE id = ?");
        $del->bind_param('i', $tokenId);
        $del->execute();
        $del->close();
        clear_auth_cookie();
        return null;
    }

    login_user($userId);

    $newExpiresTs = time() + (auth_cookie_days() * 86400);

    $newValidatorRaw = random_bytes(32);
    $newValidatorB64 = b64url_encode($newValidatorRaw);
    $newHash = hash('sha256', $newValidatorRaw);

    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);

    $upd = db()->prepare("UPDATE auth_tokens SET token_hash = ?, expires_at = FROM_UNIXTIME(?), last_used_at = NOW(), user_agent = ?, ip_address = ? WHERE id = ?");
    $upd->bind_param('sissi', $newHash, $newExpiresTs, $ua, $ip, $tokenId);
    $upd->execute();
    $upd->close();

    set_auth_cookie($selector, $newValidatorB64, $newExpiresTs);

    return $user;
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!empty($_SESSION['user_id'])) {
    $AUTH_USER = fetch_user_by_id((int)$_SESSION['user_id']);
    if (!$AUTH_USER) {
        $_SESSION = [];
        session_destroy();
        $AUTH_USER = try_cookie_login();
    }
} else {
    $AUTH_USER = try_cookie_login();
}
