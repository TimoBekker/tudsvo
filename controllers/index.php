<!DOCTYPE html>
<html lang="ru">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta charset="UTF-8" />
<title>Работа для СВОих</title>
<!-- Подключение стилей -->
<link rel="stylesheet" href="resources/styles.css" />
</head>
<body>

<div class="date-banner" id="last-update"></div>

<!-- Форма фильтров (можно оставить только кнопки/поля, без PHP, все параметры через JS) -->
<div class="filter-container" id="filters">
<!-- Здесь можно оставить статическую разметку или динамически генерировать через JS -->
<!-- Например, поля поиска, селекты и чекбоксы -->
<!-- Или оставить как есть, чтобы серверная часть заполняла их при первой загрузке -->
</div>

<a class="reset-button" href="#" id="resetFilters">Сбросить</a>

<h2 id="filter-message"></h2>

<div class="pagination-and-range" id="paginationTop"></div>

<div class="table-wrapper" id="vacancy-table">
<!-- Таблица будет заполняться через JS -->
</div>

<div class="pagination-and-range" id="paginationBottom"></div>

<!-- Скрипты -->
<script>
const apiUrl = 'https://trudsvo-rabota.samregion.ru/controllers/api_vacancies.php';

let currentPage = 1;
const perPage = 50;
let totalRecords = 0;

// Загрузка даты
document.getElementById('last-update').innerText = 'Последнее обновление: ' + (new Date()).toLocaleString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });

// Функция для получения фильтров из формы
function getFilters() {
  const form = document.querySelector('form'); // или получайте вручную параметры
  const params = new URLSearchParams(new FormData(form));
  params.set('page', currentPage);
  params.set('perPage', perPage);
  return params;
}

// Загрузка данных
function loadVacancies() {
  const params = getFilters();
  fetch(apiUrl + '?' + params.toString())
    .then(res => res.json())
    .then(data => {
      totalRecords = data.total;
      renderTable(data.vacancies);
      renderPagination();
      renderRange();
      updateFilterMessage(data.total);
    });
}

// Рендер таблицы
function renderTable(vacancies) {
  const container = document.getElementById('vacancy-table');
  let html = '<table><thead><tr>' +
    '<th>#</th><th>Вакансия</th><th>Название компании</th><th>Отрасль</th><th>Обязанности</th>' +
    '<th>Тип занятости</th><th>Адрес</th><th>Заработная плата</th><th>Образование</th><th>Опыт работы</th>' +
    '<th>Контакты от работодателя</th><th>Контакты РЦЗН</th></tr></thead><tbody>';
  let i = (currentPage - 1) * perPage + 1;
  vacancies.forEach(row => {
    // обработка зарплаты
    let display_salary = row.salary;
    if (row.salary && row.salary.toLowerCase().includes('от 0')) {
      display_salary = row.salaryMax;
    } else if (row.salary && row.salary.trim() !== '') {
      display_salary = row.salary;
    } else {
      display_salary = 'Не указано';
    }

    // обработка адрес
    let address = row.vacancyAddress || '';
    // Можно добавить функцию clean_address, если нужно

    html += `<tr>
      <td>${i}</td>
      <td><a href="${row.vacancyUrl || '#'}" target="_blank">${row.vacancy}</a></td>
      <td>${escapeHtml(row.fullCompanyName)}</td>
      <td>${escapeHtml(row.professionalSphereName)}</td>
      <td title="${escapeHtml(row.responsibilities)}">${truncateString(row.responsibilities, 200)}</td>
      <td>${escapeHtml(row.scheduleType)}</td>
      <td>${escapeHtml(address)}</td>
      <td>${escapeHtml(display_salary)}</td>
      <td>${escapeHtml(row.educationRequirements)}</td>
      <td>${escapeHtml(row.experience)}</td>
      <td>${escapeHtml(row.contacts)}</td>
      <td>${escapeHtml(row.rcznContacts)}</td>
    </tr>`;
    i++;
  });
  container.innerHTML = `<table>${html}</table>`;
}

// Отрисовка пагинации
function renderPagination() {
  // Можно реализовать простую пагинацию
  // например, страницы 1..N
  // или кнопки "предыдущая/следующая"
}

// Отрисовка диапазона
function renderRange() {
  const start = (currentPage - 1) * perPage + 1;
  const end = Math.min(start + perPage -1, totalRecords);
  document.getElementById('paginationTop').innerHTML = `Вакансии с ${start} по ${end} из ${totalRecords}`;
  document.getElementById('paginationBottom').innerHTML = document.getElementById('paginationTop').innerHTML;
}

// Обновление сообщения
function updateFilterMessage(total) {
  const messageEl = document.getElementById('filter-message');
  if (total > 0 && hasActiveFilters()) {
    messageEl.innerText = `Найдено ${total} ${declOfNum(total, ['вакансия', 'вакансии', 'вакансий'])} по фильтрам`;
  } else {
    messageEl.innerText = '';
  }
}

// Проверка активных фильтров
function hasActiveFilters() {
  // Реализуйте проверку выбранных фильтров
  return true; // или false
}

// Обработчики фильтров, формы, кнопки сброса
document.querySelector('form').addEventListener('change', () => {
  currentPage = 1;
  loadVacancies();
});
document.getElementById('resetFilters').addEventListener('click', (e) => {
  e.preventDefault();
  // сбросить форму
  document.querySelector('form').reset();
  currentPage = 1;
  loadVacancies();
});

// Инициализация
loadVacancies();

// Вспомогательные функции
function escapeHtml(text) {
  const div = document.createElement('div');
  div.innerText = text;
  return div.innerHTML;
}
function truncateString(str, length) {
  if (str.length <= length) return str;
  return str.slice(0, length) + '...';
}
function declOfNum(n, titles) {
  const cases = [2, 0, 1, 1, 1, 2];
  return titles[(n % 100 > 4 && n % 100 < 20) ? 2 : cases[(n % 10 < 5) ? n % 10 : 5]];
}
</script>
</body>
</html>