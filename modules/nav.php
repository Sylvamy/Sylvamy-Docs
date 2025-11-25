<?php
$DOC_ROOT = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($DOC_ROOT === '') {
    $DOC_ROOT = rtrim(dirname(__FILE__), '/');
}

if (!isset($PROJECTS_BASE)) {
    $PROJECTS_BASE = $DOC_ROOT . '/projects';
}

if (!isset($DOCS_BASE_URL)) {
    $DOCS_BASE_URL = '';
}
$DOCS_BASE_URL = rtrim($DOCS_BASE_URL, '/');

if (!function_exists('parse_projectinfo_file')) {
    function parse_projectinfo_file(string $path): array {
        $data = [];
        if (!is_file($path)) return $data;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) continue;
            [$key, $value] = explode(':', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key !== '') $data[$key] = $value;
        }
        return $data;
    }
}

if (!function_exists('load_projects_meta')) {
    function load_projects_meta(string $baseDir): array {
        $result = [];

        $projectDirs = glob(rtrim($baseDir, '/') . '/*', GLOB_ONLYDIR);
        if ($projectDirs === false) return $result;

        foreach ($projectDirs as $projDir) {
            $slug = basename($projDir);
            $infoPath = $projDir . '/.projectinfo';

            if (!is_file($infoPath)) continue;

            $info = parse_projectinfo_file($infoPath);

            $name = $info['Name']        ?? $slug;
            $desc = $info['Description'] ?? '';
            $cat  = trim($info['Category'] ?? 'Other');

            if (!isset($result[$cat])) $result[$cat] = [];

            $result[$cat][] = [
                'slug'        => $slug,
                'name'        => $name,
                'description' => $desc,
            ];
        }

        foreach ($result as $category => &$projects) {
            usort($projects, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        }
        unset($projects);

        $order = ['Minecraft', 'Web', 'Discord'];
        uksort($result, function ($a, $b) use ($order) {
            $ia = array_search($a, $order, true);
            $ib = array_search($b, $order, true);

            if ($ia === false && $ib === false) return strcasecmp($a, $b);
            if ($ia === false) return 1;
            if ($ib === false) return -1;
            return $ia <=> $ib;
        });

        return $result;
    }
}

$navProjects = load_projects_meta($PROJECTS_BASE);

$currentProject = null;
if (isset($_GET['project'])) {
    $currentProject = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['project']);
}
?>

<header class="navbar">
    <div class="nav-left">
        <a href="<?= htmlspecialchars($DOCS_BASE_URL !== '' ? $DOCS_BASE_URL . '/' : '/') ?>" class="brand">
            Sylvamy
        </a>

        <nav class="nav-center">
            <?php if (!empty($navProjects)): ?>
                <?php foreach ($navProjects as $category => $projects): ?>
                    <div class="nav-item">
                        <div class="nav-trigger">
                            <?= htmlspecialchars($category) ?>
                        </div>
                        <div class="dropdown-menu">
                            <?php foreach ($projects as $proj): ?>
                                <?php
                                    $url = $DOCS_BASE_URL . '/project/' . rawurlencode($proj['slug']);
                                    $isActive = ($currentProject === $proj['slug']);
                                ?>
                                <a href="<?= htmlspecialchars($url) ?>"
                                   class="dropdown-link<?= $isActive ? ' active' : '' ?>">
                                    <?= htmlspecialchars($proj['name']) ?>
                                    <?php if (!empty($proj['description'])): ?>
                                        <span><?= htmlspecialchars($proj['description']) ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>
    </div>

    <div class="nav-right">
        <a class="nav-link" href="https://github.com/Sylvamy" target="_blank" rel="noopener noreferrer">
            GitHub
        </a>
        <a class="nav-link" href="https://jenkins.sylvamy.xyz" target="_blank" rel="noopener noreferrer">
            Jenkins
        </a>

        <a class="nav-link" href="<?= htmlspecialchars($DOCS_BASE_URL . '/preferences') ?>" aria-label="Preferences">
            <i class="fa-solid fa-gear"></i>
        </a>
    </div>
</header>
