/* assets/script.js */
if (typeof questionCount === 'undefined') {
    var questionCount = 0;
}

function toggleSurveyType() {
    const type = document.querySelector('input[name="type"]:checked').value;
    const customBuilder = document.getElementById('customBuilder');
    const embedBuilder = document.getElementById('embedBuilder');

    const customInputs = customBuilder.querySelectorAll('input, select, textarea');
    const embedInputs = embedBuilder.querySelectorAll('input, select, textarea');

    if (type === 'custom') {
        customBuilder.style.display = 'block';
        embedBuilder.style.display = 'none';
        
        customInputs.forEach(el => el.disabled = false);
        embedInputs.forEach(el => el.disabled = true);
    } else {
        customBuilder.style.display = 'none';
        embedBuilder.style.display = 'block';
        
        customInputs.forEach(el => el.disabled = true);
        embedInputs.forEach(el => el.disabled = false);
    }
}

// Initialize with one question ONLY if empty (Create Mode)
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('questionsContainer');
    if(container && container.children.length === 0) {
        addQuestion();
    }
    // Initialize correct state (disabling/enabling inputs)
    toggleSurveyType();
});

function addQuestion() {
    const container = document.getElementById('questionsContainer');
    const template = document.getElementById('questionTemplate').innerHTML;
    
    const newHtml = template.replace(/{index}/g, questionCount);
    
    const div = document.createElement('div');
    div.innerHTML = newHtml;
    
    // Check if we are in embed mode, if so, disable these new inputs
    const type = document.querySelector('input[name="type"]:checked');
    if (type && type.value === 'embed') {
        const inputs = div.querySelectorAll('input, select, textarea');
        inputs.forEach(el => el.disabled = true);
    }

    container.appendChild(div.firstElementChild);
    
    questionCount++;
}

function removeQuestion(btn) {
    btn.closest('.question-item').remove();
}

function toggleOptions(select) {
    const optionsContainer = select.closest('.question-item').querySelector('.options-container');
    if (select.value === 'multiple_choice' || select.value === 'checkbox') {
        optionsContainer.style.display = 'block';
        optionsContainer.querySelector('input').required = true;
    } else {
        optionsContainer.style.display = 'none';
        optionsContainer.querySelector('input').required = false;
    }
}
