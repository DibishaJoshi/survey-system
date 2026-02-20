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

<style>
* {
    box-sizing: border-box;
}

.survey-builder-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
}

.header-card {
    background: white;
    border-radius: 12px;
    padding: 2.5rem; /* Increased padding */
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-top: 8px solid #667eea; /* Slightly thicker */
}

.header-card input,
.header-card textarea {
    background: transparent;
    border: none;
    border-bottom: 1px solid transparent;
    padding: 0.875rem 0; /* Increased */
    width: 100%;
    font-size: 1.12rem; /* Larger */
    transition: all 0.3s ease;
    color: #374151;
}

.header-card input:focus,
.header-card textarea:focus {
    outline: none;
    border-bottom-color: #667eea;
}

.header-card input::placeholder,
.header-card textarea::placeholder {
    color: #9ca3af;
}

.header-card input {
    font-size: 2.5rem; /* High-impact title */
    font-weight: 700;
    margin-bottom: 1.5rem;
    border-bottom: 3px solid #e5e7eb;
}

.header-card textarea {
    font-size: 1.25rem; /* Larger description */
    resize: none;
    border-bottom: 1px solid #e5e7eb;
}

.question-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
    animation: slideIn 0.4s ease;
}

.question-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-left-color: #667eea;
}

.question-card.focused {
    border-left-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.question-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.question-input {
    flex: 1;
    border: none;
    border-bottom: 2px solid #e5e7eb;
    padding: 0.75rem 0;
    font-size: 1.2rem; /* Significantly larger */
    /* font-weight: 600; */
    transition: border-color 0.3s ease;
    color: #111827;
}

.question-input:focus {
    outline: none;
    border-bottom-color: #667eea;
}

.question-input::placeholder {
    color: #9ca3af;
}

.question-controls {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.icon-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: #f3f4f6;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    color: #6b7280;
}

.icon-btn:hover {
    background: #e5e7eb;
    color: #374151;
    transform: scale(1.1);
}

.icon-btn.delete {
    background: #fee2e2;
    color: #dc2626;
}

.icon-btn.delete:hover {
    background: #fecaca;
}

.question-type-select {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.625rem 2.25rem 0.625rem 1rem;
    font-size: 1.125rem; /* Magnified */
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
}

.question-type-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.options-list {
    margin-top: 1rem;
}

.option-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    animation: slideIn 0.3s ease;
}

.option-input {
    flex: 1;
    border: none;
    border-bottom: 2px solid #e5e7eb;
    padding: 0.625rem 0;
    font-size: 1.125rem; /* Magnified */
    transition: border-color 0.3s ease;
    color: #374151;
}

.option-input:focus {
    outline: none;
    border-bottom-color: #667eea;
}

.option-icon {
    color: #9ca3af;
    font-size: 1.25rem;
}

.add-option-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 0;
    border: none;
    background: none;
    color: #4b5563;
    cursor: pointer;
    font-size: 1.125rem; /* Magnified */
    transition: all 0.3s ease;
    font-weight: 500;
}

.add-option-btn:hover {
    color: #667eea;
}

.question-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.125rem; /* Magnified */
    color: #4b5563;
}

.switch {
    position: relative;
    width: 52px; /* Magnified */
    height: 28px;
    background: #e5e7eb;
    border-radius: 14px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.switch.active {
    background: #667eea;
}

.switch::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.switch.active::after {
    transform: translateX(24px);
}

.add-question-btn {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    z-index: 100;
}

.add-question-btn:hover {
    transform: scale(1.1) rotate(90deg);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.settings-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.setting-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.setting-item:hover {
    background: #f3f4f6;
}

.setting-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.2rem; /* Standardized Ultra-Impact */
    color: #374151;
    font-weight: 500;
}

.action-bar {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 2.5rem; /* Larger */
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    font-size: 1.25rem; /* High-impact */
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: white;
    color: #4b5563;
    border: 1px solid #d1d5db;
    padding: 1rem 2rem; /* Larger */
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 1.25rem; /* High-impact */
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

@media (max-width: 768px) {
    .survey-builder-container {
        padding: 1rem 0.5rem;
    }
    
    .add-question-btn {
        bottom: 1rem;
        right: 1rem;
    }
}

.empty-state {
    text-align: center;
    padding: 5rem 2rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-state h3 {
    font-size: 1.75rem;
    margin-bottom: 0.75rem;
    color: #6b7280;
}

.empty-state p {
    font-size: 1.25rem;
    color: #9ca3af;
}
</style>

<div class="survey-builder-container">
    <div class="mb-4" style="display: flex; align-items: center; gap: 1rem; color: var(--text-muted); margin-bottom: 2rem;">
        <a href="dashboard.php" style="color: inherit; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        <span style="opacity: 0.5;">/</span>
        <span style="font-weight: 500;">Edit Survey</span>
    </div>

    <!-- Header Card -->
    <div class="header-card">
        <input type="text" id="title" name="title" placeholder="Untitled Survey" required maxlength="200" value="<?php echo htmlspecialchars($survey['title']); ?>">
        <textarea id="description" name="description" placeholder="Survey description" rows="2" maxlength="500"><?php echo htmlspecialchars($survey['description']); ?></textarea>
    </div>

    <form id="editSurveyForm" action="actions/edit_survey_action.php" method="POST">
        <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
        <input type="hidden" name="title" id="hiddenTitle" value="<?php echo htmlspecialchars($survey['title']); ?>">
        <input type="hidden" name="description" id="hiddenDescription" value="<?php echo htmlspecialchars($survey['description']); ?>">
        
        <div class="settings-card">
            <h3 style="margin-bottom: 1rem; font-size: 1.5rem; color: #374151;">Survey Type </h3>
            
            <div style="margin-bottom: 2rem; display: flex; gap: 3rem;">
                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                    <input type="radio" name="type" value="custom" <?php echo($survey['type'] === 'custom') ? 'checked' : ''; ?> onchange="handleSurveyTypeChange(this)" style="transform: scale(1.5);"> 
                    <span style="font-size: 1.2rem; font-weight: 500;">Custom Builder</span>
                </label>
                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                    <input type="radio" name="type" value="embed" <?php echo($survey['type'] === 'embed') ? 'checked' : ''; ?> onchange="handleSurveyTypeChange(this)" style="transform: scale(1.5);"> 
                    <span style="font-size: 1.2rem; font-weight: 500;">Embed External Form</span>
                </label>
            </div>
<h3 style="margin-bottom: 1rem; font-size: 1.5rem; color: #374151;">Survey Settings</h3>
            
            <div class="settings-grid">
                <div class="setting-item">
                    <label class="setting-label">
                        <i class="fa-solid fa-user-check"></i>
                        <span>One response per person</span>
                    </label>
                    <div class="switch <?php echo($survey['limit_one'] ?? 0) ? 'active' : ''; ?>" data-name="limit_one" onclick="toggleMainSwitch(this)"></div>
                    <input type="hidden" name="limit_one" value="<?php echo($survey['limit_one'] ?? 0); ?>">
                </div>
                <div class="setting-item">
                    <label class="setting-label">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Allow editing responses</span>
                    </label>
                    <div class="switch <?php echo($survey['allow_edit'] ?? 0) ? 'active' : ''; ?>" data-name="allow_edit" onclick="toggleMainSwitch(this)"></div>
                    <input type="hidden" name="allow_edit" value="<?php echo($survey['allow_edit'] ?? 0); ?>">
                </div>
                <div class="setting-item">
                    <label class="setting-label">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Collect email addresses</span>
                    </label>
                    <div class="switch <?php echo($survey['collect_email'] ?? 0) ? 'active' : ''; ?>" data-name="collect_email" onclick="toggleMainSwitch(this)"></div>
                    <input type="hidden" name="collect_email" value="<?php echo($survey['collect_email'] ?? 0); ?>">
                </div>
            </div>
        </div>

        <!-- Embed Section -->
        <div id="embedSection" class="settings-card" style="<?php echo($survey['type'] === 'embed') ? 'display: block;' : 'display: none;'; ?>">
            <h3 style="margin-bottom: 1rem; font-size: 1rem; color: #374151;">Embed Details</h3>
            <textarea id="embed_code" name="embed_code" class="form-control" rows="5" placeholder="Paste your embed code here" style="width: 100%; padding: 1rem; border-radius: 8px; border: 1px solid #e5e7eb; font-family: monospace; font-size: 0.875rem;"><?php echo htmlspecialchars($survey['embed_code'] ?? ''); ?></textarea>
        </div>

        <!-- Questions Container -->
        <div id="questionsContainer" style="<?php echo($survey['type'] === 'custom') ? 'display: block;' : 'display: none;'; ?>">
            <?php if (empty($questions)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <h3>No questions yet</h3>
                    <p>Click the + button to add your first question</p>
                </div>
            <?php
else: ?>
                <?php foreach ($questions as $index => $q): ?>
                    <div class="question-card">
                        <input type="hidden" name="questions[<?php echo $index; ?>][id]" value="<?php echo $q['id']; ?>">
                        <div class="question-header">
                            <input type="text" name="questions[<?php echo $index; ?>][text]" class="question-input" placeholder="Question" required value="<?php echo htmlspecialchars($q['question_text']); ?>">
                            <div class="question-controls">
                                <select name="questions[<?php echo $index; ?>][type]" class="question-type-select" onchange="handleTypeChange(this)">
                                    <option value="short_answer" <?php echo($q['question_type'] === 'short_answer') ? 'selected' : ''; ?>>Short answer</option>
                                    <option value="paragraph" <?php echo($q['question_type'] === 'paragraph') ? 'selected' : ''; ?>>Paragraph</option>
                                    <option value="multiple_choice" <?php echo($q['question_type'] === 'multiple_choice') ? 'selected' : ''; ?>>Multiple choice</option>
                                    <option value="checkbox" <?php echo($q['question_type'] === 'checkbox') ? 'selected' : ''; ?>>Checkboxes</option>
                                    <option value="dropdown" <?php echo($q['question_type'] === 'dropdown') ? 'selected' : ''; ?>>Dropdown</option>
                                    <option value="rating" <?php echo($q['question_type'] === 'rating') ? 'selected' : ''; ?>>Linear scale</option>
                                </select>
                                <button type="button" class="icon-btn delete" onclick="deleteQuestion(this)" title="Delete">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>

                        <?php
        $isChoice = in_array($q['question_type'], ['multiple_choice', 'checkbox', 'dropdown']);
        $options = json_decode($q['options'] ?? '[]', true) ?: [];
?>
                        <div class="options-container" style="<?php echo $isChoice ? 'display: block;' : 'display: none;'; ?>">
                            <div class="options-list">
                                <?php if (empty($options)):
            $options = ['Option 1'];
        endif; ?>
                                <?php foreach ($options as $oIndex => $opt): ?>
                                    <div class="option-item">
                                        <span class="option-icon">
                                            <?php if ($q['question_type'] === 'checkbox'): ?>
                                                <i class="fa-regular fa-square"></i>
                                            <?php
            else: ?>
                                                <i class="fa-regular fa-circle"></i>
                                            <?php
            endif; ?>
                                        </span>
                                        <input type="text" class="option-input" placeholder="Option <?php echo $oIndex + 1; ?>" value="<?php echo htmlspecialchars($opt); ?>">
                                        <?php if ($oIndex > 0): ?>
                                            <button type="button" class="icon-btn delete" style="width: 24px; height: 24px;" onclick="this.parentElement.remove(); updateOptions(this)">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        <?php
            endif; ?>
                                    </div>
                                <?php
        endforeach; ?>
                            </div>
                            <button type="button" class="add-option-btn" onclick="addOption(this)">
                                <i class="fa-solid fa-plus"></i> Add option
                            </button>
                        </div>

                        <textarea name="questions[<?php echo $index; ?>][options]" class="hidden-options" style="display: none;"><?php echo implode("\n", $options); ?></textarea>

                        <div class="question-footer">
                            <div class="toggle-switch">
                                <span>Required</span>
                                <div class="switch <?php echo($q['is_required'] ?? 0) ? 'active' : ''; ?>" onclick="toggleRequired(this)"></div>
                                <input type="hidden" name="questions[<?php echo $index; ?>][required]" value="<?php echo($q['is_required'] ?? 0); ?>">
                            </div>
                        </div>
                    </div>
                <?php
    endforeach; ?>
            <?php
endif; ?>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <button type="button" class="btn-secondary" onclick="window.location.href='dashboard.php'">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>
    </form>

    <!-- Floating Add Button -->
    <button type="button" class="add-question-btn" onclick="addQuestion()" id="floatAddBtn" style="<?php echo($survey['type'] === 'custom') ? 'display: block;' : 'display: none;'; ?>">
        <i class="fa-solid fa-plus"></i>
    </button>
</div>

<template id="questionTemplate">
    <div class="question-card">
        <div class="question-header">
            <input type="text" name="questions[{index}][text]" class="question-input" placeholder="Question" required>
            <div class="question-controls">
                <select name="questions[{index}][type]" class="question-type-select" onchange="handleTypeChange(this)">
                    <option value="short_answer">Short answer</option>
                    <option value="paragraph">Paragraph</option>
                    <option value="multiple_choice">Multiple choice</option>
                    <option value="checkbox">Checkboxes</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="rating">Linear scale</option>
                </select>
                <button type="button" class="icon-btn delete" onclick="deleteQuestion(this)" title="Delete">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        </div>

        <div class="options-container" style="display: none;">
            <div class="options-list">
                <div class="option-item">
                    <span class="option-icon"><i class="fa-regular fa-circle"></i></span>
                    <input type="text" class="option-input" placeholder="Option 1" data-option-index="0">
                </div>
            </div>
            <button type="button" class="add-option-btn" onclick="addOption(this)">
                <i class="fa-solid fa-plus"></i> Add option
            </button>
        </div>

        <textarea name="questions[{index}][options]" class="hidden-options" style="display: none;"></textarea>

        <div class="question-footer">
            <div class="toggle-switch">
                <span>Required</span>
                <div class="switch" onclick="toggleRequired(this)"></div>
                <input type="hidden" name="questions[{index}][required]" value="0">
            </div>
        </div>
    </div>
</template>

<script>
let questionIndex = <?php echo count($questions); ?>;

// Focus effects
document.addEventListener('click', function(e) {
    document.querySelectorAll('.question-card').forEach(card => card.classList.remove('focused'));
    const card = e.target.closest('.question-card');
    if (card) card.classList.add('focused');
});

// Title/Description syncing
document.getElementById('title').addEventListener('input', function() {
    document.getElementById('hiddenTitle').value = this.value;
});
document.getElementById('description').addEventListener('input', function() {
    document.getElementById('hiddenDescription').value = this.value;
});

function handleSurveyTypeChange(radio) {
    const questionsContainer = document.getElementById('questionsContainer');
    const embedSection = document.getElementById('embedSection');
    const floatBtn = document.getElementById('floatAddBtn');
    
    if (radio.value === 'custom') {
        questionsContainer.style.display = 'block';
        embedSection.style.display = 'none';
        floatBtn.style.display = 'block';
    } else {
        questionsContainer.style.display = 'none';
        embedSection.style.display = 'block';
        floatBtn.style.display = 'none';
    }
}

function toggleMainSwitch(element) {
    element.classList.toggle('active');
    const hiddenInput = element.nextElementSibling;
    hiddenInput.value = element.classList.contains('active') ? '1' : '0';
}

function toggleRequired(element) {
    element.classList.toggle('active');
    const hiddenInput = element.nextElementSibling;
    hiddenInput.value = element.classList.contains('active') ? '1' : '0';
}

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const emptyState = container.querySelector('.empty-state');
    if (emptyState) emptyState.remove();
    
    const template = document.getElementById('questionTemplate');
    const clone = template.content.cloneNode(true);
    
    const html = clone.querySelector('.question-card').innerHTML.replace(/{index}/g, questionIndex);
    const newCard = document.createElement('div');
    newCard.className = 'question-card focused';
    newCard.innerHTML = html;
    
    container.appendChild(newCard);
    newCard.querySelector('.question-input').focus();
    
    document.querySelectorAll('.question-card').forEach(card => {
        if (card !== newCard) card.classList.remove('focused');
    });
    
    questionIndex++;
}

function deleteQuestion(btn) {
    const card = btn.closest('.question-card');
    card.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => {
        card.remove();
        if (document.getElementById('questionsContainer').querySelectorAll('.question-card').length === 0) {
            document.getElementById('questionsContainer').innerHTML = `
                <div class="empty-state">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <h3>No questions yet</h3>
                    <p>Click the + button to add your first question</p>
                </div>
            `;
        }
    }, 300);
}

function handleTypeChange(select) {
    const card = select.closest('.question-card');
    const optionsContainer = card.querySelector('.options-container');
    const type = select.value;
    
    if (['multiple_choice', 'checkbox', 'dropdown'].includes(type)) {
        optionsContainer.style.display = 'block';
        const icons = optionsContainer.querySelectorAll('.option-icon i');
        const iconClass = type === 'checkbox' ? 'fa-square' : 'fa-circle';
        icons.forEach(i => {
            i.className = `fa-regular ${iconClass}`;
        });
    } else {
        optionsContainer.style.display = 'none';
    }
    updateOptions(card.querySelector('.question-input'));
}

function addOption(btn) {
    const card = btn.closest('.question-card');
    const optionsList = card.querySelector('.options-list');
    const type = card.querySelector('.question-type-select').value;
    const icon = type === 'checkbox' ? 'fa-square' : 'fa-circle';
    const optionCount = optionsList.children.length;
    
    const newOption = document.createElement('div');
    newOption.className = 'option-item';
    newOption.innerHTML = `
        <span class="option-icon"><i class="fa-regular ${icon}"></i></span>
        <input type="text" class="option-input" placeholder="Option ${optionCount + 1}">
        <button type="button" class="icon-btn delete" style="width: 24px; height: 24px;" onclick="this.parentElement.remove(); updateOptions(this)">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;
    
    optionsList.appendChild(newOption);
    newOption.querySelector('.option-input').focus();
    
    newOption.querySelector('.option-input').addEventListener('input', function() {
        updateOptions(this);
    });
}

function updateOptions(element) {
    const card = element.closest('.question-card');
    const optionInputs = card.querySelectorAll('.option-input');
    const hiddenField = card.querySelector('.hidden-options');
    
    const options = Array.from(optionInputs)
        .map(input => input.value.trim())
        .filter(val => val !== '')
        .join('\n');
    
    hiddenField.value = options;
}

// Add input listeners to existing option inputs
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('option-input')) {
        updateOptions(e.target);
    }
});

const styleSheet = document.createElement('style');
styleSheet.textContent = `
    @keyframes slideOut {
        to { opacity: 0; transform: translateX(100px); }
    }
`;
document.head.appendChild(styleSheet);
</script>
</body>
</html>
