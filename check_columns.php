<?php
require 'config.php';
$tables = ['surveys', 'questions', 'responses'];
foreach ($tables as $table) {
    echo "Table: $table\n";
    $stmt = $pdo->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $columns) . "\n\n";
}
?>
