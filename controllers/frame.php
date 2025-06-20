<?php
// Включение ошибок для отладки (уберите в продакшене)
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', '/var/log/php-errors.log');
error_reporting(E_ALL);

// Подключение функций
require_once 'functions.php';

// Установка PDO
$pdo = include 'PDO.php';

// Установка русской локали для форматирования даты
setlocale(LC_TIME, 'ru_RU.UTF-8');
$current_date = new DateTime();
$current_date->modify('-1 day');
$display_date = strftime('%d %B %Y', $current_date->getTimestamp());

// Получение фильтров из GET
$filter_company = isset($_GET['company']) ? trim($_GET['company']) : '';
$filter_industry = isset($_GET['industry']) ? trim($_GET['industry']) : '';
$filter_schedule = isset($_GET['schedule']) ? trim($_GET['schedule']) : '';
$filter_inval = isset($_GET['inval']) ? $_GET['inval'] : '';
$filter_salary_min = isset($_GET['salary_min']) ? $_GET['salary_min'] : '';
$filter_disability = isset($_GET['disability']) ? trim($_GET['disability']) : '';
$filter_education = isset($_GET['education']) ? trim($_GET['education']) : '';
$filter_vacancy = isset($_GET['vacancy']) ? trim($_GET['vacancy']) : '';
$filter_experience = isset($_GET['experience']) ? trim($_GET['experience']) : ''; // Новый фильтр

// Текущая страница
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

$perPage = 50;

// Получение уникальных значений для фильтров
function get_unique_values(PDO $pdo, $field_name) {
    $stmt = $pdo->prepare("SELECT DISTINCT $field_name FROM all_fields WHERE $field_name IS NOT NULL AND $field_name <> ''");
    $stmt->execute();
    $values = [];
    while ($row = $stmt->fetch()) {
        $values[] = $row[$field_name];
    }
    return $values;
}

$company_list = get_unique_values($pdo, 'fullCompanyName');
$industry_list = get_unique_values($pdo, 'professionalSphereName');
$schedule_list = get_unique_values($pdo, 'scheduleType');
$disability_group_list = get_unique_values($pdo, 'DisabilityGroup');
$education_list = get_unique_values($pdo, 'educationRequirements');
$experience_list = get_unique_values($pdo, 'experience');

sort($industry_list, SORT_STRING | SORT_FLAG_CASE);
sort($schedule_list, SORT_STRING | SORT_FLAG_CASE);
sort($disability_group_list, SORT_STRING | SORT_FLAG_CASE);
sort($education_list, SORT_STRING | SORT_FLAG_CASE);
sort($experience_list, SORT_STRING | SORT_FLAG_CASE);

$company_map = [];
foreach ($company_list as $name) {
    $short_name = shorten_company_name($name);
    $company_map[$name] = $short_name;
}
uasort($company_map, function($a, $b) {
    return strcasecmp($a, $b);
});

// Построение условий WHERE
$where_clauses = [];
$params = [];

if ($filter_company !== '') {
    $where_clauses[] = "af.fullCompanyName LIKE :company";
    $params[':company'] = '%' . $filter_company . '%';
}
if ($filter_industry !== '') {
    $where_clauses[] = "af.professionalSphereName LIKE :industry";
    $params[':industry'] = '%' . $filter_industry . '%';
}
if ($filter_schedule !== '') {
    $where_clauses[] = "af.scheduleType LIKE :schedule";
    $params[':schedule'] = '%' . $filter_schedule . '%';
}
if ($filter_inval === '1') {
    $where_clauses[] = "af.socialProtecteds LIKE '%Инвалид%'";
}
if ($filter_salary_min === '1') {
    $where_clauses[] = "(
        (
            (af.salary IS NOT NULL AND af.salary <> '') AND (
                (af.salary REGEXP 'от[[:space:]]*[0-9]+' AND CAST(REPLACE(REPLACE(af.salary, 'от', ''), ' ', '') AS UNSIGNED) >= 100000)
                OR
                (af.salary REGEXP '^[0-9]+' AND CAST(REPLACE(af.salary, ' ', '') AS UNSIGNED) >= 100000)
            )
        )
        OR
        (
            (af.salaryMax IS NULL OR af.salaryMax = '') AND (
                (af.salaryMax REGEXP 'от[[:space:]]*[0-9]+' AND CAST(REPLACE(REPLACE(af.salaryMax, 'от', ''), ' ', '') AS UNSIGNED) >= 100000)
                OR
                (af.salaryMax REGEXP '^[0-9]+' AND CAST(REPLACE(af.salaryMax, ' ', '') AS UNSIGNED) >= 100000)
            )
        )
    )";
}
if ($filter_disability !== '') {
    $where_clauses[] = "af.DisabilityGroup LIKE :disability";
    $params[':disability'] = '%' . $filter_disability . '%';
}
if ($filter_education !== '') {
    $where_clauses[] = "af.educationRequirements = :education";
    $params[':education'] = $filter_education;
}
if ($filter_vacancy !== '') {
    $where_clauses[] = "af.vacancyName LIKE :vacancy";
    $params[':vacancy'] = '%' . $filter_vacancy . '%';
}
if ($filter_experience !== '') {
    $where_clauses[] = "af.experience = :experience";
    $params[':experience'] = $filter_experience;
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Подсчет общего количества вакансий
$count_query = "SELECT COUNT(*) as cnt FROM all_fields af {$where_sql}";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$count_result = $count_stmt->fetch();
$recordsCount = $count_result['cnt'] ?? 0;

// Функция склонения слова "вакансия"
function plural_form($n, $forms) {
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n >= 11 && $n <= 14) {
        return $forms[2];
    }
    if ($n1 == 1) {
        return $forms[0];
    } elseif ($n1 >= 2 && $n1 <= 4) {
        return $forms[1];
    } else {
        return $forms[2];
    }
}

// Определение формы слова "вакансия" и глагола "найдено"
if ($recordsCount == 1) {
    $verb = 'Найдена';
    $word_form = 'вакансия';
} else {
    $verb = 'Найдено';
    $word_form = plural_form($recordsCount, ['вакансия', 'вакансии', 'вакансий']);
}

// Проверка, есть ли активные фильтры
$has_active_filters = (
    $filter_company !== '' ||
    $filter_industry !== '' ||
    $filter_schedule !== '' ||
    $filter_inval === '1' ||
    $filter_salary_min === '1' ||
    $filter_disability !== '' ||
    $filter_education !== '' ||
    $filter_vacancy !== '' ||
    $filter_experience !== ''
);

// Создаем сообщение о фильтрах только если есть активные фильтры
if ($recordsCount > 0 && $has_active_filters) {
    $filters_list = [];
    if ($filter_company !== '') $filters_list[] = "Компания: " . $filter_company;
    if ($filter_industry !== '') $filters_list[] = "Отрасль: " . $filter_industry;
    if ($filter_schedule !== '') $filters_list[] = "Тип занятости: " . $filter_schedule;
    if ($filter_inval === '1') $filters_list[] = "Инвалидность";
    if ($filter_salary_min === '1') $filters_list[] = "от 100 тыс.";
    if ($filter_disability !== '') $filters_list[] = "Доступность: " . $filter_disability;
    if ($filter_education !== '') $filters_list[] = "Образование: " . $filter_education;
    if ($filter_vacancy !== '') $filters_list[] = "Вакансия: " . $filter_vacancy;
    if ($filter_experience !== '') $filters_list[] = "Опыт работы: " . $filter_experience;

    $filters_list_str = implode(', ', $filters_list);

    $filter_message = "$verb $recordsCount $word_form по фильтрам: {$filters_list_str}";
} else {
    $filter_message = '';
}

// Параметры пагинации
$start_vacancy = ($currentPage - 1) * $perPage + 1;
$end_vacancy = min($start_vacancy + $perPage - 1, $recordsCount);
$baseUrlParams = $_GET;
unset($baseUrlParams['page']);
$baseUrl = '?' . http_build_query($baseUrlParams);
if ($baseUrl !== '?') {
    $baseUrl .= '&';
}
$offset = ($currentPage - 1) * $perPage;

// Основной запрос
$query = "
SELECT 
  CONCAT('<a href=\"', IFNULL(af.vacancyUrl, '#'), '\" target=\"_blank\">', IFNULL(af.vacancyName, 'Не указано'), '</a>') AS Вакансия,
  IFNULL(af.fullCompanyName, 'Не указано') AS `Название компании`,
  IFNULL(af.professionalSphereName, 'Не указано') AS `Отрасль`,
  IFNULL(af.responsibilities, 'Не указано') AS `Обязанности`,
  IFNULL(af.scheduleType, 'Не указано') AS `Тип занятости`,
  IFNULL(af.vacancyAddress, '') AS Адрес,
  af.salary,
  af.salaryMax,
  CONCAT(IFNULL(af.contactPerson, ''), ' ', IFNULL(af.contactList, '')) AS Контакты,
  IFNULL(mo.contacts, 'Не указано') AS `Контакты РЦЗН`,
  af.educationRequirements,
  af.experience
FROM all_fields af
LEFT JOIN m_obr mo ON af.id = mo.id
{$where_sql}
LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$i = $start_vacancy;
$stmt->execute();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta charset="UTF-8" />
<title>Работа для СВОих</title>
<!-- Подключение стилей -->
<link rel="stylesheet" href="/resources/frame.css"/>
</head>
<body>

<!-- Обновление даты -->
<!--
<div class="date-banner">
    Последнее обновление: <?php echo htmlspecialchars($display_date); ?>
</div> -->

<!-- Форма фильтров -->
<div class="filter-container" id="filter-container">
<form method="get" action="">
  <!-- Поле поиска вакансии -->
   <label class="search-field">
    Поиск вакансии: 
    <input
      type="text"
      name="vacancy"
      value="<?php echo htmlspecialchars($filter_vacancy); ?>"
      placeholder="Введите название вакансии"
    />
  </label>

  <!-- Остальные фильтры -->
  <label>
    Компания:
    <select name="company" title="Выберите компанию" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php foreach ($company_map as $original => $shortened): ?>
        <option value="<?php echo htmlspecialchars($original); ?>"
          <?php if ($filter_company == $original) echo 'selected'; ?> 
          title="<?php echo htmlspecialchars($original); ?>">
         <?php echo htmlspecialchars(truncate_string($shortened, 50)); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>
    Отрасль:
    <select name="industry" title="Выберите отрасль" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php foreach ($industry_list as $val): ?>
        <option value="<?php echo htmlspecialchars($val); ?>" 
          <?php if ($filter_industry == $val) echo 'selected'; ?> 
          title="<?php echo htmlspecialchars($val); ?>">
          <?php echo htmlspecialchars($val); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>
    Тип занятости:
    <select name="schedule" title="Выберите тип занятости" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php foreach ($schedule_list as $val): ?>
        <option value="<?php echo htmlspecialchars($val); ?>" 
          <?php if ($filter_schedule == $val) echo 'selected'; ?> 
          title="<?php echo htmlspecialchars($val); ?>">
          <?php echo htmlspecialchars($val); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>
    Опыт работы:
    <select name="experience" title="Выберите опыт работы" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php foreach ($experience_list as $exp): ?>
        <option value="<?php echo htmlspecialchars($exp); ?>"
          <?php if ($filter_experience == $exp) echo 'selected'; ?>>
          <?php echo htmlspecialchars($exp); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>
    Образование:
    <select name="education" title="Выберите образование" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php foreach ($education_list as $val): ?>
        <option value="<?php echo htmlspecialchars($val); ?>"
          <?php if ($filter_education == $val) echo 'selected'; ?> 
          title="<?php echo htmlspecialchars($val); ?>">
          <?php echo htmlspecialchars(truncate_string($val, 50)); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label style="margin-left:20px;">
    Доступность для инвалидов:
    <select name="disability" title="Выберите доступность" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <option value="Нарушения функций опорно-двигательного аппарата" <?php if ($filter_disability == 'Нарушения функций опорно-двигательного аппарата') echo 'selected'; ?>>
        Нарушения функций опорно-двигательного аппарата
      </option>
      <option value="Интеллектуальные нарушения" <?php if ($filter_disability == 'Интеллектуальные нарушения') echo 'selected'; ?>>
        Интеллектуальные нарушения
      </option>
      <option value="Нарушения функции зрения и слуха – слепоглухой" <?php if ($filter_disability == 'Нарушения функции зрения и слуха – слепоглухой') echo 'selected'; ?>>
        Нарушения функции зрения и слуха – слепоглухой
      </option>
      <option value="Нарушения зрения – слабовидящий" <?php if ($filter_disability == 'Нарушения зрения – слабовидящий') echo 'selected'; ?>>
        Нарушения зрения – слабовидящий
      </option>
      <option value="Нарушения слуха – слабослышащий" <?php if ($filter_disability == 'Нарушения слуха – слабослышащий') echo 'selected'; ?>>
        Нарушения слуха – слабослышащий
      </option>
      <option value="Нарушение речи" <?php if ($filter_disability == 'Нарушение речи') echo 'selected'; ?>>
        Нарушение речи
      </option>
      <option value="Задержка психологического развития" <?php if ($filter_disability == 'Задержка психологического развития') echo 'selected'; ?>>
        Задержка психологического развития
      </option>
      <option value="Нарушения зрения – слепой" <?php if ($filter_disability == 'Нарушения зрения – слепой') echo 'selected'; ?>>
        Нарушения зрения – слепой
      </option>
      <option value="Нарушение слуха – глухой" <?php if ($filter_disability == 'Нарушение слуха – глухой') echo 'selected'; ?>>
        Нарушение слуха – глухой
      </option>
      <option value="Расстройство аутистического спектра" <?php if ($filter_disability == 'Расстройство аутистического спектра') echo 'selected'; ?>>
        Расстройство аутистического спектра
      </option>
      <option value="Общее заболевание" <?php if ($filter_disability == 'Общее заболевание') echo 'selected'; ?>>
        Общее заболевание
      </option>
    </select>
  </label>
  <label class="checkbox-column" style="margin-left:20px;">
    <span>от 100 тыс.</span>
    <input type="checkbox" name="salary_min" value="1" onchange="this.form.submit()" <?php if ($filter_salary_min === '1') echo 'checked'; ?>>
</label>
</form>
</div>

<!-- Сообщение о фильтрах 
<?php if ($filter_message !== ''): ?>
    <h2 style="text-align: left; margin-bottom: 10px;"><?php echo htmlspecialchars($filter_message); ?></h2>
<?php endif; ?> -->

<!-- Пагинация и диапазон сверху -->
<?php if ($recordsCount >= $perPage): ?>
<div class="pagination-and-range">
  <?php renderPagination($recordsCount, $currentPage, $perPage, $baseUrl); ?>
  <div class="range-info">
    Вакансии с <?php echo $start_vacancy; ?> по <?php echo $end_vacancy; ?> из <?php echo $recordsCount; ?>
  </div>
</div>
<?php endif; ?>

<!-- Таблица вакансий -->
<div style="display:inline-block; width: 50px; height: 20px; margin-left: 10px;"></div>
<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Вакансия</th>
        <th>Название компании</th>
        <th>Отрасль</th>
        <th>Обязанности</th>
        <th>Тип занятости</th>
        <th>Адрес</th>
        <th>Заработная плата</th>
        <th>Образование</th>
        <th>Опыт работы</th> <!-- добавлено -->
        <th>Контакты от работодателя</th>
        <th>Контакты от территориального центра занятости</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $stmt->fetch()): 
        $salary = $row['salary'];
        $salaryMax = $row['salaryMax'];

        if (stripos($salary, 'от 0') !== false) {
            $display_salary = $salaryMax;
        } elseif (trim($salary) !== '') {
            $display_salary = $salary;
        } else {
            $display_salary = 'Не указано';
        }

        $address = $row['Адрес'];
        $clean_address = '';
        if ($address !== null && $address !== '') {
            $clean_address = clean_address($address);
        }
        ?>
        <tr>
          <td><?php echo $i; ?></td>
          <td><?php echo $row['Вакансия']; ?></td>
          <td><?php echo htmlspecialchars($row['Название компании']); ?></td>
          <td><?php echo htmlspecialchars($row['Отрасль']); ?></td>
          <td title="<?php echo htmlspecialchars($row['Обязанности']); ?>"><?php echo htmlspecialchars(truncate_string($row['Обязанности'], 200)); ?></td>
          <td><?php echo htmlspecialchars($row['Тип занятости']); ?></td>
          <td><?php echo htmlspecialchars($clean_address); ?></td>
          <td><?php echo htmlspecialchars($display_salary); ?></td>
          <td><?php echo htmlspecialchars($row['educationRequirements']); ?></td>
          <td><?php echo htmlspecialchars($row['experience']); ?></td>
          <td><?php echo htmlspecialchars($row['Контакты']); ?></td>
          <td><?php echo htmlspecialchars($row['Контакты РЦЗН']); ?></td>
        </tr>
        <?php $i++; ?>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- Пагинация снизу -->
<?php if ($recordsCount >= $perPage): ?>
<div class="pagination-and-range">
  <?php renderPagination($recordsCount, $currentPage, $perPage, $baseUrl); ?>
  <div class="range-info">
    Вакансии с <?php echo $start_vacancy; ?> по <?php echo $end_vacancy; ?> из <?php echo $recordsCount; ?>
  </div>
</div>
<?php endif; ?>

<!-- Подключение JS -->
<script src="/scripts/script.js"></script>
<script src="/scripts/button.js"></script>
<script src="/scripts/m_button.js"></script>
<script src="/scripts/iframe.js"></script>
<script src="/scripts/auto-submit-vacancy.js"></script>

</body>
</html>