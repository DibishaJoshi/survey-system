<?php
require 'config.php';
require 'functions.php';

// Check if ID (legacy) or Hash (new) is provided
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hash = isset($_GET['hash']) ? rtrim(clean_input($_GET['hash']), '/') : null;

if ($hash) {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE hash_id = ?");
    $stmt->execute([$hash]);
    $survey = $stmt->fetch();
    
    if ($survey) {
        $id = $survey['id'];
    }
} elseif ($id) {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$id]);
    $survey = $stmt->fetch();
} else {
    $survey = null;
}

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
    redirect("/survery_system/view_survey.php?id=$id&token=$previous_token");
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
    // Validate email if required
    if (($survey['collect_email'] ?? 0) && empty($_POST['respondent_email'])) {
        $error = "Email address is required.";
    } else {
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
    <!-- Use base href or absolute path for assets -->
    <base href="/survery_system/">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; }
    </style>
</head>
<body class="respondent-body">

<div class="survey-container">
    <?php if ($success): ?>
        <div class="survey-header-card" style="text-align: center; border-top-color: var(--success);">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success); margin-bottom: 1rem;"></i>
            <h1 class="survey-title-large">Thank You!</h1>
            <p>Your response has been recorded.</p>
            
            <?php if ($survey['allow_edit'] ?? 0): ?>
                <p style="margin-top: 24px;">
                    <a href="/survery_system/view_survey.php?id=<?php echo $id; ?>&token=<?php echo $token; ?>" style="color: var(--primary); text-decoration: none; font-weight: 500;">Edit your response</a>
                </p>
            <?php elseif (!($survey['limit_one'] ?? 0)): ?>
                <p style="margin-top: 24px;">
                    <a href="/survery_system/view_survey.php?id=<?php echo $id; ?>" style="color: var(--primary); text-decoration: none; font-weight: 500;">Submit another response</a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <form method="POST" action="">
            <!-- Header Card -->
            <div class="survey-header-card">
                <h1 class="survey-title-large"><?php echo htmlspecialchars($survey['title']); ?></h1>
                <?php if ($survey['description']): ?>
                    <div style="font-size: 11pt; color: #202124; margin-bottom: 12px; line-height: 1.5; white-space: pre-wrap;"><?php echo htmlspecialchars($survey['description']); ?></div>
                <?php endif; ?>
                
                <hr style="border: 0; border-top: 1px solid #dadce0; margin: 16px 0;">
                
                <!-- Email Status Indicator -->
                <div style="font-size: 0.9rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 0.75rem; border-radius: 8px; margin-top: 1rem;">
                    <?php if ($survey['collect_email'] ?? 0): ?>
                        <span><i class="fas fa-info-circle"></i> <b style="color: var(--text-main);">Sign in</b> to save your progress.</span> 
                        <span style="color: var(--danger); font-size: 0.9em;">* Required</span>
                    <?php else: ?>
                        <span><i class="fas fa-user-shield"></i> Anonymous Survey</span>
                        <span style="color: var(--danger); font-size: 0.9em;">* Required</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="question-card" style="border-left: 4px solid var(--danger);">
                    <div style="color: var(--danger); font-weight: 500;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Embed Mode (Legacy Support) -->
            <?php if ($survey['type'] === 'embed'): ?>
                <div class="card" style="margin-bottom: 24px;">
                    <div class="survey-embed-wrapper">
                        <?php 
                            $code = html_entity_decode($survey['embed_code']);
                            echo !empty($code) ? $code : '<p class="text-muted text-center pt-3">No embed content.</p>'; 
                        ?>
                    </div>
                </div>
                
                <div class="question-card">
                    <h3 class="question-text-label">Confirmation</h3>
                    <p style="margin-bottom: 16px; font-size: 14px;">Please confirm you have finished the activity above.</p>
                    
                    <?php if ($survey['collect_email'] ?? 0): ?>
                        <div style="margin-bottom: 16px;">
                            <label class="question-text-label">Your Email <span class="required-star">*</span></label>
                            <input type="email" name="respondent_email" class="form-control-google" placeholder="Your email" value="<?php echo htmlspecialchars($respondent_email ?? ''); ?>" required>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary); color: white; padding: 10px 24px; font-size: 14px; border-radius: 4px; border: none; font-weight: 500;">
                        Mark as Done
                    </button>
                </div>

            <?php else: ?>
                <!-- Clean Form Questions -->
                
                <!-- Email Collection Field (First Question) -->
                <?php if ($survey['collect_email'] ?? 0): ?>
                    <div class="question-card">
                        <label class="question-text-label">Email <span class="required-star">*</span></label>
                        <input type="email" name="respondent_email" class="form-control-google" placeholder="Your email" value="<?php echo htmlspecialchars($respondent_email ?? ''); ?>" required>
                        <div style="font-size: 12px; color: #5f6368; margin-top: 8px;">
                            This form is collecting emails. <a href="#" style="color: var(--primary);">Change settings</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($questions as $q): ?>
                    <?php $val = isset($existing_answers[$q['id']]) ? $existing_answers[$q['id']] : ''; ?>
                    
                    <div class="question-card">
                        <label class="question-text-label">
                            <?php echo htmlspecialchars($q['question_text']); ?>
                            <?php if ($q['is_required'] ?? 0): ?><span class="required-star">*</span><?php endif; ?>
                        </label>
                        
                        <?php if ($q['question_type'] === 'short_answer'): ?>
                            <input type="text" name="answers[<?php echo $q['id']; ?>]" class="form-control-google" placeholder="Your answer" value="<?php echo htmlspecialchars($val); ?>" <?php echo ($q['is_required'] ?? 0) ? 'required' : ''; ?>>

                        <?php elseif ($q['question_type'] === 'paragraph'): ?>
                            <textarea name="answers[<?php echo $q['id']; ?>]" class="form-control-google" rows="4" placeholder="Your answer" style="resize: vertical; border: 1px solid #e0e0e0; background: white;" <?php echo ($q['is_required'] ?? 0) ? 'required' : ''; ?>><?php echo htmlspecialchars($val); ?></textarea>

                        <?php elseif ($q['question_type'] === 'multiple_choice'): ?>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <?php $options = json_decode($q['options']); ?>
                                <?php foreach ($options as $opt): ?>
                                    <label class="option-label">
                                        <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($val === $opt) ? 'checked' : ''; ?> <?php echo ($q['is_required'] ?? 0) ? 'required' : ''; ?>>
                                        <span><?php echo htmlspecialchars($opt); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($q['question_type'] === 'checkbox'): ?>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <?php 
                                    $options = json_decode($q['options']); 
                                    $vals = explode(', ', $val);
                                ?>
                                <?php foreach ($options as $opt): ?>
                                    <label class="option-label">
                                        <input type="checkbox" name="answers[<?php echo $q['id']; ?>][]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo in_array($opt, $vals) ? 'checked' : ''; ?>>
                                        <span><?php echo htmlspecialchars($opt); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 32px;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 1rem; font-weight: 600;">
                        Submit Response
                    </button>
                    
                    <a href="#" style="font-size: 0.9rem; color: var(--text-muted); text-decoration: none;">Clear form</a>
                </div>

                <div style="text-align: center; margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--border); color: var(--text-muted);">
                    <p style="font-size: 0.85rem; margin-bottom: 0.5rem;">Never submit passwords through this form.</p>
                    <div style="font-weight: 600; font-size: 1.1rem; color: var(--primary);">SurveyAdmin</div>
                </div>

            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
