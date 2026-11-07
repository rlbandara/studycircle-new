<?php
// config/db.php: database connection and small helpers.
// Tip: before pushing to a public GitHub repo, keep real passwords out of it
// (commit a db.example.php and list db.php in .gitignore).

define('DB_HOST', 'localhost');
define('DB_NAME', 'studycircle_db');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP default is empty
define('BASE_URL', '/studycircle'); // use '' when the site sits at the domain root (live hosting)
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // where uploaded notes are stored
define('MAX_UPLOAD_MB', 10);

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Could not connect to the database. Check the settings in config/db.php.');
}

// Escape text before printing it in HTML (prevents XSS).
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Build a link that works from any folder: url('student/dashboard.php')
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// "Nimal Perera" becomes "NP" (used for avatar circles)
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out !== '' ? $out : 'U';
}

// Five-star text, e.g. stars(4.2) gives the four filled stars and one empty star
function stars(float $rating): string
{
    $n = (int)round($rating);
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

// Same colour for the same subject everywhere
function subject_badge(string $name, int $subjectId): string
{
    $classes = ['badge-sc', 'badge-sc-sky', 'badge-sc-lavender', 'badge-sc-amber'];
    return '<span class="' . $classes[$subjectId % 4] . '">' . e($name) . '</span>';
}

// pending / approved / rejected tag
function status_badge(string $status): string
{
    $map = ['approved' => 'badge-sc-success', 'pending' => 'badge-sc-amber', 'rejected' => 'badge-sc-danger'];
    return '<span class="badge-sc ' . ($map[$status] ?? 'badge-sc') . '">' . e(ucfirst($status)) . '</span>';
}

// Prints the error message under a form field (if that field has one)
function err_msg(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<div class="form-text-error">' . e($errors[$key]) . '</div>' : '';
}

// Page number buttons that keep the current search / filter in the link
function pager(int $page, int $totalPages): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $out = '<nav class="d-flex justify-content-center gap-2 mt-4 flex-wrap" aria-label="Pages">';
    for ($p = 1; $p <= $totalPages; $p++) {
        $cls  = $p === $page ? 'btn-sc' : 'btn-sc-outline';
        $href = '?' . e(http_build_query(array_merge($_GET, ['page' => $p])));
        $out .= '<a class="' . $cls . '" style="min-width:44px;padding:0" href="' . $href . '"' . ($p === $page ? ' aria-current="page"' : '') . '>' . $p . '</a>';
    }
    return $out . '</nav>';
}
