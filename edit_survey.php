<?php
require 'config.php';
require 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->execute([$id]);
$survey = $stmt->fetch();

if (!$survey) {
    redirect('dashboard.php');
}

// Fetch questions for custom surveys
$questions = [];
if ($survey['type'] === 'custom') {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY order_index ASC");
    $stmt->execute([$id]);
    $questions = $stmt->fetchAll();
}
?>

<div class="mb-4">
    <h1>Edit Survey: <?php echo htmlspecialchars($survey['title']); ?></h1>
</div>

<div class="card">
    <form id="editSurveyForm" action="actions/edit_survey_action.php" method="POST">
        <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
        
        <div class="form-group">
            <label class="form-label" for="title">Survey Title</label>
            <input type="text" id="title" name="title" class="form-control" required value="<?php echo htmlspecialchars($survey['title']); ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($survey['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Survey Type</label>
            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="type" value="custom" <?php echo ($survey['type'] === 'custom') ? 'checked' : ''; ?> onchange="toggleSurveyType()"> 
                    Custom Builder
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="type" value="embed" <?php echo ($survey['type'] === 'embed') ? 'checked' : ''; ?> onchange="toggleSurveyType()"> 
                    Embed External Form
                </label>
            </div>
        </div>

        <div class="form-group" style="background: #eef2ff; padding: 1rem; border-radius: var(--radius); border: 1px solid #c7d2fe;">
            <label class="form-label">Details & Settings</label>
            <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="limit_one" value="1" <?php echo ($survey['limit_one'] ?? 0) ? 'checked' : ''; ?>> 
                    Limit to 1 Response per Person
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="allow_edit" value="1" <?php echo ($survey['allow_edit'] ?? 0) ? 'checked' : ''; ?>> 
                    Allow Respondents to Edit Answers
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="collect_email" value="1" <?php echo ($survey['collect_email'] ?? 0) ? 'checked' : ''; ?>> 
                    Collect Email Addresses
                </label>
            </div>
        </div>

        <!-- Custom Builder Section -->
        <div id="customBuilder" style="<?php echo ($survey['type'] === 'custom') ? 'display: block;' : 'display: none;'; ?>">
            <h3>Questions</h3>
            <div id="questionsContainer">
                <?php $i = 0; foreach ($questions as $q): ?>
                    <div class="question-item">
                        <button type="button" class="remove-question-btn" onclick="removeQuestion(this)"><i class="fas fa-times"></i></button>
                        
                        <!-- Hidden ID to track existing updates -->
                        <input type="hidden" name="questions[<?php echo $i; ?>][id]" value="<?php echo $q['id']; ?>">

                        <div class="form-group">
                            <label class="form-label">Question Text</label>
                            <input type="text" name="questions[<?php echo $i; ?>][text]" class="form-control" required value="<?php echo htmlspecialchars($q['question_text']); ?>">
                            <label style="display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.875rem; cursor: pointer;">
                                <input type="checkbox" name="questions[<?php echo $i; ?>][required]" value="1" <?php echo ($q['is_required'] ?? 0) ? 'checked' : ''; ?>> Required
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="questions[<?php echo $i; ?>][type]" class="form-control" onchange="toggleOptions(this)">
                                <option value="short_answer" <?php echo ($q['question_type'] === 'short_answer') ? 'selected' : ''; ?>>Short Answer</option>
                                <option value="paragraph" <?php echo ($q['question_type'] === 'paragraph') ? 'selected' : ''; ?>>Paragraph</option>
                                <option value="multiple_choice" <?php echo ($q['question_type'] === 'multiple_choice') ? 'selected' : ''; ?>>Multiple Choice</option>
                                <option value="checkbox" <?php echo ($q['question_type'] === 'checkbox') ? 'selected' : ''; ?>>Checkbox</option>
                            </select>
                        </div>

                        <?php 
                            $showOptions = ($q['question_type'] === 'multiple_choice' || $q['question_type'] === 'checkbox');
                            $optStr = $q['options'] ? implode(', ', json_decode($q['options'])) : '';
                        ?>
                        <div class="options-container" style="<?php echo $showOptions ? 'display: block;' : 'display: none;'; ?>">
                            <label class="form-label">Options (comma separated)</label>
                            <input type="text" name="questions[<?php echo $i; ?>][options]" class="form-control" value="<?php echo htmlspecialchars($optStr); ?>" <?php echo $showOptions ? 'required' : ''; ?>>
                        </div>
                    </div>
                <?php $i++; endforeach; ?>
            </div>
            
            <button type="button" class="btn" style="background: #e2e8f0; color: var(--text-main); margin-top: 1rem;" onclick="addQuestion()">
                <i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Add Question
            </button>
        </div>

        <!-- Embed Section -->
        <div id="embedBuilder" style="<?php echo ($survey['type'] === 'embed') ? 'display: block;' : 'display: none;'; ?>">
            <div class="form-group">
                <label class="form-label" for="embed_code">Embed Code</label>
                <textarea id="embed_code" name="embed_code" class="form-control" rows="5"><?php echo htmlspecialchars($survey['embed_code']); ?></textarea>
            </div>
        </div>

        <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Save Changes</button>
            <a href="dashboard.php" class="btn" style="margin-left: 1rem; color: var(--text-muted);">Cancel</a>
        </div>
    </form>
</div>

<!-- Template for new question (hidden) -->
<template id="questionTemplate">
    <div class="question-item">
        <button type="button" class="remove-question-btn" onclick="removeQuestion(this)"><i class="fas fa-times"></i></button>
        <div class="form-group">
            <label class="form-label">Question Text</label>
            <input type="text" name="questions[{index}][text]" class="form-control" required placeholder="Enter your question">
        </div>
        
        <div class="form-group">
            <label class="form-label">Type</label>
            <select name="questions[{index}][type]" class="form-control" onchange="toggleOptions(this)">
                <option value="short_answer">Short Answer</option>
                <option value="paragraph">Paragraph</option>
                <option value="multiple_choice">Multiple Choice</option>
                <option value="checkbox">Checkbox</option>
            </select>
        </div>

        <div class="options-container" style="display: none;">
            <label class="form-label">Options (comma separated)</label>
            <input type="text" name="questions[{index}][options]" class="form-control" placeholder="Option 1, Option 2, Option 3">
        </div>
    </div>
</template>

<script>
    // Initialize question count based on PHP loop
    var questionCount = <?php echo count($questions); ?>;
</script>
<script src="assets/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
