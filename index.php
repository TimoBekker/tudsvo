<?php
// Включение ошибок (на продакшне отключите display_errors)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-errors.log'); // Укажите путь к лог-файлу

// Обработка ошибок и исключений
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Error [$severity]: $message in $file on line $line");
    header('Location: /error');
    exit();
});

set_exception_handler(function($exception) {
    error_log("Exception: " . $exception->getMessage());
    header('Location: /error');
    exit();
});

// Получение маршрута из URL
$route = isset($_GET['route']) ? trim($_GET['route']) : '';

// Объявляем базовую директорию для контроллеров
$controllerDir = __DIR__ . '/controllers/';

// Функция для отображения страницы
function renderPage($page, $controllerDir) {
    $file = $controllerDir . $page . '.php';
    if (file_exists($file)) {
        include $file;
    } else {
        // Если файла нет, показываем ошибку 404
        header("HTTP/1.0 404 Not Found");
        include '404.php';
    }
}

// Маршрутизация
switch ($route) {
    case '':
        // Главная страница
        renderPage('main', $controllerDir);
        break;
    case 'error':
        // Страница ошибок
        renderPage('error', $controllerDir);
        break;
	case 'mobile':
        // Версия с карточками
        renderPage('mobile', $controllerDir);	
    default:
        // Другие маршруты, например, /about, /contact
        renderPage($route, $controllerDir);
        break;
}
?>