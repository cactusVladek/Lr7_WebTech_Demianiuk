<?php
require_once __DIR__ . '/includes/functions.php';
startSession();

$errors  = [];
$message = $_SESSION['flash'] ?? null;   // повідомлення після дії
unset($_SESSION['flash']);

// ---------- Вихід ----------
if (isset($_GET['logout'])) {
    adminLogout();
    header('Location: admin.php');
    exit;
}

// ---------- Вхід ----------
if (!isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (adminLogin(trim($_POST['login']), $_POST['password'] ?? '')) {
        header('Location: admin.php');
        exit;
    }
    $errors[] = 'Неправильний логін або пароль.';
}

// ---------- Дії адміністратора ----------
if (isAdmin()) {
    // Експорт (до будь-якого HTML, бо віддаємо файл)
    if (isset($_GET['export'])) {
        exportCsv($_GET['export'] === 'comments');
    }

    // Видалення (тільки POST + перевірка CSRF-токена)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if (!checkCsrf($_POST['csrf'] ?? null)) {
            $_SESSION['flash'] = 'Помилка безпеки: оновіть сторінку і спробуйте ще раз.';
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($_POST['action'] === 'delete_comment') {
                $_SESSION['flash'] = deleteComment($id) ? "Коментар у відповіді №$id видалено." : 'Коментар не знайдено.';
            } elseif ($_POST['action'] === 'delete_response') {
                $_SESSION['flash'] = deleteResponse($id) ? "Відповідь №$id видалено повністю." : 'Відповідь не знайдено.';
            }
        }
        header('Location: admin.php');   // PRG: щоб F5 не повторював дію
        exit;
    }

    $responses = getAllResponses();
    $stats     = getStatistics();
}

$pageTitle = 'Адмін-панель: відповіді на анкету';
$wide      = isAdmin();
include __DIR__ . '/templates/header.php';
?>

<?php if (!isAdmin()): ?>

    <!-- ===== Форма входу ===== -->
    <?php renderErrors($errors); ?>
    <form method="POST" action="admin.php" class="login-form">
        <fieldset>
            <legend>Вхід для адміністратора</legend>
            <label>Логін: <input type="text" name="login" required autofocus></label>
            <label>Пароль: <input type="password" name="password" required></label>
        </fieldset>
        <button type="submit">Увійти</button>
    </form>

<?php else: ?>

    <!-- ===== Панель адміністратора ===== -->
    <p class="admin-bar">
        Ви увійшли як <strong><?= e($_SESSION['admin_login']) ?></strong>
        (вхід о <?= e($_SESSION['login_time']) ?>)
        | <a href="admin.php?logout=1">Вийти</a>
    </p>

    <?php renderMessage($message); ?>

    <div class="toolbar">
        <a class="btn" href="admin.php?export=all">⬇ Експорт усіх відповідей (CSV)</a>
        <a class="btn" href="admin.php?export=comments">⬇ Експорт коментарів (CSV)</a>
    </div>

    <h2>Статистика (усього відповідей: <?= count($responses) ?>)</h2>
    <ul>
        <?php foreach ($stats as $s): ?>
            <li><?= e($s['preferred_form']) ?> — <?= (int)$s['cnt'] ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Усі відповіді</h2>
    <?php if (empty($responses)): ?>
        <p>Відповідей ще немає.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <tr>
                <th>№</th><th>Дата</th><th>Ім’я</th><th>Email</th>
                <th>Краща форма</th><th>Переваги дистанційного</th><th>Перевага очного</th>
                <th>Оцінка</th><th>Коментар</th><th>Дії</th>
            </tr>
            <?php foreach ($responses as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= e(formatDate($r['created_at'])) ?></td>
                    <td><?= e($r['name']) ?></td>
                    <td><?= e($r['email']) ?></td>
                    <td><?= e($r['preferred_form']) ?></td>
                    <td><?= e($r['advantages']) ?></td>
                    <td><?= e($r['offline_benefit']) ?></td>
                    <td><?= (int)$r['rating'] ?></td>
                    <td><?= $r['comment'] !== null ? nl2br(e($r['comment'])) : '<em>—</em>' ?></td>
                    <td class="actions">
                        <?php if ($r['comment'] !== null): ?>
                        <form method="POST" action="admin.php" onsubmit="return confirm('Видалити коментар?');">
                            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button name="action" value="delete_comment" class="small">Видалити коментар</button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" action="admin.php" onsubmit="return confirm('Видалити всю відповідь?');">
                            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button name="action" value="delete_response" class="small danger">Видалити відповідь</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/templates/footer.php'; ?>
