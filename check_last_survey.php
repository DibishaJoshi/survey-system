<?php
require 'config.php';
$stmt = $pdo->query("SELECT * FROM surveys ORDER BY id DESC LIMIT 1");
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

$output = "";
if ($survey) {
    $output .= "ID: " . $survey['id'] . "\n";
    $output .= "Type: " . $survey['type'] . "\n";
    $output .= "Embed Code Raw: " . $survey['embed_code'] . "\n";
    $output .= "Embed Code Encoded: " . htmlspecialchars($survey['embed_code']) . "\n";
} else {
    $output .= "No surveys found.\n";
}

file_put_contents('debug_log.txt', $output);
echo "Done.";
?>
