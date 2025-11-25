<?php
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/modules/global.php';

if (!empty($AUTH_USER)) {
    header('Location: /');
    exit;
}

$error = null;

function create_remember_token(int $userId): void {
    $selector = b64url_encode(random_bytes(16));
    $validatorRaw = random_bytes(32);
    $validatorB64 = b64url_encode($validatorRaw);

    $tokenHash = hash('sha256', $validatorRaw);
    $expiresTs = time() + (AUTH_COOKIE_DAYS * 86400);

    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);

    $stmt = db()->prepare("
        INSERT INTO auth_tokens (user_id, selector, token_hash, expires_at, user_agent, ip_address)
        VALUES (?, ?, ?, FROM_UNIXTIME(?), ?, ?)
    ");
    $stmt->bind_param('ississ', $userId, $selector, $tokenHash, $expiresTs, $ua, $ip);
    $stmt->execute();
    $stmt->close();

    set_auth_cookie($selector, $validatorB64, $expiresTs);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter email + password.';
    } else {
        $stmt = db()->prepare("SELECT id, password_hash FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();

        $stmt->bind_result($id, $passwordHash);
        $found = $stmt->fetch();
        $stmt->close();

        if (!$found || !password_verify($password, (string)$passwordHash)) {
            $error = 'Invalid login.';
        } else {
            $userId = (int)$id;

            login_user($userId);
            create_remember_token($userId);

            header('Location: /');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sylvamy Docs — Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <?= docs_theme_css_link_tag(); ?>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/nav.php'; ?>

<div class="login-wrap">
    <div class="login-card">
        <div class="login-head">
            <h1>Login</h1>
        </div>

        <form class="login-body" method="post" action="/login.php" autocomplete="on">
            <?php if ($error): ?>
                <div class="err"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="field">
                <label>Email</label>
                <input name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="field">
                <label>Password</label>
                <input name="password" type="password" required>
            </div>

            <button class="btn" type="submit">Log in</button>
        </form>
    </div>
</div>

</body>
</html>
