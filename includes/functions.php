<?php
// includes/functions.php — уся логіка сайту у вигляді функцій.
// Сторінки survey.php та admin.php містять переважно HTML і лише викликають ці функції.

require_once __DIR__ . '/db.php';

/* ================= ЗАГАЛЬНІ ================= */

/** Безпечне виведення тексту в HTML (захист від XSS). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Значення поля з попереднього надсилання форми (щоб не вводити заново). */
function old(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

/** Виводить список помилок. */
function renderErrors(array $errors): void
{
    if (empty($errors)) {
        return;
    }
    echo '<div class="errors"><ul>';
    foreach ($errors as $err) {
        echo '<li>' . e($err) . '</li>';
    }
    echo '</ul></div>';
}

/** Виводить повідомлення (успіх / інформація). */
function renderMessage(?string $message): void
{
    if ($message) {
        echo '<div class="success">' . e($message) . '</div>';
    }
}

/* ================= АНКЕТА ================= */

/** Виводить одне питання анкети у потрібному форматі (radio, checkbox, select, textarea). */
function renderQuestion(string $key, array $q, int $number): void
{
    echo '<fieldset><legend>' . $number . '. ' . e($q['text']) . '</legend>';

    switch ($q['type']) {
        case 'radio':
            foreach ($q['options'] as $opt) {
                $checked = (old($key) === $opt) ? 'checked' : '';
                echo '<label><input type="radio" name="' . $key . '" value="' . e($opt) . '" required ' . $checked . '> ' . e($opt) . '</label>';
            }
            break;

        case 'checkbox':
            foreach ($q['options'] as $opt) {
                $checked = in_array($opt, (array)old($key, []), true) ? 'checked' : '';
                echo '<label><input type="checkbox" name="' . $key . '[]" value="' . e($opt) . '" ' . $checked . '> ' . e($opt) . '</label>';
            }
            break;

        case 'select':
            echo '<select name="' . $key . '" required><option value="">— оберіть —</option>';
            foreach ($q['options'] as $opt) {
                $selected = (old($key) === $opt) ? 'selected' : '';
                echo '<option value="' . e($opt) . '" ' . $selected . '>' . e($opt) . '</option>';
            }
            echo '</select>';
            break;

        default: // textarea
            echo '<textarea name="' . $key . '">' . e(old($key)) . '</textarea>';
    }

    echo '</fieldset>';
}

/** Виводить усі питання анкети. */
function renderQuestions(array $questions): void
{
    $i = 1;
    foreach ($questions as $key => $q) {
        renderQuestion($key, $q, $i++);
    }
}

/**
 * Перевіряє дані форми.
 * Повертає [масив_помилок, масив_даних_для_БД].
 */
function validateSurvey(array $post, array $questions): array
{
    $errors = [];
    $data   = [];

    $data['name']  = trim($post['name']  ?? '');
    $data['email'] = trim($post['email'] ?? '');

    if ($data['name'] === '' || mb_strlen($data['name']) > 100) {
        $errors[] = 'Вкажіть ваше ім’я (до 100 символів).';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Вкажіть коректний email.';
    }

    foreach ($questions as $key => $q) {
        $col = $q['column'];

        if ($q['type'] === 'checkbox') {
            $selected = array_values(array_intersect((array)($post[$key] ?? []), $q['options']));
            if (empty($selected)) {
                $errors[] = 'Оберіть хоча б один варіант у питанні: «' . $q['text'] . '»';
            }
            $data[$col] = implode('; ', $selected);
        } elseif ($q['type'] === 'textarea') {
            $data[$col] = trim($post[$key] ?? '');
        } else {
            $value = $post[$key] ?? '';
            if (!in_array($value, $q['options'], true)) {
                $errors[] = 'Дайте відповідь на питання: «' . $q['text'] . '»';
            }
            $data[$col] = $value;
        }
    }

    return [$errors, $data];
}

/** Зберігає відповідь у БД. Повертає дату й час заповнення. */
function saveSurvey(array $data): string
{
    $createdAt = date('Y-m-d H:i:s');

    $sql = 'INSERT INTO responses
              (name, email, preferred_form, advantages, offline_benefit, rating, comment, created_at)
            VALUES
              (:name, :email, :preferred_form, :advantages, :offline_benefit, :rating, :comment, :created_at)';

    // Підготовлений запит — захист від SQL-ін’єкцій
    $stmt = getDb()->prepare($sql);
    $stmt->execute([
        ':name'            => $data['name'],
        ':email'           => $data['email'],
        ':preferred_form'  => $data['preferred_form'],
        ':advantages'      => $data['advantages'],
        ':offline_benefit' => $data['offline_benefit'],
        ':rating'          => (int)$data['rating'],
        ':comment'         => $data['comment'] !== '' ? $data['comment'] : null,
        ':created_at'      => $createdAt,
    ]);

    return $createdAt;
}

/** Форматує дату з БД у вигляд ДД.ММ.РРРР ГГ:ХХ:СС. */
function formatDate(string $dbDate): string
{
    return date('d.m.Y H:i:s', strtotime($dbDate));
}

/* ================= АДМІНІСТРАТОР ================= */

/** Запускає сесію (якщо ще не запущена). */
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/** Перевіряє логін і пароль. При успіху записує вхід у $_SESSION. */
function adminLogin(string $login, string $password): bool
{
    if ($login === ADMIN_LOGIN && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);               // захист від підміни сесії
        $_SESSION['admin_logged_in'] = true;       // фіксуємо вхід адміністратора
        $_SESSION['admin_login']     = $login;
        $_SESSION['login_time']      = date('d.m.Y H:i:s');
        return true;
    }
    return false;
}

/** Чи увійшов адміністратор. */
function isAdmin(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

/** Вихід адміністратора — знищення сесії. */
function adminLogout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** CSRF-токен: захищає дії адміністратора від підроблених запитів. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function checkCsrf(?string $token): bool
{
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/** Усі відповіді з БД (новіші — першими). */
function getAllResponses(): array
{
    return getDb()->query('SELECT * FROM responses ORDER BY created_at DESC, id DESC')->fetchAll();
}

/** Кількість відповідей за кожним варіантом питання 1 — для короткої статистики. */
function getStatistics(): array
{
    return getDb()
        ->query('SELECT preferred_form, COUNT(*) AS cnt FROM responses GROUP BY preferred_form ORDER BY cnt DESC')
        ->fetchAll();
}

/** Видаляє лише коментар у відповіді (решта відповіді залишається). */
function deleteComment(int $id): bool
{
    $stmt = getDb()->prepare('UPDATE responses SET comment = NULL WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount() > 0;
}

/** Видаляє відповідь повністю. */
function deleteResponse(int $id): bool
{
    $stmt = getDb()->prepare('DELETE FROM responses WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->rowCount() > 0;
}

/**
 * Експорт у CSV (відкривається в Excel).
 * $onlyComments = true — лише коментарі; false — усі відповіді.
 */
function exportCsv(bool $onlyComments = false): void
{
    $rows     = getAllResponses();
    $fileName = ($onlyComments ? 'comments_' : 'survey_') . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM — щоб Excel правильно показав кирилицю

    if ($onlyComments) {
        fputcsv($out, ['ID', 'Дата', 'Ім’я', 'Email', 'Коментар'], ';');
        foreach ($rows as $r) {
            if ($r['comment'] !== null && $r['comment'] !== '') {
                fputcsv($out, [$r['id'], formatDate($r['created_at']), $r['name'], $r['email'], $r['comment']], ';');
            }
        }
    } else {
        fputcsv($out, ['ID', 'Дата', 'Ім’я', 'Email', 'Краща форма', 'Переваги дистанційного', 'Перевага очного', 'Оцінка', 'Коментар'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], formatDate($r['created_at']), $r['name'], $r['email'],
                $r['preferred_form'], $r['advantages'], $r['offline_benefit'], $r['rating'], $r['comment'],
            ], ';');
        }
    }

    fclose($out);
    exit;
}
