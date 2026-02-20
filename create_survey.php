<?php
require 'config.php';
require 'includes/header.php';
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
    font-size: 1.125rem; /* Larger */
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

.templates-bar {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    gap: 1rem;
    overflow-x: auto;
}

.template-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.75rem 1.25rem; /* Larger chips */
    border: 1px solid #d1d5db;
    border-radius: 50px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s ease;
    font-size: 1.125rem; /* Clearer chip labels */
    /* font-weight: 600; */
}

.template-chip:hover {
    border-color: #667eea;
    background: #f3f4f6;
    transform: translateY(-2px);
}

.template-chip.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
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
    font-size: 1.2rem; /* Significantly larger question text */
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
    width: 52px; /* Magnified switches */
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
    font-size: 1.2rem; /* Standardized to match radio labels */
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
    font-size: 1.25rem; /* High-impact */
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 4rem; /* Standard High-Impact */
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-state h3 {
    font-size: 1.75rem; /* Magnified */
    margin-bottom: 0.75rem;
    color: #6b7280;
}

.empty-state p {
    font-size: 1.25rem;
    color: #9ca3af;
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
</style>

<div class="survey-builder-container">
    <!-- Header Card -->
    <div class="header-card">
        <input type="text" id="title" name="title" placeholder="Untitled Survey" required maxlength="200">
        <textarea id="description" name="description" placeholder="Survey description" rows="2" maxlength="500"></textarea>
    </div>

    <!-- Templates Bar -->
    <div class="templates-bar">
        <div class="template-chip active" data-template="blank">
            <i class="fa-solid fa-file"></i>
            <span>Blank</span>
        </div>
        <div class="template-chip" data-template="customer_satisfaction">
            <i class="fa-solid fa-face-smile"></i>
            <span>Customer Feedback</span>
        </div>
        <div class="template-chip" data-template="event_feedback">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Event Survey</span>
        </div>
        <div class="template-chip" data-template="employee_engagement">
            <i class="fa-solid fa-users"></i>
            <span>Employee Pulse</span>
        </div>
        <div class="template-chip" data-template="contact_info">
            <i class="fa-solid fa-address-book"></i>
            <span>Contact Info</span>
        </div>
        <div class="template-chip" data-template="job_application">
            <i class="fa-solid fa-briefcase"></i>
            <span>Job App</span>
        </div>
        <div class="template-chip" data-template="general_quiz">
            <i class="fa-solid fa-lightbulb"></i>
            <span>Quiz</span>
        </div>
    </div>

    <form id="createSurveyForm" action="actions/save_survey.php" method="POST">
        <input type="hidden" name="title" id="hiddenTitle">
        <input type="hidden" name="description" id="hiddenDescription">
        <input type="hidden" name="type" id="survey_type" value="custom">

        <!-- Settings Card -->
        <div class="settings-card">
            <h3 style="margin-bottom: 1rem; font-size: 1.5rem; color: #374151;">Survey Type & Settings</h3>
            
            <div style="margin-bottom: 2rem; display: flex; gap: 3rem;">
                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                    <input type="radio" name="type_select" value="custom" checked onchange="document.getElementById('survey_type').value = this.value; handleSurveyTypeChange(this)" style="transform: scale(1.5);"> 
                    <span style="font-size: 1.2rem; font-weight: 500;">Custom Builder</span>
                </label>
                <label style="display: flex; align-items: center; gap: 1rem; cursor: pointer;">
                    <input type="radio" name="type_select" value="embed" onchange="document.getElementById('survey_type').value = this.value; handleSurveyTypeChange(this)" style="transform: scale(1.5);"> 
                    <span style="font-size: 1.2rem; font-weight: 500;">Embed External Form</span>
                </label>
            </div>

            <div class="settings-grid">
                <div class="setting-item">
                    <label class="setting-label">
                        <i class="fa-solid fa-user-check"></i>
                        <span>One response per person</span>
                    </label>
                    <div class="switch" data-name="limit_one" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="setting-item">
                    <label class="setting-label">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Allow editing responses</span>
                    </label>
                    <div class="switch" data-name="allow_edit" onclick="toggleSwitch(this)"></div>
                </div>
                <div class="setting-item">
                    <label class="setting-label">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Collect email addresses</span>
                    </label>
                    <div class="switch" data-name="collect_email" onclick="toggleSwitch(this)"></div>
                </div>
            </div>
        </div>

        <!-- Questions Container -->
        <div id="questionsContainer">
            <div class="empty-state">
                <i class="fa-solid fa-clipboard-list"></i>
                <h3>No questions yet</h3>
                <p>Click the + button to add your first question</p>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <button type="button" class="btn-secondary" onclick="window.location.href='dashboard.php'">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Save Survey
            </button>
        </div>
    </form>

    <!-- Floating Add Button -->
    <button type="button" class="add-question-btn" onclick="addQuestion()">
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
                <button type="button" class="icon-btn" onclick="duplicateQuestion(this)" title="Duplicate">
                    <i class="fa-solid fa-copy"></i>
                </button>
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
let questionIndex = 0;

// Template selection
document.querySelectorAll('.template-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        document.querySelectorAll('.template-chip').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        applyTemplate(this.dataset.template);
    });
});

// Focus effects
document.addEventListener('click', function(e) {
    document.querySelectorAll('.question-card').forEach(card => {
        card.classList.remove('focused');
    });
    
    const card = e.target.closest('.question-card');
    if (card) {
        card.classList.add('focused');
    }
});

function toggleSwitch(element) {
    element.classList.toggle('active');
    const name = element.dataset.name;
    
    // Remove existing hidden input
    let input = document.querySelector(`input[name="${name}"]`);
    if (input) input.remove();
    
    // Add new hidden input if active
    if (element.classList.contains('active')) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = '1';
        document.getElementById('createSurveyForm').appendChild(input);
    }
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
    
    questionIndex++;
    const html = clone.querySelector('.question-card').innerHTML.replace(/{index}/g, questionIndex);
    const newCard = document.createElement('div');
    newCard.className = 'question-card focused';
    newCard.innerHTML = html;
    
    container.appendChild(newCard);
    newCard.querySelector('.question-input').focus();
    
    // Remove focus from other cards
    document.querySelectorAll('.question-card').forEach(card => {
        if (card !== newCard) card.classList.remove('focused');
    });
}

function deleteQuestion(btn) {
    const card = btn.closest('.question-card');
    card.style.animation = 'slideOut 0.3s ease';
    
    setTimeout(() => {
        card.remove();
        const container = document.getElementById('questionsContainer');
        if (container.querySelectorAll('.question-card').length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fa-solid fa-clipboard-list" style="font-size: 5rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No questions yet</h3>
                    <p>Click the + button to add your first question</p>
                </div>
            `;
        }
    }, 300);
}

function duplicateQuestion(btn) {
    const card = btn.closest('.question-card');
    const clone = card.cloneNode(true);
    
    questionIndex++;
    clone.innerHTML = clone.innerHTML.replace(/questions\[\d+\]/g, `questions[${questionIndex}]`);
    clone.classList.add('focused');
    
    card.parentNode.insertBefore(clone, card.nextSibling);
    card.classList.remove('focused');
}

function handleTypeChange(select) {
    const card = select.closest('.question-card');
    const optionsContainer = card.querySelector('.options-container');
    const needsOptions = ['multiple_choice', 'checkbox', 'dropdown'].includes(select.value);
    
    if (needsOptions) {
        optionsContainer.style.display = 'block';
        const optionIcon = card.querySelector('.option-icon i');
        if (select.value === 'checkbox') {
            optionIcon.className = 'fa-regular fa-square';
        } else {
            optionIcon.className = 'fa-regular fa-circle';
        }
    } else {
        optionsContainer.style.display = 'none';
    }
}

function addOption(btn) {
    const optionsList = btn.previousElementSibling;
    const optionCount = optionsList.children.length;
    const card = btn.closest('.question-card');
    const select = card.querySelector('.question-type-select');
    const icon = select.value === 'checkbox' ? 'fa-square' : 'fa-circle';
    
    const newOption = document.createElement('div');
    newOption.className = 'option-item';
    newOption.innerHTML = `
        <span class="option-icon"><i class="fa-regular ${icon}"></i></span>
        <input type="text" class="option-input" placeholder="Option ${optionCount + 1}" data-option-index="${optionCount}">
        <button type="button" class="icon-btn delete" style="width: 24px; height: 24px;" onclick="this.parentElement.remove(); updateOptions(this)">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;
    
    optionsList.appendChild(newOption);
    newOption.querySelector('.option-input').focus();
    
    // Add listener to update hidden field
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

// Add input listeners to all option inputs
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('option-input')) {
        updateOptions(e.target);
    }
});

function applyTemplate(type) {
    const templates = {
        blank: {
            title: '',
            description: '',
            questions: []
        },
        customer_satisfaction: {
            title: 'Customer Satisfaction Survey',
            description: 'Help us improve by sharing your experience',
            questions: [
                { text: 'How satisfied are you with our service?', type: 'multiple_choice', options: ['Very Satisfied', 'Satisfied', 'Neutral', 'Dissatisfied', 'Very Dissatisfied'], required: true },
                { text: 'How likely are you to recommend us?', type: 'rating', required: true },
                { text: 'What did you like most?', type: 'paragraph', required: false }
            ]
        },
        event_feedback: {
            title: 'Event Feedback',
            description: 'Share your thoughts about the event',
            questions: [
                { text: 'Overall event rating', type: 'rating', required: true },
                { text: 'Which sessions did you attend?', type: 'checkbox', options: ['Keynote', 'Workshop A', 'Workshop B', 'Networking'], required: false },
                { text: 'What did you enjoy most?', type: 'paragraph', required: true }
            ]
        },
        employee_engagement: {
            title: 'Employee Engagement Survey',
            description: 'Help us create a better workplace',
            questions: [
                { text: 'Job satisfaction level', type: 'rating', required: true },
                { text: 'Do you feel valued at work?', type: 'multiple_choice', options: ['Always', 'Often', 'Sometimes', 'Rarely', 'Never'], required: true },
                { text: 'What can we improve?', type: 'paragraph', required: false }
            ]
        },
        contact_info: {
            title: 'Contact Information',
            description: 'Please provide your details so we can stay in touch.',
            questions: [
                { text: 'Full Name', type: 'short_answer', required: true },
                { text: 'Email Address', type: 'short_answer', required: true },
                { text: 'Phone Number', type: 'short_answer', required: false },
                { text: 'Best time to contact?', type: 'multiple_choice', options: ['Morning', 'Afternoon', 'Evening'], required: false },
                { text: 'Comments', type: 'paragraph', required: false }
            ]
        },
        job_application: {
            title: 'Job Application',
            description: 'Apply for your next big opportunity here.',
            questions: [
                { text: 'Desired Position', type: 'dropdown', options: ['Engineer', 'Designer', 'Manager', 'Analyst'], required: true },
                { text: 'Years of Experience', type: 'multiple_choice', options: ['0-1', '1-3', '3-5', '5+'], required: true },
                { text: 'Technical Skills', type: 'checkbox', options: ['Python', 'PHP', 'JavaScript', 'SQL', 'AWS'], required: true },
                { text: 'Portfolio Link', type: 'short_answer', required: false },
                { text: 'Why us?', type: 'paragraph', required: true }
            ]
        },
        general_quiz: {
            title: 'General Knowledge Quiz',
            description: 'Test your knowledge with these quick questions!',
            questions: [
                { text: 'Capital of France?', type: 'multiple_choice', options: ['London', 'Berlin', 'Paris', 'Rome'], required: true },
                { text: 'Which is the Red Planet?', type: 'multiple_choice', options: ['Venus', 'Mars', 'Jupiter', 'Saturn'], required: true },
                { text: 'Select primary colors:', type: 'checkbox', options: ['Red', 'Blue', 'Yellow', 'Green', 'Orange'], required: true },
                { text: 'Who wrote the Odyssey?', type: 'short_answer', required: true }
            ]
        }
    };
    
    const template = templates[type];
    if (!template) return;
    
    document.getElementById('title').value = template.title;
    document.getElementById('description').value = template.description;
    
    const container = document.getElementById('questionsContainer');
    if (template.questions.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-clipboard-list"></i>
                <h3>No questions yet</h3>
                <p>Click the + button to add your first question</p>
            </div>
        `;
    }
    
    template.questions.forEach(q => {
        addQuestion();
        const card = container.lastElementChild;
        card.querySelector('.question-input').value = q.text;
        card.querySelector('.question-type-select').value = q.type;
        
        if (q.required) {
            const reqSwitch = card.querySelector('.question-footer .switch');
            reqSwitch.classList.add('active');
            reqSwitch.nextElementSibling.value = '1';
        }
        
        if (q.options) {
            handleTypeChange(card.querySelector('.question-type-select'));
            const optionsList = card.querySelector('.options-list');
            optionsList.innerHTML = '';
            q.options.forEach((opt, i) => {
                const icon = q.type === 'checkbox' ? 'fa-square' : 'fa-circle';
                const optDiv = document.createElement('div');
                optDiv.className = 'option-item';
                optDiv.innerHTML = `
                    <span class="option-icon"><i class="fa-regular ${icon}"></i></span>
                    <input type="text" class="option-input" value="${opt}" data-option-index="${i}">
                `;
                optionsList.appendChild(optDiv);
                
                optDiv.querySelector('.option-input').addEventListener('input', function() {
                    updateOptions(this);
                });
            });
            updateOptions(optionsList.querySelector('.option-input'));
        }
    });
}

// Form submission
document.getElementById('createSurveyForm').addEventListener('submit', function(e) {
    document.getElementById('hiddenTitle').value = document.getElementById('title').value;
    document.getElementById('hiddenDescription').value = document.getElementById('description').value;
});

const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }
`;
document.head.appendChild(style);

// Check URL for template
const urlParams = new URLSearchParams(window.location.search);
const templateParam = urlParams.get('template');
if (templateParam) {
    setTimeout(() => {
        const chip = document.querySelector(`[data-template="${templateParam}"]`);
        if (chip) chip.click();
    }, 100);
}
</script>

</body>
</html>