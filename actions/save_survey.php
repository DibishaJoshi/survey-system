<?php
session_start();
require '../config.php';
require '../functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $type = clean_input($_POST['type']);
    $embed_code = isset($_POST['embed_code']) ? $_POST['embed_code'] : null;
    
    $limit_one = isset($_POST['limit_one']) ? 1 : 0;
    $allow_edit = isset($_POST['allow_edit']) ? 1 : 0;

    try {
        $pdo->beginTransaction();

        $collect_email = isset($_POST['collect_email']) ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO surveys (title, description, type, embed_code, limit_one, allow_edit, collect_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $type, $embed_code, $limit_one, $allow_edit, $collect_email]);
        $survey_id = $pdo->lastInsertId();

        if ($type === 'custom' && isset($_POST['questions'])) {
            $stmtQ = $pdo->prepare("INSERT INTO questions (survey_id, question_text, question_type, options, order_index, is_required) VALUES (?, ?, ?, ?, ?, ?)");
            
            $order = 0;
            foreach ($_POST['questions'] as $q) {
                $qText = clean_input($q['text']);
                $qType = clean_input($q['type']);
                
                $options = null;
                if (($qType === 'multiple_choice' || $qType === 'checkbox') && isset($q['options'])) {
                    // Convert comma separated string to JSON array
                    $opts = array_map('trim', explode(',', $q['options']));
                    $options = json_encode($opts);
                }

                $isRequired = isset($q['required']) ? 1 : 0;

                $stmtQ->execute([$survey_id, $qText, $qType, $options, $order, $isRequired]);
                $order++;
            }
        }

        $pdo->commit();
        redirect('../dashboard.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error saving survey: " . $e->getMessage());
    }
}
?>
