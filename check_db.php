<?php
require 'config.php';
try {
    $stmt = $pdo->query("DESCRIBE surveys");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('limit_one', $columns) && in_array('allow_edit', $columns)) {
        echo "Columns exist.";
    } else {
        echo "Columns MISSING. Found: " . implode(', ', $columns);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
