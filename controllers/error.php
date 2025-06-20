<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Ошибка</title>
    <link rel="stylesheet" href="styles.css" />
</head>
<body style="margin: 0; padding: 0; overflow: hidden;">
    <!-- Основной контейнер с флексбоксом для вертикального центрирования -->
    <div style="max-width: 600px; margin: 50px auto; text-align: center; display: flex; flex-direction: column; min-height: 100vh; box-sizing: border-box;">
        <h1 style="color: #e74c3c;">Возникла проблема</h1>
        <p style="font-size: 16px; margin-bottom: 30px;">
            Мы ее увидели и уже начали над ней работать.<br>
            Попробуйте вернуться <a href="/">в начало</a>.
        </p>
        <!-- Обертка для центрирования изображения по вертикали -->
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding-bottom: 20px;">
            <img src="../resources/error.png" alt="Error" style="max-width: 100%; height: auto;">
        </div>
    </div>
</body>
</html>