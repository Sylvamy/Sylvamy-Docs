<?php
include $_SERVER['DOCUMENT_ROOT'] . '/modules/global.php';

$PROJECTS_BASE = __DIR__ . '/projects';
$DEFAULT_PROJECT = 'Zephyr';

require_once __DIR__ . '/libs/Parsedown.php';

function slugify_id(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    return $text ?: 'section';
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
function build_edit_url(string $projectSlug, ?string $pageParam): string {
    if ($pageParam === null || trim($pageParam) === '') {
        return '/edit/' . urlencode($projectSlug);
    }
    $pageParam = ltrim($pageParam, '/');
    return '/edit/' . urlencode($projectSlug) . '/' . $pageParam;
}

function parse_page_filename(string $filename): array {
    $order = null;
    $cleanName = $filename;

    if (preg_match('/^(\d+)-(.*)$/', $filename, $m)) {
        $order = (int)$m[1];
        $cleanName = $m[2];
    }

    $cleanName = trim($cleanName);
    if ($cleanName === '') $cleanName = $filename;

    return [$cleanName, $order];
}

function load_project_structure(string $projectSlug, string $baseDir): array {
    $pagesDir = $baseDir . '/' . $projectSlug . '/pages';
    $structure = [];

    if (!is_dir($pagesDir)) return $structure;
    $categoryDirs = glob($pagesDir . '/*', GLOB_ONLYDIR);
    if ($categoryDirs === false) $categoryDirs = [];

    foreach ($categoryDirs as $catDir) {
        $category = basename($catDir);
        $mdFiles = glob($catDir . '/*.md');
        if ($mdFiles === false) continue;

        $pageList = [];
        foreach ($mdFiles as $filePath) {
            $filename = basename($filePath, '.md');
            [$cleanName, $order] = parse_page_filename($filename);

            $relPath = $category . '/' . $cleanName;

            $pageList[] = [
                'title'    => $cleanName,
                'relPath'  => $relPath,
                'filePath' => $filePath,
                'order'    => $order,
            ];
        }

        if (!empty($pageList)) {
            usort($pageList, function ($a, $b) {
                $ao = $a['order'];
                $bo = $b['order'];

                if ($ao !== null && $bo !== null && $ao !== $bo) return $ao <=> $bo;
                if ($ao !== null && $bo === null) return -1;
                if ($ao === null && $bo !== null) return 1;

                return strcasecmp($a['title'], $b['title']);
            });

            $structure[$category] = $pageList;
        }
    }

    if (!empty($structure)) {
        ksort($structure, SORT_NATURAL | SORT_FLAG_CASE);
    }

    $rootFiles = glob($pagesDir . '/*.md');
    if ($rootFiles === false) $rootFiles = [];

    $rootPages = [];
    foreach ($rootFiles as $filePath) {
        $filename = basename($filePath, '.md');
        [$cleanName, $order] = parse_page_filename($filename);

        $rootPages[] = [
            'title'    => $cleanName,
            'relPath'  => $cleanName,
            'filePath' => $filePath,
            'order'    => $order,
        ];
    }

    if (!empty($rootPages)) {
        usort($rootPages, function ($a, $b) {
            $ao = $a['order'];
            $bo = $b['order'];

            if ($ao !== null && $bo !== null && $ao !== $bo) return $ao <=> $bo;
            if ($ao !== null && $bo === null) return -1;
            if ($ao === null && $bo !== null) return 1;

            return strcasecmp($a['title'], $b['title']);
        });

        $structure['_root'] = $rootPages;
    }

    return $structure;
}

function resolve_active_page(array $structure, ?string $pageParam): ?array {
    if (empty($structure)) return null;
    if ($pageParam !== null) {
        $paramSlug = slugify_path($pageParam);
        foreach ($structure as $category => $pages) {
            foreach ($pages as $page) {
                $pageSlug = slugify_path($page['relPath']);
                if (strcasecmp($pageSlug, $paramSlug) === 0) {
                    return [
                        'category' => $category,
                        'title'    => $page['title'],
                        'relPath'  => $page['relPath'],
                        'filePath' => $page['filePath'],
                        'order'    => $page['order'] ?? null,
                    ];
                }
            }
        }
        return null;
    }

    $firstCategory = array_key_first($structure);
    $firstPage = $structure[$firstCategory][0];

    return [
        'category' => $firstCategory,
        'title'    => $firstPage['title'],
        'relPath'  => $firstPage['relPath'],
        'filePath' => $firstPage['filePath'],
        'order'    => $firstPage['order'] ?? null,
    ];
}
function flatten_structure(array $structure): array {
    $flat = [];
    foreach ($structure as $category => $pages) {
        foreach ($pages as $p) {
            $flat[] = [
                'category' => $category,
                'title'    => $p['title'],
                'relPath'  => $p['relPath'],
                'filePath' => $p['filePath'],
                'order'    => $p['order'] ?? null,
            ];
        }
    }
    return $flat;
}

function build_toc_from_markdown(string $markdown): array {
    $toc = [];
    if (preg_match_all('/^(#{1,3})[ \t]+(.+?)\s*$/m', $markdown, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $hashCount = strlen($m[1]);
            $text = trim($m[2]);
            $level = ($hashCount === 1) ? 1 : 2;

            $toc[] = [
                'level' => $level,
                'text'  => $text,
                'id'    => slugify_id($text),
            ];
        }
    }
    return $toc;
}

function render_markdown_with_ids(string $markdown): string {
    $parsedown = new Parsedown();
    $html = $parsedown->text($markdown);

    return preg_replace_callback(
        '/<h([1-3])>(.*?)<\/h\1>/i',
        function ($m) {
            $level = $m[1];
            $inner = $m[2];
            $text  = strip_tags($inner);
            $id    = slugify_id($text);
            return '<h' . $level . ' id="' . htmlspecialchars($id, ENT_QUOTES) . '">' . $inner . '</h' . $level . '>';
        },
        $html
    );
}

function requested_title_from_param(?string $pageParam, string $fallback): string {
    if ($pageParam === null) return $fallback;
    $tmp = str_replace('\\', '/', $pageParam);
    $tmp = trim($tmp, "/ \t\n\r\0\x0B");
    if ($tmp === '') return $fallback;
    $parts = explode('/', $tmp);
    $last = end($parts);
    return $last !== false && $last !== '' ? $last : $fallback;
}

$projectSlug = $_GET['project'] ?? $DEFAULT_PROJECT;
$projectSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '', $projectSlug);
if ($projectSlug === '') $projectSlug = $DEFAULT_PROJECT;

$pageParam = isset($_GET['page']) ? $_GET['page'] : null;
$pageParamRaw = $pageParam;

if ($pageParam !== null) {
    $pageParam = urldecode($pageParam);
    $pageParam = str_replace(['..', '\\'], '', $pageParam);
    $pageParam = trim($pageParam, "/ \t\n\r\0\x0B");
}

$projectDir = $PROJECTS_BASE . '/' . $projectSlug;
$projectExists = is_dir($projectDir);

$structure = $projectExists ? load_project_structure($projectSlug, $PROJECTS_BASE) : [];

$markdown     = '';
$toc          = [];
$pageHtml     = '';
$pageTitle    = '';
$projectTitle = ucwords(str_replace(['-', '_'], ' ', $projectSlug));
$isHome       = false;
$missingPage  = false;
$active       = null;

$homePath = $PROJECTS_BASE . '/' . $projectSlug . '/Project.md';

$lastEditedFormatted = null;
$prevPage = null;
$nextPage = null;

if ($pageParam === null) {
    if ($projectExists && is_file($homePath)) {
        $isHome = true;
        $pageTitle = $projectTitle;

        $markdown = file_get_contents($homePath) ?: '';
        $toc      = build_toc_from_markdown($markdown);
        $pageHtml = render_markdown_with_ids($markdown);
    } else {
        $active = resolve_active_page($structure, null);

        if ($active !== null && is_file($active['filePath'])) {
            $pageTitle = $active['title'];

            $markdown = file_get_contents($active['filePath']) ?: '';
            $toc      = build_toc_from_markdown($markdown);
            $pageHtml = render_markdown_with_ids($markdown);
        } else {
            if (!$projectExists) {
                $missingPage = true;
                http_response_code(404);
                $pageTitle = $projectTitle;
                $toc = [];
                $pageHtml = '<p>This page doesnt exist!</p>';
            } else {
                $pageTitle = $projectTitle;
                $pageHtml = '<p>No pages found for this project yet.</p>';
            }
        }
    }
} else {
    if (!$projectExists) {
        $missingPage = true;
        http_response_code(404);

        $pageTitle = requested_title_from_param($pageParam, $projectTitle);
        $toc = [];
        $pageHtml = '<p>This page doesnt exist!</p>';
        $active = null;
    } else {
        $active = resolve_active_page($structure, $pageParam);

        if ($active !== null && is_file($active['filePath'])) {
            $pageTitle = $active['title'];

            $markdown = file_get_contents($active['filePath']) ?: '';
            $toc      = build_toc_from_markdown($markdown);
            $pageHtml = render_markdown_with_ids($markdown);
        } else {
            $missingPage = true;
            http_response_code(404);

            $pageTitle = requested_title_from_param($pageParam, $projectTitle);
            $toc = [];
            $pageHtml = '<p>This page doesnt exist!</p>';
            $active = null;
        }
    }
}

$isLoggedIn = !empty($AUTH_USER);

$editLinkText = null;
$editLinkHref = null;

if ($isLoggedIn) {
    $editLinkHref = build_edit_url($projectSlug, $pageParamRaw);

    if ($missingPage) {
        $editLinkText = 'Create This Page';
    } else {
        $editLinkText = 'Edit Page';
    }
}

if (!$missingPage) {
    $sourceFile = null;
    if ($isHome && $projectExists && is_file($homePath)) {
        $sourceFile = $homePath;
    } elseif ($active && isset($active['filePath']) && is_file($active['filePath'])) {
        $sourceFile = $active['filePath'];
    }

    if ($sourceFile !== null) {
        $timestamp = filemtime($sourceFile);

        $tzName = $_SERVER['HTTP_CF_TIMEZONE'] ?? null;
        if ($tzName && in_array($tzName, timezone_identifiers_list(), true)) {
            $tz = new DateTimeZone($tzName);
        } else {
            $tz = new DateTimeZone(date_default_timezone_get());
        }

        $dt = new DateTime('@' . $timestamp);
        $dt->setTimezone($tz);
        $lastEditedFormatted = $dt->format('jS F Y, H:i');
    }
}

if (!$missingPage && $projectExists) {
    $flatPages = flatten_structure($structure);

    if (!empty($flatPages)) {
        if ($isHome) {
            $nextPage = $flatPages[0] ?? null;
        } elseif ($active) {
            foreach ($flatPages as $i => $p) {
                if ($p['relPath'] === $active['relPath']) {
                    $prevPage = $flatPages[$i - 1] ?? null;
                    $nextPage = $flatPages[$i + 1] ?? null;
                    break;
                }
            }
        }
    }
}

$sidebarStructure = $structure;
$rootPagesSidebar = [];
if (isset($sidebarStructure['_root'])) {
    $rootPagesSidebar = $sidebarStructure['_root'];
    unset($sidebarStructure['_root']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sylvamy Docs – <?= htmlspecialchars($projectTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/monokai-sublime.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

<?= docs_theme_css_link_tag(); ?>
<link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="<?= !$projectExists ? 'no-sidebars' : '' ?>">

<?php include $_SERVER['DOCUMENT_ROOT'] . '/modules/nav.php'; ?>

<aside class="sidebar sidebar-left">
    <?php if ($projectExists): ?>
        <div class="accordion">
            <a href="/project/<?= urlencode($projectSlug) ?>" class="sidebar-main-link<?= $isHome ? ' active' : '' ?>">
                <span><?= htmlspecialchars($projectTitle) ?></span>
            </a>

            <?php if (!empty($sidebarStructure)): ?>
                <?php foreach ($sidebarStructure as $category => $pages): ?>
                    <?php $isOpen = ($active && $active['category'] === $category); ?>
                    <div class="accordion-item <?= $isOpen ? 'open' : '' ?>">
                        <button class="accordion-header" type="button">
                            <span class="accordion-label"><?= htmlspecialchars($category) ?></span>
                            <span class="accordion-icon"><i class="fa-solid fa-angle-right"></i></span>
                        </button>
                        <div class="accordion-panel">
                            <ul class="accordion-links">
                                <?php foreach ($pages as $p): ?>
                                    <?php
                                    $isActive = $active && $active['relPath'] === $p['relPath'];
                                    $url = build_page_url($projectSlug, $p['relPath']);
                                    ?>
                                    <li>
                                        <a class="left-nav-link<?= $isActive ? ' active' : '' ?>" href="<?= htmlspecialchars($url) ?>">
                                            <?= htmlspecialchars($p['title']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($rootPagesSidebar)): ?>
                <?php foreach ($rootPagesSidebar as $p): ?>
                    <?php
                    $isActive = $active && $active['relPath'] === $p['relPath'];
                    $url = build_page_url($projectSlug, $p['relPath']);
                    ?>
                    <a class="sidebar-main-link<?= $isActive ? ' active' : '' ?>" href="<?= htmlspecialchars($url) ?>">
                        <span><?= htmlspecialchars($p['title']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($sidebarStructure) && empty($rootPagesSidebar)): ?>
                <p class="sidebar-note">No pages yet for this project.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</aside>

<aside class="sidebar sidebar-right">
    <?php if ($projectExists): ?>
        <div class="toc-title">On this page</div>
        <ul class="toc-list">
            <?php if (!empty($toc)): ?>
                <?php foreach ($toc as $item): ?>
                    <?php
                    $levelClass = $item['level'] === 1 ? 'toc-level-1' : 'toc-level-2';
                    $href = '#' . $item['id'];
                    ?>
                    <li>
                        <a class="toc-link <?= $levelClass ?>" href="<?= htmlspecialchars($href) ?>">
                            <?= htmlspecialchars($item['text']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li><span style="font-size:0.85rem;opacity:.8;">No headings yet.</span></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
</aside>

<main class="doc-main">
    <nav class="breadcrumbs">
        <a href="/">Projects</a>
        <span class="crumb-separator">›</span>
        <a href="/project/<?= urlencode($projectSlug) ?>"><?= htmlspecialchars($projectTitle) ?></a>

        <?php
        $crumb = null;
        if (!$isHome) {
            if ($active) $crumb = $active['title'];
            elseif ($pageParam !== null) $crumb = requested_title_from_param($pageParam, $projectTitle);
        }
        ?>
        <?php if ($crumb): ?>
            <span class="crumb-separator">›</span>
            <span class="crumb-current"><?= htmlspecialchars($crumb) ?></span>
        <?php endif; ?>
    </nav>

    <header class="doc-header">
        <h1 class="project-title"><?= htmlspecialchars($isHome ? $projectTitle : ($pageTitle !== '' ? $pageTitle : $projectTitle)) ?></h1>

        <?php if ($isLoggedIn && $editLinkText && $editLinkHref): ?>
            <div class="doc-meta-row">
                <a class="doc-edit-link" href="<?= htmlspecialchars($editLinkHref) ?>"><?= htmlspecialchars($editLinkText) ?></a>
                <?php if ($lastEditedFormatted): ?>
                    <div class="doc-last-edited">Page last updated <?= htmlspecialchars($lastEditedFormatted) ?></div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($lastEditedFormatted): ?>
                <div class="doc-last-edited">Page last updated <?= htmlspecialchars($lastEditedFormatted) ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </header>

    <section class="doc-section">
        <?php if ($missingPage): ?>
            <div class="missing-note">
                <?= $pageHtml ?>
            </div>
        <?php else: ?>
            <?= $pageHtml ?>
        <?php endif; ?>
    </section>

    <footer class="doc-footer">
        <?php if (!$missingPage): ?>
            <div class="doc-pager">
                <?php if ($prevPage): ?>
                    <a class="pager-button pager-prev" href="<?= htmlspecialchars(build_page_url($projectSlug, $prevPage['relPath'])) ?>">
                        <span class="pager-label">Previous Page</span>
                        <span class="pager-title"><?= htmlspecialchars($prevPage['title']) ?></span>
                    </a>
                <?php else: ?>
                    <span class="pager-placeholder"></span>
                <?php endif; ?>

                <?php if ($nextPage): ?>
                    <a class="pager-button pager-next" href="<?= htmlspecialchars(build_page_url($projectSlug, $nextPage['relPath'])) ?>">
                        <span class="pager-label">Next Page</span>
                        <span class="pager-title"><?= htmlspecialchars($nextPage['title']) ?></span>
                    </a>
                <?php else: ?>
                    <span class="pager-placeholder"></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </footer>
</main>

<?php if ($projectExists): ?>
    <div class="sidebar-mobile-bar">
        <button class="sidebar-toggle sidebar-toggle-left" type="button" aria-label="Toggle navigation sidebar">
            <i class="fa-solid fa-angle-right"></i>
            <span>Navigation</span>
        </button>

        <button class="sidebar-toggle sidebar-toggle-right" type="button" aria-label="Toggle table of contents">
            <i class="fa-solid fa-angle-left"></i>
            <span>On this page</span>
        </button>
    </div>
<?php endif; ?>

<button class="scroll-top-btn" type="button" aria-label="Scroll to top">
    <i class="fa-solid fa-angle-up"></i>
</button>

<script src="/assets/js/main.js"></script>
</body>
</html>
