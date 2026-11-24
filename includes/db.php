<?php
require_once __DIR__ . '/config.php';

/**
 * Повертає одне спільне підключення до БД (створюється при першому виклику).
 */
function getDb(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // помилки — як винятки
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // рядки — як асоціативні масиви
            ]);
        } catch (PDOException $ex) {
            die('Помилка підключення до бази даних: ' . htmlspecialchars($ex->getMessage()));
        }
    }
    return $pdo;
}
