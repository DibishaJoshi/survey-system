<?php
require 'config.php';

try {
    // Add columns to surveys table
    $pdo->exec("ALTER TABLE surveys ADD COLUMN limit_one TINYINT(1) DEFAULT 0");
    $pdo->exec("ALTER TABLE surveys ADD COLUMN allow_edit TINYINT(1) DEFAULT 0");
    
    // Add token to responses table
    $pdo->exec("ALTER TABLE responses ADD COLUMN token VARCHAR(64) DEFAULT NULL");
    
    echo "Database updated successfully.";
} catch (PDOException $e) {
    echo "Error updating DB (columns might already exist): " . $e->getMessage();
}
?>
