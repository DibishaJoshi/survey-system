<?php
require 'config.php';
require 'includes/header.php';

$templates = [
    [
        'id' => 'blank',
        'name' => 'Blank Survey',
        'desc' => 'Start from scratch with a clean slate',
        'icon' => 'fa-plus',
        'color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    ],
    [
        'id' => 'customer_satisfaction',
        'name' => 'Customer Feedback',
        'desc' => 'Measure satisfaction and loyalty',
        'icon' => 'fa-face-smile',
        'color' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',

    ],
    [
        'id' => 'contact_info',
        'name' => 'Contact Info',
        'desc' => 'Capture lead and contact details',
        'icon' => 'fa-address-book',
        'color' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
    ],
    [
        'id' => 'job_application',
        'name' => 'Job Application',
        'desc' => 'Streamline your hiring process',
        'icon' => 'fa-briefcase',
        'color' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
    ],
    [
        'id' => 'general_quiz',
        'name' => 'General Quiz',
        'desc' => 'Engage users with a fun quiz',
        'icon' => 'fa-lightbulb',
        'color' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',

    ],
    [
        'id' => 'event_feedback',
        'name' => 'Event Survey',
        'desc' => 'Gather post-event insights',
        'icon' => 'fa-calendar-check',
        'color' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
    ],
    [
        'id' => 'employee_engagement',
        'name' => 'Employee Pulse',
        'desc' => 'Track team engagement levels',
        'icon' => 'fa-users',
        'color' => 'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'
    ]
];
?>

<div class="container" style="padding-top: 4rem; padding-bottom: 6rem;">
    <div style="margin-bottom: 4rem; text-align: center;">
        <h1 style="font-size: 3rem; font-weight: 800; color: #1a1a1a; margin-bottom: 1rem;">Template Gallery</h1>
        <p style="font-size: 1.25rem; color: #6b7280;">Select a template to jumpstart your survey creation process</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 2.5rem;">
        <?php foreach ($templates as $t): ?>
            <a href="create_survey.php?template=<?php echo $t['id']; ?>" class="template-card-large" style="text-decoration: none; display: block; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: all 0.3s ease;">
                <div style="height: 200px; background: <?php echo $t['color']; ?>; display: flex; align-items: center; justify-content: center; position: relative;">
                    <?php if (isset($t['badge'])): ?>
                        <span style="position: absolute; top: 1rem; right: 1rem; background: white; color: #667eea; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;"><?php echo $t['badge']; ?></span>
                    <?php
    endif; ?>
                    <i class="fa-solid <?php echo $t['icon']; ?>" style="font-size: 5rem; color: white;"></i>
                </div>
                <div style="padding: 2rem;">
                    <h3 style="font-size: 1.5rem; font-weight: 700; color: #1a1a1a; margin-bottom: 0.75rem;"><?php echo $t['name']; ?></h3>
                    <p style="font-size: 1.1rem; color: #6b7280; line-height: 1.5;"><?php echo $t['desc']; ?></p>
                </div>
            </a>
        <?php
endforeach; ?>
    </div>
</div>

<style>
.template-card-large:hover {
    transform: translateY(-12px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}
</style>


</body>
</html>
