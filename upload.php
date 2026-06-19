<?php
header('Content-Type: application/json; charset=utf-8');

$uploadDir = __DIR__ . '/uploads/';
$maxSize = 500 * 1024 * 1024;

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => '无法创建 uploads 目录，请检查文件夹权限']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持 POST 请求']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => '文件超过 php.ini 中 upload_max_filesize 限制',
        UPLOAD_ERR_FORM_SIZE => '文件超过表单中 MAX_FILE_SIZE 限制',
        UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        UPLOAD_ERR_EXTENSION => 'PHP扩展中断了文件上传',
    ];
    $errCode = isset($_FILES['file']) ? $_FILES['file']['error'] : UPLOAD_ERR_NO_FILE;
    echo json_encode(['success' => false, 'message' => $errors[$errCode] ?? '未知错误']);
    exit;
}

$file = $_FILES['file'];

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => '文件大小超过 500MB 限制']);
    exit;
}

$originalName = $file['name'];
$filename = pathinfo($originalName, PATHINFO_FILENAME);
$ext = pathinfo($originalName, PATHINFO_EXTENSION);
$safeName = preg_replace('/[^a-zA-Z0-9_\-\.\x{4e00}-\x{9fa5}]/u', '_', $filename);
$safeExt = preg_replace('/[^a-zA-Z0-9]/u', '', $ext);

$finalName = $safeName . ($safeExt ? '.' . $safeExt : '');
$uploadPath = $uploadDir . $finalName;

$counter = 1;
while (file_exists($uploadPath)) {
    $finalName = $safeName . '_' . $counter . ($safeExt ? '.' . $safeExt : '');
    $uploadPath = $uploadDir . $finalName;
    $counter++;
}

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $protocol . '://' . $host . $dir . '/uploads/' . rawurlencode($finalName);

    echo json_encode([
        'success' => true,
        'message' => '上传成功',
        'url' => $url,
        'filename' => $finalName,
        'original_name' => $originalName,
        'size' => $file['size']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '文件移动失败，请检查服务器权限']);
}
