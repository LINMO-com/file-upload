<?php
header('Content-Type: application/json; charset=utf-8');

$uploadDir = __DIR__ . '/uploads/';
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$baseUrl = $protocol . '://' . $host . $dir . '/uploads/';

if (!is_dir($uploadDir)) {
    echo json_encode(['success' => true, 'files' => []]);
    exit;
}

$files = [];
$entries = scandir($uploadDir, SCANDIR_SORT_DESCENDING);

if ($entries === false) {
    echo json_encode(['success' => false, 'message' => '无法读取目录']);
    exit;
}

foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..' || $entry === '.htaccess') continue;
    $fullPath = $uploadDir . $entry;
    if (!is_file($fullPath)) continue;

    $size = filesize($fullPath);
    $mtime = filemtime($fullPath);
    $encodedName = rawurlencode($entry);

    $files[] = [
        'name' => $entry,
        'size' => $size,
        'time' => $mtime,
        'url' => $baseUrl . $encodedName
    ];
}

echo json_encode(['success' => true, 'files' => $files]);
