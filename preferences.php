<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/global.php'; ?>
<?php

$isLoggedIn = !empty($AUTH_USER);

$THEME_COOKIE = 'docs_theme';
$THEME_DEFAULT = 'theme-dark';

$ALLOWED_THEMES = [
    'theme-dark' => 'Dark (Default)',
    'theme-ice'  => 'Ice (Light)',
    'theme-crazy' => 'Crazy',
];

$currentTheme = $_COOKIE[$THEME_COOKIE] ?? $THEME_DEFAULT;
if (!array_key_exists($currentTheme, $ALLOWED_THEMES)) {
    $currentTheme = $THEME_DEFAULT;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chosen = $_POST['theme'] ?? $THEME_DEFAULT;
    if (!array_key_exists($chosen, $ALLOWED_THEMES)) {
        $chosen = $THEME_DEFAULT;
    }

    $expiry = time() + (86400 * 365);

    setcookie($THEME_COOKIE, $chosen, [
        'expires'  => $expiry,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    header('Location: /preferences?saved=1');
    exit;
}

$saved = (isset($_GET['saved']) && $_GET['saved'] === '1');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sylvamy Docs – Preferences</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <?= docs_theme_css_link_tag(); ?>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/nav.php'; ?>

<aside class="sidebar sidebar-left">
    <div class="accordion">
        <a href="/preferences" class="sidebar-main-link active">
            <span>Appearance</span>
        </a>
    </div>
</aside>

<main class="prefs-wrap">
    <header class="prefs-header">
        <h1 class="prefs-title">Preferences</h1>
        <div class="prefs-sub">Personalise your docs experience.</div>
    </header>

    <section class="prefs-card">
        <form method="post" action="/preferences">
            <div class="prefs-row">
                <div class="prefs-field">
                    <label for="theme">Theme</label>
                    <select class="prefs-select" name="theme" id="theme">
                        <?php foreach ($ALLOWED_THEMES as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $currentTheme === $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="prefs-actions">
                    <button class="prefs-btn" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save
                    </button>
                </div>
            </div>

            <?php if ($saved): ?>
                <div class="prefs-success">
                    <i class="fa-solid fa-check"></i>
                    Saved!
                </div>
            <?php endif; ?>
        </form>
    </section>
</main>

<script src="/assets/js/main.js"></script>
</body>
</html>
