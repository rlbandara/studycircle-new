<?php
// Securely serve the logged-in student's profile photo.

require_once __DIR__ . '/../includes/auth_check.php';

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT profile_image FROM users WHERE id = ?'
);
$stmt->execute([$userId]);

$filename = $stmt->fetchColumn();

if (!is_string($filename) || $filename === '') {
    http_response_code(404);
    exit;
}

// Accept only generated image filenames.
if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $filename)) {
    http_response_code(404);
    exit;
}

$folder = __DIR__ . '/../uploads/profile_pictures/';
$filePath = $folder . $filename;

if (!is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit;
}

$allowedMime = [
    'image/jpeg',
    'image/png',
    'image/webp'
];

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($filePath);

if (!in_array($mime, $allowedMime, true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
