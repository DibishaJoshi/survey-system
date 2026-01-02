<?php
require 'config.php';
require 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$id]);
$survey = $stmt->fetch();

if (!$survey) {
    die("Survey not found.");
}

$cookie_name = 'survey_submitted_' . $id;
$previous_token = isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null;
$url_token = isset($_GET['token']) ? $_GET['token'] : null;

// Determine if editing
$edit_mode = false;
$response_id = null;
$existing_answers = [];

// If URL has token and editing allowed, try to load response
if ($url_token && ($survey['allow_edit'] ?? 0)) {
    $stmt = $pdo->prepare("SELECT id FROM responses WHERE survey_id = ? AND token = ?");
    $stmt->execute([$id, $url_token]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $edit_mode = true;
        $response_id = $existing['id'];
        $previous_token = $url_token;
    }
}
// If limit one is on, existing cookie found, and NOT in valid edit mode -> Block
elseif (($survey['limit_one'] ?? 0) && $previous_token && !($survey['allow_edit'] ?? 0)) {
    die("You have already submitted this survey.");
}
// If limit one is on, existing cookie found, and editing IS allowed -> Redirect to edit if not already there
elseif (($survey['limit_one'] ?? 0) && $previous_token && ($survey['allow_edit'] ?? 0) && !$url_token) {
    redirect("view_survey.php?id=$id&token=$previous_token");
}

// Pre-fill answer for email if exists
$respondent_email = '';
if ($edit_mode && $response_id) {
    $stmtE = $pdo->prepare("SELECT respondent_email FROM responses WHERE id = ?");
    $stmtE->execute([$response_id]);
    $respondent_email = $stmtE->fetchColumn();
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $token = $edit_mode ? $previous_token : generateToken();
        
        if ($edit_mode) {
            // Update existing response timestamp and email
            $respondent_email = isset($_POST['respondent_email']) ? clean_input($_POST['respondent_email']) : null;
            $stmt = $pdo->prepare("UPDATE responses SET submitted_at = CURRENT_TIMESTAMP, respondent_email = ? WHERE id = ?");
            $stmt->execute([$respondent_email, $response_id]);
            
            // Delete old answers
            $stmt = $pdo->prepare("DELETE FROM answers WHERE response_id = ?");
            $stmt->execute([$response_id]);
        } else {
            // New response
            $respondent_email = isset($_POST['respondent_email']) ? clean_input($_POST['respondent_email']) : null;
            $stmt = $pdo->prepare("INSERT INTO responses (survey_id, token, respondent_email) VALUES (?, ?, ?)");
            $stmt->execute([$id, $token, $respondent_email]);
            $response_id = $pdo->lastInsertId();
        }

        if (isset($_POST['answers'])) {
            $stmtA = $pdo->prepare("INSERT INTO answers (response_id, question_id, answer_text) VALUES (?, ?, ?)");
            foreach ($_POST['answers'] as $q_id => $ans) {
                $answer_text = is_array($ans) ? implode(', ', $ans) : $ans; // Handle checkboxes
                $stmtA->execute([$response_id, $q_id, clean_input($answer_text)]);
            }
        }
        
        $pdo->commit();
        $success = true;
        
        // Set cookie for 1 year
        setcookie($cookie_name, $token, time() + (86400 * 365), "/");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "An error occurred while submitting your response.";
    }
}

// Fetch questions if custom
$questions = [];
if ($survey['type'] === 'custom') {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_index ASC");
    $stmt->execute([$id]);
    $questions = $stmt->fetchAll();
}

// Pre-fill answers if editing
if ($edit_mode) {
    $stmt = $pdo->prepare("SELECT question_id, answer_text FROM answers WHERE response_id = ?");
    $stmt->execute([$response_id]);
    $existing_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($existing_rows as $row) {
        $existing_answers[$row['question_id']] = $row['answer_text'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($survey['title']); ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body style="background: #f1f5f9; min-height: 100vh; padding: 2rem 0;">

<div class="container" style="max-width: 800px;">
    <?php if ($success): ?>
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success); margin-bottom: 1rem;"></i>
            <h2>Thank You!</h2>
            <p>Your response has been recorded successfully.</p>
            
            <?php if ($survey['allow_edit'] ?? 0): ?>
                <p style="margin-top: 1rem; color: var(--text-muted);">
                    You can edit your response anytime using this link:<br>
                    <a href="?id=<?php echo $id; ?>&token=<?php echo $token; ?>">Edit Response</a>
                </p>
            <?php elseif (!($survey['limit_one'] ?? 0)): ?>
                <a href="view_survey.php?id=<?php echo $id; ?>" class="btn btn-primary" style="margin-top: 1rem;">Submit Another Response</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h1 style="text-align: center; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($survey['title']); ?></h1>
            <?php if ($survey['description']): ?>
                <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;"><?php echo nl2br(htmlspecialchars($survey['description'])); ?></p>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: var(--radius); margin-bottom: 1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($survey['type'] === 'embed'): ?>
                <div class="survey-embed-wrapper">
                    <?php 
                        $code = $survey['embed_code'];
                        // Decode just in case it was stored with entities
                        $code = html_entity_decode($code);
                        
                        if (!empty($code)) {
                            echo $code; 
                        } else {
                            echo '<p class="text-muted" style="text-align:center; padding: 2rem;">No embed code configured for this survey.</p>';
                        }
                    ?>
                </div>

                <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 2rem;">
                    <form method="POST" action="">
                        <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">Confirm Completion</h3>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                            After submitting the form above, please confirm your submission here to track your participation.
                        </p>

                        <?php if ($survey['collect_email'] ?? 0): ?>
                            <div class="form-group">
                                <label class="form-label">
                                    Your Email Address <span style="color: red;">*</span>
                                </label>
                                <input type="email" name="respondent_email" class="form-control" value="<?php echo htmlspecialchars($respondent_email ?? ''); ?>" required>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                            <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> I have submitted the form
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <?php if ($survey['collect_email'] ?? 0): ?>
                        <div class="form-group" style="background: #f8fafc; padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border);">
                            <label class="form-label" style="font-size: 1.1rem; margin-bottom: 1rem;">
                                Email Address <span style="color: red;">*</span>
                            </label>
                            <input type="email" name="respondent_email" class="form-control" value="<?php echo htmlspecialchars($respondent_email ?? ''); ?>" required>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($questions as $q): ?>
                        <div class="form-group" style="background: #f8fafc; padding: 1.5rem; border-radius: var(--radius); border: 1px solid var(--border);">
                            <label class="form-label" style="font-size: 1.1rem; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($q['question_text']); ?>
                                <?php if ($q['is_required'] ?? 0): ?><span style="color: red;">*</span><?php endif; ?>
                            </label>

                            <?php 
                                $val = isset($existing_answers[$q['id']]) ? $existing_answers[$q['id']] : '';
                            ?>

                            <?php if ($q['question_type'] === 'short_answer'): ?>
                                <input type="text" name="answers[<?php echo $q['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($val); ?>" <?php echo ($q['is_required'] ?? 0) ? 'required' : ''; ?>>
                            
                            <?php elseif ($q['question_type'] === 'paragraph'): ?>
                                <textarea name="answers[<?php echo $q['id']; ?>]" class="form-control" rows="3" <?php echo ($q['is_required'] ?? 0) ? 'required' : ''; ?>><?php echo htmlspecialchars($val); ?></textarea>
                            
                            <?php elseif ($q['question_type'] === 'multiple_choice'): ?>
                                <?php $options = json_decode($q['options']); ?>
                                <?php foreach ($options as $opt): ?>
                                    <label style="display: block; margin-bottom: 0.5rem; cursor: pointer;">
                                        <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($val === $opt) ? 'checked' : ''; ?> <?php echo ($q['is_required'] ?? 0) ? 'required' : ''; ?>>
                                        <?php echo htmlspecialchars($opt); ?>
                                    </label>
                                <?php endforeach; ?>
                            
                            <?php elseif ($q['question_type'] === 'checkbox'): ?>
                                <?php 
                                    $options = json_decode($q['options']); 
                                    $vals = explode(', ', $val);
                                ?>
                                <?php foreach ($options as $opt): ?>
                                    <label style="display: block; margin-bottom: 0.5rem; cursor: pointer;">
                                        <input type="checkbox" name="answers[<?php echo $q['id']; ?>][]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo in_array($opt, $vals) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($opt); ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1rem;">Submit</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
