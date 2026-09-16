<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST required'], 405);
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    jsonResponse(['error' => 'Invalid id'], 400);
}

try {
    $pdo = getPDO();

    // Find image path so we can delete the file too
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(['error' => 'Product not found'], 404);
    }

    // Remove DB row
    $pdo->prepare("DELETE FROM products WHERE id = :id")->execute([':id' => $id]);

    // Remove file if it lives under uploads/
    if (str_starts_with($row['image'], 'uploads/')) {
        $path = __DIR__ . '/../' . $row['image'];
        if (is_file($path)) @unlink($path);
    }

    jsonResponse(['success' => true]);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}