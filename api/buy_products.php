<?php
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST required'], 405);
}

$raw = file_get_contents('php://input');
$items = json_decode($raw, true);

if (!is_array($items) || count($items) === 0) {
    jsonResponse(['error' => 'Cart is empty'], 400);
}

$pdo = getPDO();

try {
    $pdo->beginTransaction();

    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);

        if ($id <= 0 || $qty <= 0) {
            throw new Exception('Invalid item in cart');
        }

        // Lock the row so concurrent orders can't oversell
        $stmt = $pdo->prepare("SELECT title, stock FROM products WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new Exception("Product $id not found");
        }

        if ($row['stock'] < $qty) {
            throw new Exception("Not enough stock for '{$row['title']}' (only {$row['stock']} left)");
        }

        $update = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
        $update->execute([':qty' => $qty, ':id' => $id]);
    }

    $pdo->commit();
    jsonResponse(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['error' => $e->getMessage()], 400);
}