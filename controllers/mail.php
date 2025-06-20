<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8" />
<title>Форма</title>
<style>
body {
  font-family: Arial, sans-serif;
  background-color: #f4f4f4;
  margin: 20px;
}
form {
  background: #fff;
  padding: 20px;
  max-width: 400px;
  margin: auto;
  border-radius: 8px;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
h2 {
  text-align: center;
}
label {
  display: block;
  margin-top: 15px;
}
input[type="text"],
select {
  width: 100%;
  padding: 8px;
  margin-top: 5px;
  border: 1px solid #ccc;
  border-radius: 4px;
}
input[type="checkbox"] {
  margin-right: 10px;
}
button {
  display: block;
  width: 100%;
  padding: 10px;
  background-color: #00baff;
  color: white;
  border: none;
  border-radius: 4px;
  margin-top: 20px;
  font-size: 16px;
  cursor: pointer;
}
button:hover {
  background-color: #0099cc;
}
</style>
</head>
<body>

<h2>Форма</h2>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Обработка формы
    $fio = trim($_POST['fio']);
    $tel = trim($_POST['tel']);
    $municipality = trim($_POST['municipality']);
    $privacy = isset($_POST['privacy']);

    // Проверка обязательных полей
    if (!$fio || !$tel || !$municipality || !$privacy) {
        echo "<p style='color:red;'>Пожалуйста, заполните все обязательные поля и согласитесь с условиями.</p>";
    } else {
        // Формируем сообщение
        $to = 'your_email@example.com'; // Замените на ваш email
        $subject = 'Новая заявка с сайта';
        $message = "ФИО: $fio\nТелефон: $tel\nМуниципалитет: $municipality";

        $headers = "From: no-reply@yourdomain.com\r\n"; // Замените на ваш домен
        $headers .= "Reply-To: no-reply@yourdomain.com\r\n";

        // Отправка письма
        if (mail($to, $subject, $message, $headers)) {
            echo "<p style='color:green;'>Сообщение успешно отправлено!</p>";
        } else {
            echo "<p style='color:red;'>Ошибка при отправке сообщения.</p>";
        }
    }
}
?>

<form method="post" action="">
  <label for="fio">ФИО *</label>
  <input type="text" id="fio" name="fio" required />

  <label for="tel">Телефон *</label>
  <input type="text" id="tel" name="tel" required />

  <label for="municipality">Муниципалитет *</label>
  <select id="municipality" name="municipality" required>
    <option value="">Выберите муниципалитет</option>
    <option value="Мэрия 1">Мэрия 1</option>
    <option value="Мэрия 2">Мэрия 2</option>
    <!-- Добавьте свои варианты -->
  </select>
  
  <label>
    <input type="checkbox" name="privacy" required />
    Нажимая кнопку «отправить», я даю согласие на обработку моих персональных данных, в соответствии с Федеральным законом от 27.07.2006 года №152-ФЗ «О персональных данных», на условиях и для целей, определённых в Согласии на обработку персональных данных
  </label>
  
  <button type="submit">Отправить</button>
</form>

</body>
</html>