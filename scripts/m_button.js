document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('toggle-filters');
  const filters = document.querySelector('.filters-column');

  if (toggleBtn && filters) {
    toggleBtn.addEventListener('click', function() {
      filters.classList.toggle('show');

      // Меняем текст кнопки в зависимости от состояния
      if (filters.classList.contains('show')) {
        toggleBtn.textContent = 'Скрыть фильтры';
      } else {
        toggleBtn.textContent = 'Показать фильтры';
      }
    });
  }
});