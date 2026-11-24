<?php
// survey.php — сторінка анкети.
// Форма надсилається через XMLHttpRequest (script.js → submit.php) без перезавантаження сторінки.
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Яке навчання краще — дистанційне чи очне?';
include __DIR__ . '/templates/header.php';
?>

    <!-- Сюди script.js виводить помилки або повідомлення про успіх -->
    <div id="survey-result"></div>

    <form id="survey-form" method="POST" action="submit.php">
        <fieldset>
            <legend>Ваші дані</legend>
            <label>Ім’я:
                <input type="text" name="name" maxlength="100" required>
            </label>
            <label>Email:
                <input type="email" name="email" maxlength="150" required>
            </label>
        </fieldset>

        <?php renderQuestions($questions); ?>

        <button type="submit">Надіслати відповідь</button>
    </form>

    <script src="script.js"></script>

<?php include __DIR__ . '/templates/footer.php'; ?>
