//Показать еще интересные винодельни
document.addEventListener("DOMContentLoaded", function() {
    // Получаем все блоки interestingPreview_cont
    const previewBlocks = document.querySelectorAll('.interestingPreview_cont');
    
    // Скрываем все блоки, кроме первого
    for (let i = 1; i < previewBlocks.length; i++) {
        previewBlocks[i].style.display = 'none';
    }

    // Получаем ссылку "Показать еще"
    const showMoreLink = document.querySelector('.moreinterest');

    // Добавляем обработчик события на клик
    showMoreLink.addEventListener('click', function(event) {
        event.preventDefault(); // предотвращаем переход по ссылке
        
        // Находим второй блок и отображаем его
        if (previewBlocks[1]) {
            previewBlocks[1].style.display = 'grid';
            showMoreLink.style.display = 'none'; // скрываем ссылку "Показать еще"
        }
    });
});

//Показать еще свежие статьи
document.addEventListener("DOMContentLoaded", function() {
    // Получаем все блоки interestingPreview_cont
    const previewBlocks = document.querySelectorAll('.newsPreview_cont');
    
    // Скрываем все блоки, кроме первого
    for (let i = 1; i < previewBlocks.length; i++) {
        previewBlocks[i].style.display = 'none';
    }

    // Получаем ссылку "Показать еще"
    const showMoreLink = document.querySelector('.morearticle');

    // Добавляем обработчик события на клик
    showMoreLink.addEventListener('click', function(event) {
        event.preventDefault(); // предотвращаем переход по ссылке
        
        // Находим второй блок и отображаем его
        if (previewBlocks[1]) {
            previewBlocks[1].style.display = 'grid';
            showMoreLink.style.display = 'none'; // скрываем ссылку "Показать еще"
        }
    });
});

//Показать еще другие винодельни
document.addEventListener("DOMContentLoaded", function() {
    // Получаем все блоки interestingPreview_cont
    const previewBlocks = document.querySelectorAll('.RegionOther_cont');
    
    // Скрываем все блоки, кроме первого
    for (let i = 1; i < previewBlocks.length; i++) {
        previewBlocks[i].style.display = 'none';
    }

    // Получаем ссылку "Показать еще"
    const showMoreLink = document.querySelector('.morewinery');

    // Добавляем обработчик события на клик
    showMoreLink.addEventListener('click', function(event) {
        event.preventDefault(); // предотвращаем переход по ссылке
        
        // Находим второй блок и отображаем его
        if (previewBlocks[1]) {
            previewBlocks[1].style.display = 'grid';
            showMoreLink.style.display = 'none'; // скрываем ссылку "Показать еще"
        }
    });
});

// Проверка формы
document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector('.backForm_cont');

    form.addEventListener('submit', function(event) {
        // Получаем значения полей
        const name = form.querySelector('input[type="text"][placeholder="Ваше имя"]').value.trim();
        const phone = form.querySelector('input[type="tel"][placeholder="Номер телефона"]').value.trim();
        const winery = form.querySelector('input[type="text"][placeholder="Винодельня"]').value.trim();

        // Проверка на пустые поля
        if (!name || !phone || !winery) {
            event.preventDefault(); // Отменяем отправку формы
            alert('Пожалуйста, заполните все поля!');
            return;
        }

        // Проверка формата номера телефона (пример для российского формата)
        const phonePattern = /^\+?[0-9\s()-]{7,15}$/; // Регулярное выражение для проверки номера телефона
        if (!phonePattern.test(phone)) {
            event.preventDefault(); // Отменяем отправку формы
            alert('Пожалуйста, введите корректный номер телефона!');
            return;
        }

        // Если все проверки пройдены, форма будет отправлена
    });
});


