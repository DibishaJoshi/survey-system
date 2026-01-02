<?php
require 'config.php';

try {
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS survey_db");
    $pdo->exec("USE survey_db");

    // Table: users
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_users);

    // Create default admin user (admin / admin123)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES ('admin', ?)");
        $stmt->execute([$password]);
        echo "Default admin user created.<br>";
    }

    // Table: surveys
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
    $pdo->exec($sql_surveys);

    // Table: questions
    $sql_questions = "CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        survey_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type VARCHAR(50) NOT NULL,
        options TEXT, -- JSON for multiple choice / checkbox
        order_index INT DEFAULT 0,
        is_required TINYINT(1) DEFAULT 0,
        FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql_questions);

    // Table: responses
    $sql_responses = "CREATE TABLE IF NOT EXISTS responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        survey_id INT NOT NULL,
        token VARCHAR(64) DEFAULT NULL,
        respondent_email VARCHAR(255) DEFAULT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql_responses);

    // Table: answers
    $sql_answers = "CREATE TABLE IF NOT EXISTS answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        response_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT,
        FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql_answers);

    echo "Database setup completed successfully.";

} catch (PDOException $e) {
    die("DB Setup Error: " . $e->getMessage());
}
?>
