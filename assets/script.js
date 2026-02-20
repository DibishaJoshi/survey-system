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
    if (container && container.children.length === 0) {
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

// Template Definitions
const templates = {
    'blank': {
        title: '',
        description: '',
        questions: []
    },
    'customer_satisfaction': {
        title: 'Customer Satisfaction Survey',
        description: 'Please help us improve our services by providing your feedback.',
        questions: [
            { text: 'How satisfied are you with our service?', type: 'multiple_choice', options: 'Very Satisfied, Satisfied, Neutral, Dissatisfied' },
            { text: 'How likely are you to recommend us?', type: 'multiple_choice', options: 'Very Likely, Likely, Unlikely, Very Unlikely' },
            { text: 'What did we do well?', type: 'paragraph', options: '' },
            { text: 'What can we improve?', type: 'paragraph', options: '' }
        ]
    },
    'event_feedback': {
        title: 'Event Feedback Form',
        description: 'Thank you for attending. We would love to hear your thoughts.',
        questions: [
            { text: 'How would you rate the event overall?', type: 'multiple_choice', options: 'Excellent, Good, Average, Poor' },
            { text: 'Which sessions did you attend?', type: 'checkbox', options: 'Keynote, Workshop A, Workshop B, Networking' },
            { text: 'Any suggestions for future events?', type: 'paragraph', options: '' }
        ]
    },
    'employee_engagement': {
        title: 'Employee Engagement Survey',
        description: 'We value your input. Responses are anonymous.',
        questions: [
            { text: 'I feel valued at work.', type: 'multiple_choice', options: 'Strongly Agree, Agree, Neutral, Disagree, Strongly Disagree' },
            { text: 'I have the resources I need to do my job.', type: 'multiple_choice', options: 'Yes, No' },
            { text: 'What motivates you the most?', type: 'short_answer', options: '' }
        ]
    },
    'contact_info': {
        title: 'Contact Information',
        description: 'Please provide your contact details so we can get in touch.',
        questions: [
            { text: 'Full Name', type: 'short_answer', options: '' },
            { text: 'Email Address', type: 'short_answer', options: '' },
            { text: 'Phone Number', type: 'short_answer', options: '' },
            { text: 'Best time to reach you?', type: 'multiple_choice', options: 'Morning, Afternoon, Evening' },
            { text: 'Comments/Inquiries', type: 'paragraph', options: '' }
        ]
    },
    'job_application': {
        title: 'Job Application Form',
        description: 'Apply for your dream position by filling out the details below.',
        questions: [
            { text: 'Position Applied For', type: 'dropdown', options: 'Software Engineer, Designer, Product Manager, Marketing Specialist' },
            { text: 'Years of Professional Experience', type: 'multiple_choice', options: '0-1, 1-3, 3-5, 5+' },
            { text: 'Technical Skills', type: 'checkbox', options: 'JavaScript, PHP, Python, React, SQL, AWS' },
            { text: 'Link to Portfolio/LinkedIn', type: 'short_answer', options: '' },
            { text: 'Why are you interested in this role?', type: 'paragraph', options: '' }
        ]
    },
    'general_quiz': {
        title: 'General Knowledge Quiz',
        description: 'Test your knowledge with these fun questions!',
        questions: [
            { text: 'What is the capital of France?', type: 'multiple_choice', options: 'London, Berlin, Madrid, Paris' },
            { text: 'Which planet is known as the Red Planet?', type: 'multiple_choice', options: 'Venus, Mars, Jupiter, Saturn' },
            { text: 'Select all the primary colors:', type: 'checkbox', options: 'Red, Green, Blue, Yellow, Orange' },
            { text: 'Who wrote "Romeo and Juliet"?', type: 'short_answer', options: '' }
        ]
    }
};

function applyTemplate(type) {
    if (document.getElementById('title').value !== '' && !confirm('This will overwrite your current survey. Continue?')) {
        return;
    }

    const data = templates[type];

    document.getElementById('title').value = data.title;
    document.getElementById('description').value = data.description;

    document.getElementById('questionsContainer').innerHTML = '';

    if (data.questions.length === 0) {
        addQuestion();
    } else {
        data.questions.forEach(q => {
            addQuestion();
            const items = document.querySelectorAll('.question-item');
            const lastItem = items[items.length - 1];

            lastItem.querySelector('input[name*="[text]"]').value = q.text;
            const typeSelect = lastItem.querySelector('select[name*="[type]"]');
            typeSelect.value = q.type;
            toggleOptions(typeSelect);
            if (q.options) {
                lastItem.querySelector('input[name*="[options]"]').value = q.options;
            }
        });
    }
    document.getElementById('createSurveyForm').scrollIntoView({ behavior: 'smooth' });
}
