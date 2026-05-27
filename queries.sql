INSERT INTO vendors (stall_name, vendor_name, email, password, contact_number)
VALUES ('Ian shop', 'Ian Pegenia', 'christiankarlgpegenia@gmail.com', 'varkaforever123', '09489006761');

INSERT INTO products (vendor_id, name, category, price, stock, is_available)
VALUES (1, 'Matcha Latte (Iced)', 'Drinks', 75.00, 50, 1);

SELECT * FROM products;

-- INSERT order
INSERT INTO orders (vendor_id, customer_name, payment_method, total_amount, status)
VALUES (1, 'Walk-in', 'cash', 225.00, 'pending');

-- INSERT order items
INSERT INTO order_items (order_id, product_id, quantity, unit_price)
VALUES (1, 1, 3, 75.00);

-- plop into sales

INSERT INTO sales (order_id, vendor_id, total_amount, sale_date)

VALUES (1, 1, 225.00, CURDATE());

-- SELECT
SELECT * FROM products WHERE vendor_id = 1 AND is_available = 1;

-- UPDATE (change price)
UPDATE products SET price = 80.00 WHERE id = 1;

-- UPDATE (bawas stock after order)
UPDATE products SET stock = stock - 3 WHERE id = 1;

-- DELETE (make product unavail. pero no delete)
UPDATE products SET is_available = 0 WHERE id = 1;

-- actual DELETE
DELETE FROM products WHERE id = 1;

-- WHERE (low stock alert)
SELECT * FROM products WHERE stock < 5 AND vendor_id = 1;

-- JOIN (order details w/ product names)
SELECT o.id, p.name, oi.quantity, oi.unit_price
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
WHERE o.vendor_id = 1;

-- JOIN (daily sales summary)
SELECT SUM(total_amount) AS daily_total, COUNT(*) AS total_orders
FROM sales
WHERE vendor_id = 1 AND sale_date = CURDATE();