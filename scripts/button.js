(function() {
  // Создаем кнопку
  const btn = document.createElement('button');
  btn.id = 'scrollToTopBtn';
  btn.title = 'Вернуться наверх';
  btn.setAttribute('aria-label', 'Вернуться наверх');

  // Добавляем изображение внутрь кнопки
  const img = document.createElement('img');
  img.src = '../resources/back.png'; // укажите правильный путь к изображению
  img.alt = 'Наверх';

  // Устанавливаем размеры кнопки в 5% от ширины экрана
  btn.style.cssText = `
    display: none; /* изначально скрыта */
    position: fixed;
    bottom: 40px;
    right: 40px;
    width: 5vw; /* ширина 5% от ширины окна */
    height: auto; /* высота по содержимому */
    background: transparent;
    border: none;
    padding: 0;
    cursor: pointer;
    z-index: 1000;
  `;

  // Сделать изображение растягивающимся внутри кнопки
  img.style.width = '100%';
  img.style.height = 'auto';

  btn.appendChild(img);
  document.body.appendChild(btn);

  let hideTimeout;

  // Обработчик прокрутки
  window.addEventListener('scroll', () => {
    const scrollY = window.scrollY;
    const viewportHeight = window.innerHeight;
    const scrollThreshold = 0.1 * viewportHeight; // 10% высоты окна

    // Если прокрутка >= 10% высоты окна, показываем кнопку
    if (scrollY >= scrollThreshold) {
      btn.style.display = 'block';

      // Если есть активный таймаут, очищаем его
      if (hideTimeout) {
        clearTimeout(hideTimeout);
      }

      // Устанавливаем таймаут скрытия
      hideTimeout = setTimeout(() => {
        btn.style.display = 'none';
      }, 3000);
    }
  });

  // Обработчик клика
  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
})();