<?php
// student/download.php: counts the download and sends the file (no screen of its own).
require_once __DIR__ . '/../includes/auth_check.php';

$userId  = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$id      = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, user_id, file_name, file_path, status FROM materials WHERE id = ?');
$stmt->execute([$id]);
$m = $stmt->fetch();

if (!$m || ($m['status'] !== 'approved' && (int)$m['user_id'] !== $userId && !$isAdmin)) {
    set_flash('danger', 'That material was not found.');
    header('Location: ' . url('student/materials.php'));
    exit;
}

$path = UPLOAD_DIR . basename($m['file_path']);   // basename() blocks paths like ../../
if (!is_file($path)) {
    set_flash('danger', 'The file is not available right now. Please tell the uploader or the admin.');
    header('Location: ' . url('student/material_view.php?id=' . $id));
    exit;
}

$pdo->prepare('UPDATE materials SET downloads = downloads + 1 WHERE id = ?')->execute([$id]);

$downloadName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $m['file_name']);
while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
