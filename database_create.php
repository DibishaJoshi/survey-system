<?php
require 'config.php';
?>
<!DOCTYPE html>
<html>
<head><title></title></head>
<body style="font-family: sans-serif; padding: 2rem;">
<h1>Database Creator</h1>
<ul>
<?php

// Create database if not exists
$pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
$pdo->exec("USE $dbname");

function createTable($pdo, $sql, $tableName) {
    try {
        $pdo->exec($sql);
        echo "<li style='color: green;'>Table <b>$tableName</b> checked/created.</li>";
    } catch (PDOException $e) {
        echo "<li style='color: red;'>Error creating table <b>$tableName</b>: " . $e->getMessage() . "</li>";
    }
}

// Table Create SQLs
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql_surveys = "CREATE TABLE IF NOT EXISTS surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('custom', 'embed') NOT NULL DEFAULT 'custom',
    embed_code TEXT,
    limit_one TINYINT(1) DEFAULT 0,
    allow_edit TINYINT(1) DEFAULT 0,
    collect_email TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql_questions = "CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(50) NOT NULL,
    options TEXT,
    order_index INT DEFAULT 0,
    is_required TINYINT(1) DEFAULT 0,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
)";

$sql_responses = "CREATE TABLE IF NOT EXISTS responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    token VARCHAR(64) DEFAULT NULL,
    respondent_email VARCHAR(255) DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
)";

$sql_answers = "CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    response_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_text TEXT,
    FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
)";

createTable($pdo, $sql_users, 'users');
createTable($pdo, $sql_surveys, 'surveys');
createTable($pdo, $sql_questions, 'questions');
createTable($pdo, $sql_responses, 'responses');
createTable($pdo, $sql_answers, 'answers');

// Create default admin user (admin / admin123)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES ('admin', ?)");
        $stmt->execute([$password]);
        echo "<li style='color: green;'>Default admin user (<b>admin</b>) created. Password: <b>admin123</b></li>";
    } else {
        echo "<li style='color: orange;'>Admin user already exists (Skipped).</li>";
    }
} catch (PDOException $e) {
    echo "<li style='color: red;'>Error creating admin user: " . $e->getMessage() . "</li>";
}

function addColumn($pdo, $table, $column, $definition) {
    try {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "<li style='color: green;'>Fixed/Added column <b>$column</b> to <b>$table</b></li>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            } 
        else {
            echo "<li style='color: red;'>Error adding <b>$column</b>: " . $e->getMessage() . "</li>";
        }
    }
}

addColumn($pdo, 'surveys', 'collect_email', 'TINYINT(1) DEFAULT 0');
addColumn($pdo, 'surveys', 'limit_one', 'TINYINT(1) DEFAULT 0');
addColumn($pdo, 'surveys', 'allow_edit', 'TINYINT(1) DEFAULT 0');
addColumn($pdo, 'questions', 'is_required', 'TINYINT(1) DEFAULT 0');
addColumn($pdo, 'responses', 'respondent_email', 'VARCHAR(255) DEFAULT NULL');
addColumn($pdo, 'responses', 'token', 'VARCHAR(64) DEFAULT NULL');

?>
</ul>
<p><b>Done.</b> You can now <a href="dashboard.php">return to the dashboard</a>.</p>
</body>
</html>
