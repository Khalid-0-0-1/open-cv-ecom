<?php
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST required'], 405);
}

$raw = file_get_contents('php://input');
$order = json_decode($raw, true);
$customer = $order['customer'] ?? [];
$items = $order['items'] ?? [];

$customerName = trim((string)($customer['name'] ?? ''));
$customerAddress = trim((string)($customer['address'] ?? ''));

if ($customerName === '' || $customerAddress === '') {
    jsonResponse(['error' => 'Name and address are required'], 400);
}

if (!is_array($items) || count($items) === 0) {
    jsonResponse(['error' => 'Cart is empty'], 400);
}

$pdo = getPDO();

try {
    $pdo->beginTransaction();
    $orderItems = [];
    $total = 0;

    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);

        if ($id <= 0 || $qty <= 0) {
            throw new Exception('Invalid item in cart');
        }

        // Lock the row so concurrent orders can't oversell
        $stmt = $pdo->prepare("SELECT title, price, stock FROM products WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new Exception("Product $id not found");
        }

        if ($row['stock'] < $qty) {
            throw new Exception("Not enough stock for '{$row['title']}' (only {$row['stock']} left)");
        }

        $total += (float)$row['price'] * $qty;
        $orderItems[] = [
            'id' => $id,
            'title' => $row['title'],
            'price' => $row['price'],
            'quantity' => $qty,
        ];

        $update = $pdo->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
        $update->execute([':qty' => $qty, ':id' => $id]);
    }

    $order = $pdo->prepare(
        "INSERT INTO orders (customer_name, customer_address, total)
         VALUES (:customer_name, :customer_address, :total)"
    );
    $order->execute([
        ':customer_name' => $customerName,
        ':customer_address' => $customerAddress,
        ':total' => $total,
    ]);
    $orderId = (int)$pdo->lastInsertId();

    $orderItem = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, product_title, price, quantity)
         VALUES (:order_id, :product_id, :product_title, :price, :quantity)"
    );
    foreach ($orderItems as $item) {
        $orderItem->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['id'],
            ':product_title' => $item['title'],
            ':price' => $item['price'],
            ':quantity' => $item['quantity'],
        ]);

        $warehouseItem = $pdo->prepare(
            "INSERT INTO warehouse_items (order_id, product_id, product_title, quantity)
             VALUES (:order_id, :product_id, :product_title, :quantity)"
        );
        $warehouseItem->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['id'],
            ':product_title' => $item['title'],
            ':quantity' => $item['quantity'],
        ]);
    }

    $pdo->commit();
    jsonResponse(['success' => true, 'orderId' => $orderId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['error' => $e->getMessage()], 400);
}