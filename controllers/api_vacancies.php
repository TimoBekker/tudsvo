
<?php
// Включение отображения ошибок и логирования
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/var/log/php-errors.log');
error_reporting(E_ALL);

require_once 'functions.php'; // подключение функций
$pdo = include 'PDO.php'; // подключение PDO

header('Content-Type: application/json');

// Получение фильтров из GET-запроса
$filter_company = isset($_GET['company']) ? trim($_GET['company']) : '';
$filter_industry = isset($_GET['industry']) ? trim($_GET['industry']) : '';
$filter_schedule = isset($_GET['schedule']) ? trim($_GET['schedule']) : '';
$filter_inval = isset($_GET['inval']) ? $_GET['inval'] : '';
$filter_salary_min = isset($_GET['salary_min']) ? $_GET['salary_min'] : '';
$filter_disability = isset($_GET['DisabilityGroup']) ? trim($_GET['DisabilityGroup']) : '';
$filter_education = isset($_GET['education']) ? trim($_GET['education']) : '';
$filter_vacancy = isset($_GET['vacancy']) ? trim($_GET['vacancy']) : '';
$filter_experience = isset($_GET['experience']) ? trim($_GET['experience']) : '';

// Параметры пагинации
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$perPage = 50;
$offset = ($currentPage - 1) * $perPage;

// Обработка параметров сортировки (оставляем только выбранные параметры)
$allowedSortFields = [
    'vacancy',
    'fullCompanyName',
    'professionalSphereName',
    'salary',
    'DisabilityGroup',
    'educationRequirements',
    'experience'
];

$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : '';
$sortOrder = isset($_GET['sortOrder']) && strtoupper($_GET['sortOrder']) === 'DESC' ? 'DESC' : 'ASC';

if (!in_array($sortBy, $allowedSortFields) || $sortBy === '') {
    $orderBySql = '';
} else {
    $orderBySql = "ORDER BY af." . $sortBy . " " . $sortOrder;
}

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
$count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
$recordsCount = $count_result['cnt'] ?? 0;

// Основной запрос с поддержкой сортировки и пагинации
$query = "
SELECT 
  af.id,
  af.vacancyName,
  af.vacancyUrl,
  af.fullCompanyName,
  af.professionalSphereName,
  af.responsibilities,
  af.scheduleType,
  af.vacancyAddress,
  af.salary,
  af.salaryMax,
  CONCAT(IFNULL(af.contactPerson, ''), ' ', IFNULL(af.contactList, '')) AS contacts,
  IFNULL(mo.contacts, 'Не указано') AS rcznContacts,
  af.educationRequirements,
  af.experience,
  af.DisabilityGroup
FROM all_fields af
LEFT JOIN m_obr mo ON af.id = mo.id
{$where_sql}
{$orderBySql}
LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$vacancies = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $vacancies[] = [
        'id' => $row['id'],
        'vacancy' => $row['vacancyName'],
        'vacancyUrl' => $row['vacancyUrl'],
        'fullCompanyName' => $row['fullCompanyName'],
        'professionalSphereName' => $row['professionalSphereName'],
        'responsibilities' => $row['responsibilities'],
        'scheduleType' => $row['scheduleType'],
        'vacancyAddress' => $row['vacancyAddress'],
        'salary' => $row['salary'],
        'salaryMax' => $row['salaryMax'],
        'contacts' => $row['contacts'],
        'rcznContacts' => $row['rcznContacts'],
        'educationRequirements' => $row['educationRequirements'],
        'experience' => $row['experience'],
        'DisabilityGroup' => $row['DisabilityGroup']
    ];
}

// Отправка JSON-ответа
echo json_encode([
    'total' => $recordsCount,
    'page' => $currentPage,
    'perPage' => $perPage,
    'vacancies' => $vacancies,
]);
exit;
?>