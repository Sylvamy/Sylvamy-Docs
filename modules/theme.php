<?php
$DOCS_THEME_COOKIE  = 'docs_theme';
$DOCS_THEME_DEFAULT = 'theme-dark';

$DOCS_THEME_ALLOWED = [
    'theme-dark' => '/assets/css/theme-dark.css',
    'theme-ice'  => '/assets/css/theme-ice.css',
    'theme-crazy'  => '/assets/css/theme-crazy.css',
];

$docsThemeKey = $_COOKIE[$DOCS_THEME_COOKIE] ?? $DOCS_THEME_DEFAULT;
if (!isset($DOCS_THEME_ALLOWED[$docsThemeKey])) {
    $docsThemeKey = $DOCS_THEME_DEFAULT;
}

$expiry = time() + (86400 * 365);

if (!headers_sent()) {
    setcookie($DOCS_THEME_COOKIE, $docsThemeKey, [
        'expires'  => $expiry,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function docs_theme_css_href(): string {
    global $DOCS_THEME_ALLOWED, $docsThemeKey;
    return $DOCS_THEME_ALLOWED[$docsThemeKey];
}

function docs_theme_css_link_tag(): string {
    $href = docs_theme_css_href();
    return '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . "\n";
}
