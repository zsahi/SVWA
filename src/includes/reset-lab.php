<?php
function reset_lab_data(PDO $database, string $scope): void {
    if (!in_array($scope, ['guestbook', 'labs'], true)) {
        throw new InvalidArgumentException('Unknown reset option.');
    }
    $database->beginTransaction();
    try {
        $database->exec('DELETE FROM messages');
        if ($scope === 'labs') {
            $database->exec('DELETE FROM products');
            $database->exec("INSERT INTO products VALUES (1,'Canvas Backpack',49.00,'Travel'),(2,'Desk Lamp',35.00,'Home'),(3,'Travel Mug',18.50,'Travel'),(4,'Wireless Keyboard',65.00,'Tech')");
            $database->exec('DELETE FROM users');
            $database->exec("INSERT INTO users VALUES (1,'admin','admin123'),(2,'student','student123')");
        }
        $database->commit();
    } catch (Throwable $error) {
        if ($database->inTransaction()) $database->rollBack();
        throw $error;
    }
}
