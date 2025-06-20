<?php
// Функция для сокращения названий компаний
function shorten_company_name($fullName) {
    $patterns = [
        '/^(?:ГОСУДАРСТВЕННОЕ\s+БЮДЖЕТНОЕ\s+УЧРЕЖДЕНИЕ\s+ЗДРАВООХРАНЕНИЯ\s+САМАРСКОЙ\s+ОБЛАСТИ)/i' => 'ГБУЗ СО',
        '/^(?:ГОСУДАРСТВЕННОЕ\s+БЮДЖЕТНОЕ\s+УЧРЕЖДЕНИЕ\s+ЗДРАВООХРАНЕНИЯ)/i' => 'ГБУЗ',
        '/^(?:ГОСУДАРСТВЕННОЕ\s+КАЗЁННОЕ\s+УЧРЕЖДЕНИЕ\s+САМАРСКОЙ\s+ОБЛАСТИ)/i' => 'ГКУ СО',
        '/^(?:ГОСУДАРСТВЕННОЕ\s+КАЗЕННОЕ\s+УЧРЕЖДЕНИЕ\s+САМАРСКОЙ\s+ОБЛАСТИ)/i' => 'ГКУ СО',
        '/^(?:ГОСУДАРСТВЕННОЕ\s+БЮДЖЕТНОЕ\s+ОБЩЕОБРАЗОВАТЕЛЬНОЕ\s+УЧРЕЖДЕНИЕ\s+САМАРСКОЙ\s+ОБЛАСТИ)/i' => 'ГБОУ СО',
        '/^(?:ПУБЛИЧНОЕ\s+АКЦИОНЕРНОЕ\s+ОБЩЕСТВО)/i' => 'ПАО',
        '/^(?:ЗАКРЫТОЕ\s+АКЦИОНЕРНОЕ\s+ОБЩЕСТВО)/i' => 'ЗАО',
        '/^(?:ОТКРЫТОЕ\s+АКЦИОНЕРНОЕ\s+ОБЩЕСТВО)/i' => 'ОАО',
        '/^(?:АКЦИОНЕРНОЕ\s+ОБЩЕСТВО)/i' => 'АО',
        '/^(?:Акционерное\s+общество)/i' => 'АО',
        '/^(?:ОБЩЕСТВО\s+С\s+ОГРАНИЧЕННОЙ\s+ОТВЕТСТВЕННОСТЬЮ)/i' => 'ООО',
        '/^(?:ИНДИВИДУАЛЬНЫЙ\s+ПРЕДПРИНИМАТЕЛЬ)/i' => 'ИП',
        '/^(?:Индивидуальный\s+предприниматель)/i' => 'ИП',
        '/^(?:СЕЛЬСКОХОЗЯЙСТВЕННЫЙ\s+ПРОИЗВОДСТВЕННЫЙ\s+КООПЕРАТИВ)/i' => 'СПК'
    ];

    foreach ($patterns as $pattern => $abbreviation) {
        if (preg_match($pattern, $fullName)) {
            return preg_replace($pattern, $abbreviation, $fullName);
        }
    }
    return $fullName;
}

// Функция для генерации пагинации
function renderPagination($totalItems, $currentPage, $perPage, $baseUrl) {
    $totalPages = ceil($totalItems / $perPage);
    if ($totalPages <= 1) return;

    echo '<div class="pagination" style="margin-top:20px;">';

    // Первая и предыдущая
    if ($currentPage > 1) {
        echo '<a href="' . $baseUrl . 'page=1">« В начало</a> ';
        echo '<a href="' . $baseUrl . 'page=' . ($currentPage - 1) . '">< Предыдущая</a> ';
    }

    // Номера страниц
    $pagesToShow = 5;
    $startPage = max(1, $currentPage - floor($pagesToShow / 2));
    $endPage = min($totalPages, $startPage + $pagesToShow - 1);
    if ($endPage - $startPage + 1 < $pagesToShow) {
        $startPage = max(1, $endPage - $pagesToShow + 1);
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            echo '<span class="current" style="margin:0 5px;">' . $i . '</span> ';
        } else {
            echo '<a href="' . $baseUrl . 'page=' . $i . '" style="margin:0 5px;">' . $i . '</a> ';
        }
    }

    // Следующая и последняя
    if ($currentPage < $totalPages) {
        echo '<a href="' . $baseUrl . 'page=' . ($currentPage + 1) . '">Следующая ></a> ';
        echo '<a href="' . $baseUrl . 'page=' . $totalPages . '">Последняя »</a>';
    }

    echo '</div>';

    // Диапазон вакансий
    $startVacancy = ($currentPage - 1) * $perPage + 1;
    $endVacancy = min($currentPage * $perPage, $totalItems);
	echo '<div style="display:inline-block; width: 50px; height: 20px; margin-left: 10px;"></div>';
}

// Функция усечения строки с добавлением "..."
function truncate_string($string, $maxLength) {
    if (mb_strlen($string, 'UTF-8') > $maxLength) {
        return mb_substr($string, 0, $maxLength, 'UTF-8') . '...';
    } else {
        return $string;
    }
}

function clean_address($address) {
    return trim($address);
}
?>