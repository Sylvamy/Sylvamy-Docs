<?php
include $_SERVER['DOCUMENT_ROOT'] . '/modules/global.php';
if (!$AUTH_USER) {
    header('Location: /');
    exit;
}

$SITE_ROOT = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
$PROJECTS_BASE = $SITE_ROOT . '/projects';
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

function run_cmd(string $cmd, string $cwd, ?string &$output = null): int {
    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $descriptors, $pipes, $cwd);
    if (!is_resource($proc)) {
        $output = 'Failed to start process';
        return 127;
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $code = proc_close($proc);
    $output = trim((string)$stdout . (string)$stderr);
    return (int)$code;
}

function git_autocommit_file(string $repoRoot, string $absFilePath, bool $created, bool $push, ?string &$gitMsg = null): bool {
    $repoRootReal = realpath($repoRoot);
    if ($repoRootReal === false) {
        $gitMsg = 'Repo root not found';
        return false;
    }
    if (!is_dir($repoRootReal . '/.git')) {
        $gitMsg = 'Not a git repo (missing .git)';
        return false;
    }

    $fileReal = realpath($absFilePath);
    if ($fileReal === false) {
        $gitMsg = 'Saved file not found for git';
        return false;
    }

    $repoPrefix = rtrim($repoRootReal, '/') . '/';
    if (!str_starts_with($fileReal, $repoPrefix)) {
        $gitMsg = 'Refusing to commit: file is outside repo';
        return false;
    }

    $rel = ltrim(substr($fileReal, strlen($repoPrefix)), '/');

    $out = '';
    $code = run_cmd('git status --porcelain -- ' . escapeshellarg($rel), $repoRootReal, $out);
    if ($code !== 0) {
        $gitMsg = 'git status failed: ' . $out;
        return false;
    }
    if (trim($out) === '') {
        $gitMsg = 'No changes to commit';
        return true;
    }

    $code = run_cmd('git add -- ' . escapeshellarg($rel), $repoRootReal, $out);
    if ($code !== 0) {
        $gitMsg = 'git add failed: ' . $out;
        return false;
    }

    $msg = 'Docs: ' . ($created ? 'create ' : 'update ') . $rel;
    $code = run_cmd('git commit -m ' . escapeshellarg($msg) . ' -- ' . escapeshellarg($rel), $repoRootReal, $out);
    if ($code !== 0) {
        if (str_contains($out, 'nothing to commit') || str_contains($out, 'no changes added')) {
            $gitMsg = 'No changes to commit';
            return true;
        }
        $gitMsg = 'git commit failed: ' . $out;
        return false;
    }

    if ($push) {
        $code = run_cmd('git push', $repoRootReal, $out);
        if ($code !== 0) {
            $gitMsg = 'Committed, but push failed: ' . $out;
            return false;
        }
        $gitMsg = 'Committed + pushed: ' . $msg;
        return true;
    }

    $gitMsg = 'Committed: ' . $msg;
    return true;
}

$projectSlug = clean_project_slug($_GET['project'] ?? $DEFAULT_PROJECT);
$pageInput   = $_GET['page'] ?? 'Project';

$saved = false;
$created = false;
$willCreate = false;
$error = null;

$gitOk = null;
$gitMsg = null;

$target = resolve_md_target($PROJECTS_BASE, $projectSlug, $pageInput);
$filePath = $target['absPath'];

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

        // Auto git commit (and optional push)
        $push = (function_exists('env') && env('AUTO_GIT_PUSH', '0') === '1');
        $repoRoot = $SITE_ROOT;
        $gitOk = git_autocommit_file($repoRoot, $filePath, $created, $push, $gitMsg);
        if ($gitOk === false && $gitMsg && !$error) {
            $error = $gitMsg;
        }
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

            <?php if ($gitMsg): ?>
                <span class="pill <?= ($gitOk === false ? 'bad' : 'ok') ?>"><i class="fa-brands fa-git-alt"></i> <?= htmlspecialchars($gitMsg) ?></span>
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
                File: <code><?= htmlspecialchars(str_replace($SITE_ROOT . '/', '', $filePath)) ?></code>
                <?php if (!$fileExists): ?><span style="opacity:.75;">(missing)</span><?php endif; ?>
            </div>
        </div>
    </form>
</div>

</body>
</html>
