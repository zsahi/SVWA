USE svwa;
CREATE TABLE products (id INT PRIMARY KEY, name VARCHAR(100), price DECIMAL(8,2), category VARCHAR(60));
INSERT INTO products VALUES (1,'Canvas Backpack',49.00,'Travel'),(2,'Desk Lamp',35.00,'Home'),(3,'Travel Mug',18.50,'Travel'),(4,'Wireless Keyboard',65.00,'Tech');
CREATE TABLE users (id INT PRIMARY KEY, username VARCHAR(60), password VARCHAR(100));
INSERT INTO users VALUES (1,'admin','teal-lantern-42'),(2,'student','practice123');
CREATE TABLE messages (id INT AUTO_INCREMENT PRIMARY KEY, author VARCHAR(80), message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
