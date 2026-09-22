-- Kør denne fil i phpMyAdmin på databasen simple_shop.
-- Den opretter lagerkøen, som får en linje hver gang en kunde køber en vare.

CREATE TABLE IF NOT EXISTS `warehouse_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_title` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','picked') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `warehouse_order_id` (`order_id`),
  KEY `warehouse_status` (`status`),
  CONSTRAINT `warehouse_items_order_fk`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Læg eksisterende ordrelinjer i lagerkøen én gang.
INSERT INTO `warehouse_items` (`order_id`, `product_id`, `product_title`, `quantity`)
SELECT oi.`order_id`, oi.`product_id`, oi.`product_title`, oi.`quantity`
FROM `order_items` oi
WHERE NOT EXISTS (
  SELECT 1
  FROM `warehouse_items` wi
  WHERE wi.`order_id` = oi.`order_id`
    AND wi.`product_id` = oi.`product_id`
);