<?php
require __DIR__ . '/db.php';

header('Access-Control-Allow-Origin: *');

$stmt = getPDO()->query(
    "SELECT wi.id AS warehouse_item_id, wi.order_id, wi.product_id,
            wi.product_title, wi.quantity, o.customer_name, o.created_at
     FROM warehouse_items wi
     INNER JOIN orders o ON o.id = wi.order_id
     WHERE wi.status = 'pending'
     ORDER BY o.created_at DESC, wi.id ASC"
);

jsonResponse($stmt->fetchAll());