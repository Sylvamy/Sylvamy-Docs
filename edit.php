<?php
include $_SERVER['DOCUMENT_ROOT'] . '/modules/global.php';
if (!$AUTH_USER) {
    header('Location: /');
    exit;
}

$PROJECTS_BASE = __DIR__ . '/projects';
$DEFAULT_PROJECT = 'Zephyr';

function clean_project_slug(string $slug): string {
    $slug = trim($slug);
    $slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', $slug);
    return $slug !== '' ? $slug : 'Zephyr';
}
function resolve_md_target(string $projectsBase, string $projectSlug, string $pageInput): array {
    $raw = trim($pageInput);

    $raw = str_replace('\\', '/', $raw);
    $raw = preg_replace('#/+#', '/', $raw);
    $raw = ltrim($raw, '/');
    if (preg_match('/\.md$/i', $raw)) {
        $raw = preg_replace('/\.md$/i', '', $raw);
    }
    $raw = str_replace('..', '', $raw);
    if ($raw === '' || strcasecmp($raw, 'project') === 0) {
        return [
            'absPath' => $projectsBase . '/' . $projectSlug . '/Project.md',
            'viewRelPath' => null,
            'pretty' => 'Project',
        ];
    }

    $parts = array_values(array_filter(explode('/', $raw), fn($p) => trim($p) !== ''));
    $filePart = array_pop($parts);
    $dirParts = $parts;

    $cleanSeg = function(string $seg): string {
        $seg = trim($seg);
        $seg = str_replace(["\0"], '', $seg);
        $seg = preg_replace('/[^a-zA-Z0-9 _\-]/', '', $seg);
        $seg = preg_replace('/\s+/', ' ', $seg);
        return trim($seg);
    };

    $dirParts = array_map($cleanSeg, $dirParts);
    $filePart = $cleanSeg($filePart);
    if ($filePart === '') $filePart = 'Untitled';

    $pagesDir = $projectsBase . '/' . $projectSlug . '/pages';
    $absDir = $pagesDir . (count($dirParts) ? '/' . implode('/', $dirParts) : '');

    $exactPath = $absDir . '/' . $filePart . '.md';
    $chosenBase = $filePart;

    if (!is_file($exactPath) && is_dir($absDir)) {
        $bestNum = null;
        $bestBase = null;

        foreach (glob($absDir . '/*.md') ?: [] as $fp) {
            $base = basename($fp, '.md');
            if (preg_match('/^(\d+)-(.*)$/', $base, $m)) {
                $num = (int)$m[1];
                $rest = trim($m[2]);

                if (strcasecmp($rest, $filePart) === 0) {
                    if ($bestNum === null || $num < $bestNum) {
                        $bestNum = $num;
                        $bestBase = $base;
                    }
                }
            }
        }

        if ($bestBase !== null) {
            $chosenBase = $bestBase;
        }
    }

    $absPath = $absDir . '/' . $chosenBase . '.md';
    $cleanTitle = $chosenBase;
    if (preg_match('/^(\d+)-(.*)$/', $chosenBase, $m)) {
        $cleanTitle = trim($m[2]) !== '' ? trim($m[2]) : $chosenBase;
    }

    $viewRelPath = count($dirParts)
        ? (implode('/', $dirParts) . '/' . $cleanTitle)
        : $cleanTitle;
    $pretty = (count($dirParts) ? implode('/', $dirParts) . '/' : '') . $chosenBase;

    return [
        'absPath' => $absPath,
        'viewRelPath' => $viewRelPath,
        'pretty' => $pretty,
    ];
}

function ensure_parent_dir(string $filePath): void {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}
function slugify_path(string $relPath): string {
    $segments = explode('/', $relPath);
    $slugSegments = [];
    foreach ($segments as $seg) {
        $seg = trim($seg);
        $seg = preg_replace('/\s*-\s*/', '-', $seg);
        $seg = preg_replace('/\s+/', '-', $seg);
        $slugSegments[] = $seg;
    }
    return implode('/', $slugSegments);
}

function build_page_url(string $projectSlug, string $relPath): string {
    $slug = slugify_path($relPath);
    return '/project/' . urlencode($projectSlug) . '/' . $slug;
}

$projectSlug = clean_project_slug($_GET['project'] ?? $DEFAULT_PROJECT);
$pageInput   = $_GET['page'] ?? 'Project';

$saved = false;
$created = false;
$willCreate = false;
$error = null;

$target = resolve_md_target($PROJECTS_BASE, $projectSlug, $pageInput);
$filePath = $target['absPath'];

$projectRoot = realpath($PROJECTS_BASE . '/' . $projectSlug);
if ($projectRoot === false) {
    $projectRoot = $PROJECTS_BASE . '/' . $projectSlug;
}

$fileExists = is_file($filePath);
$willCreate = !$fileExists;

$content = "";
if ($fileExists) {
    $content = file_get_contents($filePath);
    if ($content === false) $content = "";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectSlug = clean_project_slug($_POST['project'] ?? $projectSlug);
    $pageInput   = (string)($_POST['page'] ?? $pageInput);
    $newContent  = (string)($_POST['content'] ?? '');

    $target = resolve_md_target($PROJECTS_BASE, $projectSlug, $pageInput);
    $filePath = $target['absPath'];

    ensure_parent_dir($filePath);

    $created = !is_file($filePath);

    $ok = @file_put_contents($filePath, $newContent, LOCK_EX);
    if ($ok === false) {
        $error = "Failed to save file (permissions?)";
    } else {
        $saved = true;
        $content = $newContent;
        $fileExists = true;
        $willCreate = false;
    }
}

$projectTitle = ucwords(str_replace(['-', '_'], ' ', $projectSlug));
$viewUrl = null;
if (!empty($target['viewRelPath'])) {
    $viewUrl = build_page_url($projectSlug, $target['viewRelPath']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit – <?= htmlspecialchars($projectTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= docs_theme_css_link_tag(); ?>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/nav.php'; ?>

<div class="edit-wrap">
    <form class="edit-card" method="post">
        <div class="edit-top">
            <div class="edit-row">
                <h1><i class="fa-solid fa-pen-to-square"></i> Edit Markdown</h1>

                <div class="edit-field" style="flex:0 0 240px;">
                    <label>Project</label>
                    <input name="project" value="<?= htmlspecialchars($projectSlug) ?>" spellcheck="false" />
                </div>

                <div class="edit-field" style="flex:1 1 620px;">
                    <label>Page (path)</label>
                    <input
                        name="page"
                        value="<?= htmlspecialchars($target['pretty']) ?>"
                        placeholder='Changelog  OR  Getting Started/1-Installation'
                        spellcheck="false"
                    />
                </div>

                <div class="edit-actions">
                    <?php if ($viewUrl): ?>
                        <a class="edit-btn" href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" rel="noopener">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> View
                        </a>
                    <?php endif; ?>

                    <button class="edit-btn primary" type="submit">
                        <i class="fa-solid fa-floppy-disk"></i> Save
                    </button>
                </div>
            </div>
        </div>

        <div class="edit-status">
            <span class="pill"><i class="fa-regular fa-file-lines"></i> <?= htmlspecialchars(basename($filePath)) ?></span>
            <span class="pill"><i class="fa-solid fa-folder"></i> <?= htmlspecialchars(str_replace($PROJECTS_BASE . '/', '', dirname($filePath))) ?></span>

            <?php if ($created): ?>
                <span class="pill warn"><i class="fa-solid fa-wand-magic-sparkles"></i> Created on save</span>
            <?php elseif ($willCreate): ?>
                <span class="pill warn"><i class="fa-regular fa-circle-question"></i> New file (will be created on save)</span>
            <?php endif; ?>

            <?php if ($saved): ?>
                <span class="pill ok"><i class="fa-solid fa-check"></i> Saved</span>
            <?php endif; ?>

            <?php if ($error): ?>
                <span class="pill bad"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></span>
            <?php endif; ?>
        </div>

        <div class="edit-editor">
            <textarea class="edit-textarea" name="content" spellcheck="false"><?= htmlspecialchars($content) ?></textarea>
        </div>

        <div class="edit-footer">
            <div>
                Tip: Your viewer URL uses the “clean” name (so <code>1-Installation.md</code> shows as <code>Installation</code> in the page route).
            </div>
            <div>
                File: <code><?= htmlspecialchars(str_replace(__DIR__ . '/', '', $filePath)) ?></code>
                <?php if (!$fileExists): ?><span style="opacity:.75;">(missing)</span><?php endif; ?>
            </div>
        </div>
    </form>
</div>

</body>
</html>
