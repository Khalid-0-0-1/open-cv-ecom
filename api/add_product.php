<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST required'], 405);
}

$title = trim($_POST['title'] ?? '');
$price = $_POST['price'] ?? '';
$stock = $_POST['stock'] ?? '';

if ($title === '' || !is_numeric($price) || !is_numeric($stock) || (int)$stock < 0) {
    jsonResponse(['error' => 'Title, numeric price, and non-negative stock required'], 400);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'Image upload failed'], 400);
}

$file = $_FILES['image'];

// Validate it's a real image
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);


if (!isset($allowed[$mime])) {
    jsonResponse(['error' => 'Only JPG, PNG, WEBP, or GIF allowed'], 400);
}

// Max 5 MB
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(['error' => 'Image too large (max 5MB)'], 400);
}

$ext = $allowed[$mime];
$filename = bin2hex(random_bytes(8)) . '.' . $ext;
$uploadDir = __DIR__ . '/../uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$destination = $uploadDir . $filename;
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'Could not save image'], 500);
}

// Public path used by the frontend
$imagePath = 'uploads/' . $filename;

try {$stmt = getPDO()->prepare(
    "INSERT INTO products (title, price, stock, image) VALUES (:title, :price, :stock, :image)"
);
$stmt->execute([
    ':title' => $title,
    ':price' => $price,
    ':stock' => (int)$stock,
    ':image' => $imagePath,
]);

jsonResponse([
    'id' => (int)getPDO()->lastInsertId(),
    'title' => $title,
    'price' => (float)$price,
    'stock' => (int)$stock,
    'image' => $imagePath,
], 201);
} catch (Exception $e) {
    // Clean up file if DB insert fails
    @unlink($destination);
    jsonResponse(['error' => $e->getMessage()], 500);
}