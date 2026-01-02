<?php
require 'config.php';
require 'includes/header.php';
?>

<div class="mb-4">
    <h1>Create New Survey</h1>
</div>

<div class="card">
    <form id="createSurveyForm" action="actions/save_survey.php" method="POST">
        <div class="form-group">
            <label class="form-label" for="title">Survey Title</label>
            <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. Employee Satisfaction Survey">
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3" placeholder="Brief description of the survey..."></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Survey Type</label>
            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="type" value="custom" checked onchange="toggleSurveyType()"> 
                    Custom Builder
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="type" value="embed" onchange="toggleSurveyType()"> 
                    Embed External Form
                </label>
            </div>
        </div>

        <div class="form-group" style="background: #eef2ff; padding: 1rem; border-radius: var(--radius); border: 1px solid #c7d2fe;">
            <label class="form-label">Details & Settings</label>
            <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="limit_one" value="1"> 
                    Limit to 1 Response per Person
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="allow_edit" value="1"> 
                    Allow Respondents to Edit Answers
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="collect_email" value="1"> 
                    Collect Email Addresses
                </label>
            </div>
        </div>

        <!-- Custom Builder Section -->
        <div id="customBuilder">
            <h3>Questions</h3>
            <div id="questionsContainer">
                <!-- Questions will be added here dynamically -->
            </div>
            
            <button type="button" class="btn" style="background: #e2e8f0; color: var(--text-main); margin-top: 1rem;" onclick="addQuestion()">
                <i class="fas fa-plus" style="margin-right: 0.5rem;"></i> Add Question
            </button>
        </div>

        <!-- Embed Section -->
        <div id="embedBuilder" style="display: none;">
            <div class="form-group">
                <label class="form-label" for="embed_code">Embed Code (Google/Microsoft Forms)</label>
                <textarea id="embed_code" name="embed_code" class="form-control" rows="5" placeholder="Paste the iframe code here..."></textarea>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem;">
                    Go to your form provider, find the "Embed" or "Share" option, and copy the HTML code (usually starts with &lt;iframe).
                </p>
            </div>
        </div>

        <div style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Save Survey</button>
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
            <label style="display: inline-flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.875rem; cursor: pointer;">
                <input type="checkbox" name="questions[{index}][required]" value="1"> Required
            </label>
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

<script src="assets/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
