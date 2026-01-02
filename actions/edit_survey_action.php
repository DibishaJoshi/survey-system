<?php
session_start();
require '../config.php';
require '../functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $survey_id = (int)$_POST['survey_id'];
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $type = clean_input($_POST['type']);
    $embed_code = isset($_POST['embed_code']) ? $_POST['embed_code'] : null;
    
    $limit_one = isset($_POST['limit_one']) ? 1 : 0;
    $allow_edit = isset($_POST['allow_edit']) ? 1 : 0;

    try {
        $pdo->beginTransaction();

        $collect_email = isset($_POST['collect_email']) ? 1 : 0;

        // Update Survey Details
        $stmt = $pdo->prepare("UPDATE surveys SET title = ?, description = ?, type = ?, embed_code = ?, limit_one = ?, allow_edit = ?, collect_email = ? WHERE id = ?");
        $stmt->execute([$title, $description, $type, $embed_code, $limit_one, $allow_edit, $collect_email, $survey_id]);

        if ($type === 'custom') {
            // Get existing question IDs
            $stmt = $pdo->prepare("SELECT id FROM questions WHERE survey_id = ?");
            $stmt->execute([$survey_id]);
            $existing_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $posted_ids = [];
            $order = 0;

            if (isset($_POST['questions'])) {
                $stmtInst = $pdo->prepare("INSERT INTO questions (survey_id, question_text, question_type, options, order_index, is_required) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtUpd = $pdo->prepare("UPDATE questions SET question_text = ?, question_type = ?, options = ?, order_index = ?, is_required = ? WHERE id = ?");

                foreach ($_POST['questions'] as $q) {
                    $qText = clean_input($q['text']);
                    $qType = clean_input($q['type']);
                    $options = null;

                    if (($qType === 'multiple_choice' || $qType === 'checkbox') && isset($q['options'])) {
                        $opts = array_map('trim', explode(',', $q['options']));
                        $options = json_encode($opts);
                    }

                    $isRequired = isset($q['required']) ? 1 : 0;

                    if (isset($q['id']) && in_array($q['id'], $existing_ids)) {
                        // Update existing
                        $stmtUpd->execute([$qText, $qType, $options, $order, $isRequired, $q['id']]);
                        $posted_ids[] = $q['id'];
                    } else {
                        // Insert new
                        $stmtInst->execute([$survey_id, $qText, $qType, $options, $order, $isRequired]);
                    }
                    $order++;
                }
            }

            // Delete removed questions
            $to_delete = array_diff($existing_ids, $posted_ids);
            if (!empty($to_delete)) {
                $placeholders = implode(',', array_fill(0, count($to_delete), '?'));
                $stmtDel = $pdo->prepare("DELETE FROM questions WHERE id IN ($placeholders)");
                $stmtDel->execute(array_values($to_delete));
            }
        }

        $pdo->commit();
        redirect('../dashboard.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error updating survey: " . $e->getMessage());
    }
}
?>
