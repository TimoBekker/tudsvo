<?php
// Настройка ошибок
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/php-errors.log');

require_once 'functions.php';

// подключение PDO
$pdo = include 'PDO.php';

// Получение параметров
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10; // по умолчанию 10 записей на страницу

// Получение фильтров (так же, как в main.php)
$filter_company = isset($_GET['company']) ? trim($_GET['company']) : '';
$filter_industry = isset($_GET['industry']) ? trim($_GET['industry']) : '';
$filter_schedule = isset($_GET['schedule']) ? trim($_GET['schedule']) : '';
$filter_inval = isset($_GET['inval']) ? $_GET['inval'] : '';
$filter_salary_min = isset($_GET['salary_min']) ? $_GET['salary_min'] : '';

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

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Подсчет общего количества записей
$count_sql = "SELECT COUNT(*) as cnt FROM all_fields af {$where_sql}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$count_result = $count_stmt->fetch();
$total_records = $count_result['cnt'];

// Расчет смещения
$offset = ($page - 1) * $per_page;

// Основной запрос с лимитом
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
  IFNULL(mo.contacts, 'Не указано') AS `Контакты РЦЗН`
FROM all_fields af
LEFT JOIN m_obr mo ON af.id = mo.id
{$where_sql}
LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();

// Вывод таблицы (тот же код, что и в main.php)
?>
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
      <th>Контакты</th>
      <th>Контакты РЦЗН</th>
    </tr>
  </thead>
  <tbody>
<?php
$i = $offset + 1;
while ($row = $stmt->fetch()) {
    // Аналогично, как в main.php
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

    echo "<tr>
        <td>{$i}</td>
        <td>{$row['Вакансия']}</td>
        <td>".htmlspecialchars($row['Название компании'])."</td>
        <td>".htmlspecialchars($row['Отрасль'])."</td>
        <td title=\"".htmlspecialchars($row['Обязанности'])."\">".htmlspecialchars(truncate_string($row['Обязанности'], 200))."</td>
        <td>".htmlspecialchars($row['Тип занятости'])."</td>
        <td>".htmlspecialchars($clean_address)."</td>
        <td>".htmlspecialchars($display_salary)."</td>
        <td>".htmlspecialchars($row['Контакты'])."</td>
        <td>".htmlspecialchars($row['Контакты РЦЗН'])."</td>
      </tr>";
    $i++;
}
?>
  </tbody>
</table>

<!-- Навигация по страницам -->
<?php
$total_pages = ceil($total_records / $per_page);
$current_page = $page;

echo '<div class="pagination">';
if ($current_page > 1) {
    $prev_page = $current_page - 1;
    echo "<a href=\"?page={$prev_page}&per_page={$per_page}\" class=\"prev\">Предыдущая</a> ";
}
if ($current_page < $total_pages) {
    $next_page = $current_page + 1;
    echo "<a href=\"?page={$next_page}&per_page={$per_page}\" class=\"next\">Следующая</a>";
}
echo '</div>';
?>