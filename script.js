// script.js — надсилання анкети без перезавантаження сторінки за допомогою XMLHttpRequest

document.addEventListener('DOMContentLoaded', function () {
    var form   = document.getElementById('survey-form');
    var result = document.getElementById('survey-result');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        event.preventDefault(); // зупиняємо звичайне надсилання форми (з перезавантаженням)

        // Перевірка: у питанні 2 має бути обрано хоча б один варіант
        if (!form.querySelector('input[name="q2[]"]:checked')) {
            showErrors(['Оберіть хоча б один варіант у питанні 2.']);
            return;
        }

        var button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'Надсилання...';

        // 1. Збираємо всі поля форми (ім'я, email, відповіді)
        var formData = new FormData(form);

        // 2. Створюємо і налаштовуємо запит
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'submit.php', true);           // метод POST, адреса обробника, асинхронно
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        // 3. Що робити, коли сервер відповів
        xhr.onload = function () {
            button.disabled = false;
            button.textContent = 'Надіслати відповідь';

            var response;
            try {
                response = JSON.parse(xhr.responseText); // перетворюємо JSON у об'єкт
            } catch (e) {
                showErrors(['Сервер повернув некоректну відповідь.']);
                return;
            }

            if (response.success) {
                showSuccess(response.name, response.submitted_at);
            } else {
                showErrors(response.errors || ['Невідома помилка.']);
            }
        };

        // 4. Помилка мережі (немає зв'язку із сервером)
        xhr.onerror = function () {
            button.disabled = false;
            button.textContent = 'Надіслати відповідь';
            showErrors(['Не вдалося з’єднатися із сервером.']);
        };

        // 5. Надсилаємо дані
        xhr.send(formData);
    });

    // Показ повідомлення про успіх з датою й часом заповнення
    function showSuccess(name, submittedAt) {
        form.style.display = 'none';
        result.innerHTML =
            '<div class="success">' +
                '<h2>Дякуємо, ' + escapeHtml(name) + '!</h2>' +
                '<p>Вашу відповідь збережено в базі даних.</p>' +
                '<p><strong>Дата та час заповнення форми:</strong> ' + escapeHtml(submittedAt) + '</p>' +
            '</div>' +
            '<p><a href="survey.php">Заповнити анкету ще раз</a></p>';
    }

    // Показ списку помилок над формою
    function showErrors(errors) {
        var html = '<div class="errors"><ul>';
        errors.forEach(function (err) {
            html += '<li>' + escapeHtml(err) + '</li>';
        });
        html += '</ul></div>';
        result.innerHTML = html;
        result.scrollIntoView({ behavior: 'smooth' });
    }

    // Захист від XSS: екранування спецсимволів
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
