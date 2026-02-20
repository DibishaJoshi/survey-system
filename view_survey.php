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
}
elseif ($id) {
    $stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->execute([$id]);
    $survey = $stmt->fetch();
}
else {
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
    $already_submitted = true;
}
// If limit one is on, existing cookie found, and editing IS allowed -> Redirect to edit if not already there
elseif (($survey['limit_one'] ?? 0) && $previous_token && ($survey['allow_edit'] ?? 0) && !$url_token) {
    redirect(base_url("view_survey.php?id=$id&token=$previous_token"));
}

// Pre-fill answer for email if exists
$respondent_email = '';
if ($edit_mode && $response_id) {
    $stmtE = $pdo->prepare("SELECT respondent_email FROM responses WHERE id = ?");
    $stmtE->execute([$response_id]);
    $respondent_email = $stmtE->fetchColumn();
}

$success = false;
$already_submitted = $already_submitted ?? false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate email if required
    if (($survey['collect_email'] ?? 0) && empty($_POST['respondent_email'])) {
        $error = "Email address is required.";
    }
    else {
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
            }
            else {
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

        }
        catch (Exception $e) {
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
    <base href="<?php echo base_url('/'); ?>/">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Roboto', sans-serif; 
            background-color: #f0ebf8;
            min-height: 100vh;
            padding: 3rem 1.5rem;
            margin: 0;
            color: #202124;
            font-size: 1.125rem; /* Clean large base */
            line-height: 1.6;
        }
        
        .survey-wrapper {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .survey-card {
            background: white;
            border-radius: 8px;
            padding: 2rem 2.5rem; /* Increased padding */
            margin-bottom: 1rem; /* More space between cards */
            box-shadow: 0 1px 2px 0 rgba(60,64,67,.3), 0 1px 3px 1px rgba(60,64,67,.15);
            border: 1px solid #dadce0;
        }
        
        .header-card {
            border-top: 10px solid #673ab7;
            padding: 1.5rem 2rem 2rem 2rem;
        }
        
        .survey-title {
            font-size: 2.25rem;
            font-weight: 420;
            color: #202124;
            margin: 0 0 1rem 0;
            line-height: 1.2;
        }
        
        .survey-description {
            font-size: 1.02rem; /* Massive description */
            color: #202124;
            line-height: 1.7;
            margin: 0;
            white-space: pre-wrap;
        }
        
        .question-card {
            padding: 1.5rem 2rem;
            transition: box-shadow 0.2s ease;
        }
        
        .question-label {
            font-size: 1.25rem;
            font-weight: 500;
            color: #202124;
            margin-bottom: 1.5rem;
            display: block;
            word-wrap: break-word;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 0; /* Increased */
            border: none;
            border-bottom: 1px solid #dadce0;
            font-size: 1.125rem; /* Confirmed larger */
            font-family: inherit;
            transition: border-bottom 0.2s ease;
            background: transparent;
        }
        
        .form-input:focus {
            outline: none;
            border-bottom: 2px solid #673ab7;
        }
        
        textarea.form-input {
            resize: none;
            min-height: 24px;
            line-height: 1.5;
            overflow-y: hidden;
        }
        
        .options-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .option-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
        }
        
        .option-item input[type="radio"],
        .option-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #673ab7;
        }
        
        .option-item label {
            cursor: pointer;
            font-size: 1.125rem; /* Larger font */
            color: #202124;
        }
        
        .btn-submit {
            background-color: #673ab7;
            color: white;
            border: none;
            padding: 0.6rem 1.75rem; /* Increased padding */
            font-size: 1rem; /* Larger font */
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .btn-submit:hover {
            background-color: #512da8;
        }
        
        .btn-clear {
            background: none;
            border: none;
            color: #673ab7;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        
        .btn-clear:hover {
            background: rgba(103, 58, 183, 0.04);
        }
        
        .success-card {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: scaleIn 0.5s ease;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .success-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 0.75rem 0;
        }
        
        .success-message {
            font-size: 1.125rem;
            color: #5f6368;
            margin: 0 0 2rem 0;
        }
        
        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-link:hover {
            background: #f0f1ff;
        }
        
        .error-banner {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
            padding: 1rem 1.25rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        
        .rating-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 0;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .rating-range-label {
            font-size: 1rem;
            color: #70757a;
            margin-top: 2rem; /* Align horizontally with the radio buttons */
            white-space: nowrap;
        }
        
        .rating-scale {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-grow: 1;
        }
        
        .rating-point {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            min-width: 40px;
        }
        
        .rating-point span {
            font-size: 0.875rem;
            color: #202124;
            font-weight: 500;
        }
        
        .rating-point input[type="radio"] {
            width: 20px;
            height: 20px;
            margin: 0;
            cursor: pointer;
            accent-color: #673ab7;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1.5rem;
            color: rgba(122, 116, 116, 0.9);
        }
        
        .footer-text {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .footer-brand {
            font-size: 1.125rem;
            font-weight: 700;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem 0.5rem;
            }
            
            .survey-card {
                padding: 1.5rem;
            }
            
            .header-card {
                padding: 2rem 1.5rem;
            }
            
            .survey-title {
                font-size: 1.5rem;
            }
            
            .rating-container {
                gap: 1rem;
                justify-content: space-between;
            }
            
            .rating-scale {
                gap: 0.25rem;
            }

            .rating-point {
                min-width: 30px;
            }
            
            .action-bar {
                flex-direction: column-reverse;
                gap: 1rem;
            }
            
            .btn-submit {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="survey-wrapper">
    <?php if ($success): ?>
        <div class="survey-card header-card">
            <h1 class="survey-title"><?php echo htmlspecialchars($survey['title']); ?></h1>
            <p style="font-size: 0.875rem; margin-top: 1rem;">Your response has been recorded.</p>
            
            <div style="margin-top: 1.5rem;">
                <?php if ($survey['allow_edit'] ?? 0): ?>
                    <a href="view_survey.php?id=<?php echo $id; ?>&token=<?php echo $token; ?>" class="btn-link">Edit your response</a><br>
                <?php
    endif; ?>
                <a href="view_survey.php?id=<?php echo $id; ?>" class="btn-link">Submit another response</a>
            </div>
        </div>

    <?php
elseif ($already_submitted): ?>
        <div class="survey-card header-card">
            <h1 class="survey-title"><?php echo htmlspecialchars($survey['title']); ?></h1>
            <p style="font-size: 0.875rem; margin-top: 1rem;">You've already responded</p>
            <p style="font-size: 0.875rem; color: var(--text-secondary);">You can only fill out this form once.</p>
            
            <div style="margin-top: 1.5rem;">
                <?php if ($survey['allow_edit'] ?? 0): ?>
                    <a href="view_survey.php?id=<?php echo $id; ?>&token=<?php echo $previous_token; ?>" class="btn-link">Edit your response</a>
                <?php
    endif; ?>
            </div>
        </div>

    <?php
else: ?>
        <form id="surveyForm" method="POST" action="">
            <!-- Header Card -->
            <div class="survey-card header-card">
                <h1 class="survey-title"><?php echo htmlspecialchars($survey['title']); ?></h1>
                <?php if (!empty($survey['description'])): ?>
                    <p class="survey-description"><?php echo htmlspecialchars($survey['description']); ?></p>
                <?php
    endif; ?>
                
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 0.875rem; color: var(--text-secondary);">
                    <?php if ($survey['collect_email'] ?? 0): ?>
                        <div style="color: #d93025; font-weight: 500;">* Indicates required question</div>
                    <?php
    else: ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Anonymous Survey</span>
                        </div>
                    <?php
    endif; ?>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="survey-card" style="border-left: 5px solid #d93025; color: #d93025;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php
    endif; ?>

            <?php if ($survey['type'] === 'embed'): ?>
                <div class="survey-card">
                    <div style="width: 100%; overflow: hidden;">
                        <?php echo html_entity_decode($survey['embed_code']); ?>
                    </div>
                </div>
                
                <div class="survey-card question-card">
                    <span class="question-text">Confirm Completion <span class="required-star">*</span></span>
                    <?php if ($survey['collect_email'] ?? 0): ?>
                        <input type="email" name="respondent_email" class="form-input" placeholder="Your email" value="<?php echo htmlspecialchars($respondent_email); ?>" required>
                    <?php
        endif; ?>
                    <div style="margin-top: 1rem;">
                        <button type="submit" class="btn-submit">Submit</button>
                    </div>
                </div>

            <?php
    else: ?>
                <!-- Email Collection Card -->
                <?php if (($survey['collect_email'] ?? 0) && !$respondent_email): ?>
                    <div class="survey-card question-card">
                        <span class="question-text">Email Address <span class="required-star">*</span></span>
                        <input type="email" name="respondent_email" class="form-input" placeholder="Your email" required>
                    </div>
                <?php
        endif; ?>

                <!-- Questions -->
                <?php foreach ($questions as $q): ?>
                    <?php $ans = $existing_answers[$q['id']] ?? ''; ?>
                    <div class="survey-card question-card">
                        <span class="question-text">
                            <?php echo htmlspecialchars($q['question_text']); ?>
                            <?php if ($q['is_required'] ?? 0): ?><span class="required-star">*</span><?php
            endif; ?>
                        </span>

                        <div style="margin-top: 1.5rem;">
                            <?php switch ($q['question_type']):
                case 'short_answer': ?>
                                    <input type="text" name="answers[<?php echo $q['id']; ?>]" class="form-input" placeholder="Your answer" value="<?php echo htmlspecialchars($ans); ?>" <?php echo($q['is_required'] ?? 0) ? 'required' : ''; ?>>
                                <?php break;
                case 'paragraph': ?>
                                    <textarea name="answers[<?php echo $q['id']; ?>]" class="form-input" placeholder="Your answer" rows="1" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'" <?php echo($q['is_required'] ?? 0) ? 'required' : ''; ?>><?php echo htmlspecialchars($ans); ?></textarea>
                                <?php break;
                case 'multiple_choice': ?>
                                    <div class="options-group">
                                        <?php foreach (json_decode($q['options']) as $opt): ?>
                                            <div class="option-item" onclick="this.querySelector('input').click()">
                                                <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo($ans === $opt) ? 'checked' : ''; ?> <?php echo($q['is_required'] ?? 0) ? 'required' : ''; ?>>
                                                <label><?php echo htmlspecialchars($opt); ?></label>
                                            </div>
                                        <?php
                    endforeach; ?>
                                    </div>
                                <?php break;
                case 'checkbox':
                    $checked_opts = explode(', ', $ans); ?>
                                    <div class="options-group">
                                        <?php foreach (json_decode($q['options']) as $opt): ?>
                                            <div class="option-item" onclick="this.querySelector('input').click()">
                                                <input type="checkbox" name="answers[<?php echo $q['id']; ?>][]" value="<?php echo htmlspecialchars($opt); ?>" <?php echo in_array($opt, $checked_opts) ? 'checked' : ''; ?>>
                                                <label><?php echo htmlspecialchars($opt); ?></label>
                                            </div>
                                        <?php
                    endforeach; ?>
                                    </div>
                                <?php break;
                case 'dropdown': ?>
                                    <select name="answers[<?php echo $q['id']; ?>]" class="form-input" <?php echo($q['is_required'] ?? 0) ? 'required' : ''; ?>>
                                        <option value="" disabled <?php echo empty($ans) ? 'selected' : ''; ?>>Choose</option>
                                        <?php foreach (json_decode($q['options']) as $opt): ?>
                                            <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo($ans === $opt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option>
                                        <?php
                    endforeach; ?>
                                    </select>
                                <?php break;
                case 'rating': ?>
                                    <div class="rating-container">
                                        <span class="rating-range-label">Poor</span>
                                        <div class="rating-scale">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <label class="rating-point">
                                                    <span><?php echo $i; ?></span>
                                                    <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="<?php echo $i; ?>" <?php echo($ans == $i) ? 'checked' : ''; ?> <?php echo($q['is_required'] ?? 0) ? 'required' : ''; ?>>
                                                </label>
                                            <?php
                    endfor; ?>
                                        </div>
                                        <span class="rating-range-label">Excellent</span>
                                    </div>
                                <?php break;
            endswitch; ?>
                        </div>
                    </div>
                <?php
        endforeach; ?>


                <?php if (empty($questions)): ?>
                    <div class="survey-card" style="text-align: center; color: var(--text-secondary);">
                        <i class="fa-solid fa-clipboard-list" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                        <p>This survey is currently empty.</p>
                    </div>
                <?php
        else: ?>
                    <div class="action-bar">
                        <button type="submit" class="btn-submit">Submit</button>
                        <button type="button" class="btn-clear" onclick="if(confirm('Clear form?')) document.getElementById('surveyForm').reset()">Clear form</button>
                    </div>
                <?php
        endif; ?>
            <?php
    endif; ?>
        </form>
        
        <div class="footer">
            <p>Never submit passwords through SurveyAdmin.</p>
            <div style="font-size: 1.25rem; color: #5f6368; margin-top: 1rem;">SurveyAdmin</div>
         </div>
    <?php
endif; ?>
</div>

<script>
// Auto-resize textareas
document.querySelectorAll('textarea.form-input').forEach(el => {
    el.style.height = el.scrollHeight + 'px';
});
</script>
</body>
</html>

