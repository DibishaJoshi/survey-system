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
        $hash_id = bin2hex(random_bytes(8));

        $stmt = $pdo->prepare("INSERT INTO surveys (title, description, type, embed_code, limit_one, allow_edit, collect_email, hash_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $type, $embed_code, $limit_one, $allow_edit, $collect_email, $hash_id]);
        $survey_id = $pdo->lastInsertId();

        if ($type === 'custom' && isset($_POST['questions'])) {
            $stmtQ = $pdo->prepare("INSERT INTO questions (survey_id, question_text, question_type, options, order_index, is_required) VALUES (?, ?, ?, ?, ?, ?)");

            $order = 0;
            foreach ($_POST['questions'] as $q) {
                $qText = clean_input($q['text']);
                $qType = clean_input($q['type']);

                $options = null;
                if (isset($q['options']) && ($qType === 'multiple_choice' || $qType === 'checkbox' || $qType === 'dropdown')) {
                    $rawOptions = $q['options'];
                    // Try newline first (modern UI)
                    $opts = preg_split('/\r\n|\r|\n/', $rawOptions);
                    $opts = array_filter(array_map('trim', $opts));

                    // If only one entry and it contains a comma, it's likely the legacy format
                    if (count($opts) <= 1 && !empty($rawOptions) && strpos($rawOptions, ',') !== false) {
                        $opts = array_filter(array_map('trim', explode(',', $rawOptions)));
                    }

                    $options = json_encode(array_values($opts));
                }

                $isRequired = isset($q['required']) ? 1 : 0;

                $stmtQ->execute([$survey_id, $qText, $qType, $options, $order, $isRequired]);
                $order++;
            }
        }

        $pdo->commit();
        redirect('../dashboard.php');

    }
    catch (Exception $e) {
        $pdo->rollBack();
        die("Error saving survey: " . $e->getMessage());
    }
}
?>
