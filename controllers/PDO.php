<?php
// Подключение конфигурации
$config = include 'config.php';

$dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=utf8';

return new PDO($dsn, $config['user'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
?>