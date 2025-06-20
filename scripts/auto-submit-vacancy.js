document.addEventListener('DOMContentLoaded', function() {
    const vacancyInput = document.querySelector('input[name="vacancy"]');
    const form = vacancyInput ? vacancyInput.closest('form') : null;
    if (vacancyInput && form) {
        let debounceTimeout;

        vacancyInput.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(function() {
                form.submit();
            }, 1000); // задержка в миллисекундах
        });
    }
});