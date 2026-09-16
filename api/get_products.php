<?php
require __DIR__ . '/db.php';

try {
$stmt = getPDO()->query("SELECT id, title, price, stock, image FROM products ORDER BY id DESC");    $products = $stmt->fetchAll();
    jsonResponse($products);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}