<?php

date_default_timezone_set('Europe/Kyiv');

define('DB_HOST', 'sql210.infinityfree.com');
define('DB_NAME', 'if0_43483399_survey');
define('DB_USER', 'if0_43483399');
define('DB_PASS', 'CNVsoundvol14');

define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$seG2jD92j7eYCo95oyzx6.Xtqh/GcyL2BftUAS/xy.VBJxZJS2HsS');

// ---- Питання анкети ----
// column — назва стовпця в таблиці responses
$questions = [
    'q1' => [
        'column'  => 'preferred_form',
        'text'    => 'Яку форму навчання ви вважаєте кращою?',
        'type'    => 'radio',
        'options' => ['Очне навчання', 'Дистанційне навчання', 'Змішане (поєднання обох)'],
    ],
    'q2' => [
        'column'  => 'advantages',
        'text'    => 'Які переваги дистанційного навчання для вас найважливіші? (можна кілька)',
        'type'    => 'checkbox',
        'options' => ['Гнучкий графік', 'Економія часу на дорогу', 'Доступ до записів занять', 'Навчання з будь-якого місця', 'Жодних переваг не бачу'],
    ],
    'q3' => [
        'column'  => 'offline_benefit',
        'text'    => 'Що, на вашу думку, найкраще дає очне навчання?',
        'type'    => 'radio',
        'options' => ['Живе спілкування з викладачем', 'Робота в команді з одногрупниками', 'Краща дисципліна і мотивація', 'Практичні заняття в лабораторіях'],
    ],
    'q4' => [
        'column'  => 'rating',
        'text'    => 'Оцініть якість дистанційного навчання, яке ви мали (1 — погано, 5 — відмінно)',
        'type'    => 'select',
        'options' => ['1', '2', '3', '4', '5'],
    ],
    'q5' => [
        'column'  => 'comment',
        'text'    => 'Ваш коментар або пропозиції щодо навчання',
        'type'    => 'textarea',
        'options' => [],
    ],
];
