<?php
session_start();
require '../config.php';
require '../functions.php';
requireLogin();

$id = isset($_POST['survey_id']) ? (int)$_POST['survey_id'] : 0;

// Security Check
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$id]);
$survey = $stmt->fetch();

if (!$survey || $survey['type'] !== 'custom') {
    die("Invalid survey or permission denied.");
}

// File Upload Check
if (!isset($_FILES['existing_file']) || $_FILES['existing_file']['error'] !== UPLOAD_ERR_OK) {
    die("Error uploading file.");
}

$file = $_FILES['existing_file']['tmp_name'];

// Parse CSV to find max ID
$max_id = 0;
$handle = fopen($file, "r");
if ($handle !== FALSE) {
    // Get headers
    $headers = fgetcsv($handle);
    
    // Find "Response ID" column index
    $id_col_index = -1;
    foreach ($headers as $index => $col) {
        if (trim($col) === 'Response ID') {
            $id_col_index = $index;
            break;
        }
    }

    if ($id_col_index === -1) {
        die("Invalid CSV format. 'Response ID' column not found.");
    }

    while (($data = fgetcsv($handle)) !== FALSE) {
        if (isset($data[$id_col_index]) && is_numeric($data[$id_col_index])) {
            $val = (int)$data[$id_col_index];
            if ($val > $max_id) {
                $max_id = $val;
            }
        }
    }
    fclose($handle);
}

// Prepare Output
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="survey_results_' . $id . '_merged.csv"');

$output = fopen('php://output', 'w');

// Stream original file content first
$handle = fopen($file, "r");
while (($line = fgets($handle)) !== false) {
    fputs($output, $line);
}
fclose($handle);

// Fetch NEW responses
$stmt = $pdo->prepare("SELECT id, submitted_at FROM responses WHERE survey_id = ? AND id > ? ORDER BY submitted_at ASC");
$stmt->execute([$id, $max_id]);
$new_responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($new_responses) > 0) {
    // Fetch Headers again to ensure column mapping (Assuming standard export structure)
    // We need to map questions to the same order as the export.
    // Ideally we rely on the DB question order which was used for the headers.
    
    $stmtQ = $pdo->prepare("SELECT id FROM questions WHERE survey_id = ? ORDER BY order_index ASC");
    $stmtQ->execute([$id]);
    $questions = $stmtQ->fetchAll(PDO::FETCH_COLUMN);

    foreach ($new_responses as $resp) {
        $row = [$resp['id'], $resp['submitted_at']];
        
        $stmtA = $pdo->prepare("SELECT question_id, answer_text FROM answers WHERE response_id = ?");
        $stmtA->execute([$resp['id']]);
        $answers_raw = $stmtA->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($questions as $q_id) {
            $row[] = isset($answers_raw[$q_id]) ? $answers_raw[$q_id] : '';
        }
        
        fputcsv($output, $row);
    }
}

fclose($output);
exit;
?>
