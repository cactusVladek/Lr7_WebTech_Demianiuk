<?php
// submit.php — приймає анкету від JavaScript (XMLHttpRequest) і повертає відповідь у форматі JSON.
// HTML тут не виводиться — лише дані для скрипта script.js.
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

// Приймаємо лише POST-запити
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'errors' => ['Дозволено лише метод POST.']], JSON_UNESCAPED_UNICODE);
    exit;
}

// Перевірка даних (та сама функція, що й раніше)
[$errors, $data] = validateSurvey($_POST, $questions);

if (!empty($errors)) {
    http_response_code(422); // дані не пройшли перевірку
    echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

// Збереження в БД
try {
    $createdAt = saveSurvey($data);
} catch (Throwable $ex) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Не вдалося зберегти відповідь. Спробуйте пізніше.']], JSON_UNESCAPED_UNICODE);
    exit;
}

// Успіх: повертаємо ім'я та дату й час заповнення
echo json_encode([
    'success'      => true,
    'name'         => $data['name'],
    'submitted_at' => formatDate($createdAt),
], JSON_UNESCAPED_UNICODE);
