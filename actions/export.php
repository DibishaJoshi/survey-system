<?php
session_start();
require '../config.php';
require '../functions.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Check ownership logic if needed, simplified here

$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$id]);
$survey = $stmt->fetch();

if (!$survey || $survey['type'] !== 'custom') {
    die("Invalid survey for export.");
}

// Fetch all questions for headers
$stmt = $pdo->prepare("SELECT id, question_text FROM questions WHERE survey_id = ? ORDER BY order_index ASC");
$stmt->execute([$id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare headers
$csv_headers = ['Response ID', 'Submitted At'];
foreach ($questions as $q) {
    $csv_headers[] = $q['question_text'];
}

// Fetch responses
$stmt = $pdo->prepare("SELECT id, submitted_at FROM responses WHERE survey_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$id]);
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="survey_export_' . $id . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, $csv_headers);

foreach ($responses as $resp) {
    $row = [$resp['id'], $resp['submitted_at']];
    
    // Fetch answers
    $stmtA = $pdo->prepare("SELECT question_id, answer_text FROM answers WHERE response_id = ?");
    $stmtA->execute([$resp['id']]);
    $answers_raw = $stmtA->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach ($questions as $q) {
        $row[] = isset($answers_raw[$q['id']]) ? $answers_raw[$q['id']] : '';
    }
    
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
