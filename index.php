<?php
include $_SERVER['DOCUMENT_ROOT'] . '/modules/global.php';
$PROJECTS_BASE = __DIR__ . '/projects';

function parse_project_info(string $filePath, string $slug): array {
    $info = [
        'slug'        => $slug,
        'name'        => $slug,
        'description' => '',
        'category'    => 'Other',
    ];

    $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $info;
    }

    foreach ($lines as $line) {
        if (preg_match('/^\s*Name\s*:\s*(.+)$/i', $line, $m)) {
            $info['name'] = trim($m[1]);
        } elseif (preg_match('/^\s*Description\s*:\s*(.+)$/i', $line, $m)) {
            $info['description'] = trim($m[1]);
        } elseif (preg_match('/^\s*Category\s*:\s*(.+)$/i', $line, $m)) {
            $info['category'] = trim($m[1]);
        }
    }

    return $info;
}

function load_all_projects(string $baseDir): array {
    $result = [];

    if (!is_dir($baseDir)) {
        return $result;
    }

    $projectDirs = glob($baseDir . '/*', GLOB_ONLYDIR);
    foreach ($projectDirs as $dir) {
        $slug = basename($dir);
        $infoFile = $dir . '/.projectinfo';
        if (!is_file($infoFile)) {
            continue;
        }

        $info = parse_project_info($infoFile, $slug);
        $cat = $info['category'] ?: 'Other';

        if (!isset($result[$cat])) {
            $result[$cat] = [];
        }

        $result[$cat][] = $info;
    }

    ksort($result, SORT_NATURAL | SORT_FLAG_CASE);

    foreach ($result as $cat => &$projects) {
        usort($projects, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    }

    return $result;
}

$projectCategories = load_all_projects($PROJECTS_BASE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sylvamy Docs – Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<?= docs_theme_css_link_tag(); ?>
<link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/nav.php'; ?>

<main class="doc-main home-main">
    <header class="home-title">
        <h1>Sylvamy Docs</h1>
    </header>

    <?php if (empty($projectCategories)): ?>
        <p>No projects found yet. Drop some <code>.projectinfo</code> files into <code>/projects/&lt;ProjectName&gt;/.projectinfo</code> to get started.</p>
    <?php else: ?>
        <?php foreach ($projectCategories as $category => $projects): ?>
            <section class="category-section">
                <h2 class="category-title"><?= htmlspecialchars($category) ?></h2>
                <div class="project-grid">
                    <?php foreach ($projects as $proj): ?>
                        <a class="project-card" href="/project/<?= urlencode($proj['slug']) ?>">
                            <div class="project-card-title"><?= htmlspecialchars($proj['name']) ?></div>
                            <?php if (!empty($proj['description'])): ?>
                                <div class="project-card-desc"><?= htmlspecialchars($proj['description']) ?></div>
                            <?php else: ?>
                                <div class="project-card-desc">No description yet.</div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
<script src="/assets/js/main.js"></script>
</body>
</html>
