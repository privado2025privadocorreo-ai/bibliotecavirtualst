'use strict';

const navigationToggle = document.querySelector('.nav-toggle');
const navigation = document.querySelector('#site-navigation');

if (navigationToggle && navigation) {
    navigationToggle.addEventListener('click', () => {
        const open = navigation.classList.toggle('is-open');
        navigationToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        navigationToggle.querySelector('.sr-only').textContent = open
            ? 'Cerrar navegación'
            : 'Abrir navegación';
    });

    document.addEventListener('click', (event) => {
        if (!navigation.contains(event.target) && !navigationToggle.contains(event.target)) {
            navigation.classList.remove('is-open');
            navigationToggle.setAttribute('aria-expanded', 'false');
            navigationToggle.querySelector('.sr-only').textContent = 'Abrir navegación';
        }
    });
}

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

const patronSearch = document.querySelector('#usuario_busqueda');
const patronId = document.querySelector('#usuario_id');
const patronSuggestions = document.querySelector('#usuario_sugerencias');
const patronLimitText = document.querySelector('#usuario_limite');
const patronNoResults = document.querySelector('#usuario_sin_resultados');
const bookLimitText = document.querySelector('#libros_limite');
const bookOptions = Array.from(document.querySelectorAll('[data-book-option]'));
const patronOptions = patronSuggestions
    ? Array.from(patronSuggestions.querySelectorAll('[data-user-id]'))
    : [];
const autocomplete = patronSearch?.closest('.autocomplete');

if (patronSearch && patronId && patronSuggestions && autocomplete) {
    const loanLimit = Number(autocomplete.dataset.userLimit || 0);
    let activeLoans = null;
    let highlightedIndex = -1;

    const closeSuggestions = () => {
        patronSuggestions.hidden = true;
        patronSearch.setAttribute('aria-expanded', 'false');
        patronSearch.removeAttribute('aria-activedescendant');
        highlightedIndex = -1;
        patronOptions.forEach((option) => {
            option.classList.remove('is-active');
            option.setAttribute('aria-selected', 'false');
        });
    };

    const updateBookLimit = () => {
        if (activeLoans === null) {
            bookOptions.forEach((option) => {
                option.checked = false;
                option.disabled = true;
                option.closest('.book-choice')?.classList.remove('is-selected');
                option.closest('.book-choice')?.classList.add('is-disabled');
            });
            bookLimitText.textContent = 'Selecciona primero el usuario responsable.';
            return;
        }

        const remaining = Math.max(0, loanLimit - activeLoans);
        const selectedCount = bookOptions.filter((option) => option.checked).length;
        bookOptions.forEach((option) => {
            option.disabled = !option.checked && selectedCount >= remaining;
            option.closest('.book-choice')?.classList.toggle('is-disabled', option.disabled);
            option.closest('.book-choice')?.classList.toggle('is-selected', option.checked);
        });

        bookLimitText.textContent = `Tiene ${activeLoans} de ${loanLimit} ejemplares activos; puede llevar ${remaining} más. Seleccionados: ${selectedCount}.`;
    };

    const selectPatron = (option) => {
        patronId.value = option.dataset.userId;
        patronSearch.value = option.textContent.trim();
        activeLoans = Number(option.dataset.activeLoans || 0);
        closeSuggestions();
        patronLimitText.textContent = `Tiene ${activeLoans} de ${loanLimit} ejemplares activos.`;
        updateBookLimit();
    };

    const showSuggestions = () => {
        const term = patronSearch.value.trim().toLocaleLowerCase();
        let visibleCount = 0;
        patronOptions.forEach((option) => {
            const matches = option.textContent.toLocaleLowerCase().includes(term)
                && visibleCount < 8;
            option.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });
        patronSuggestions.hidden = visibleCount === 0;
        patronNoResults.hidden = visibleCount > 0 || term === '';
        patronSearch.setAttribute('aria-expanded', visibleCount > 0 ? 'true' : 'false');
        highlightedIndex = -1;
        patronOptions.forEach((option) => {
            option.classList.remove('is-active');
            option.setAttribute('aria-selected', 'false');
        });
    };

    patronSearch.addEventListener('focus', showSuggestions);
    patronSearch.addEventListener('input', () => {
        patronId.value = '';
        activeLoans = null;
        patronLimitText.textContent = 'Selecciona una coincidencia para ver el límite disponible.';
        updateBookLimit();
        showSuggestions();
    });

    patronSearch.addEventListener('keydown', (event) => {
        const visibleOptions = patronOptions.filter((option) => !option.hidden);
        if (event.key === 'Escape') {
            closeSuggestions();
            return;
        }
        if (visibleOptions.length === 0) {
            return;
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            highlightedIndex = highlightedIndex === -1
                ? (direction === 1 ? 0 : visibleOptions.length - 1)
                : (highlightedIndex + direction + visibleOptions.length) % visibleOptions.length;
            visibleOptions.forEach((option, index) => {
                const active = index === highlightedIndex;
                option.classList.toggle('is-active', active);
                option.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            patronSearch.setAttribute('aria-activedescendant', visibleOptions[highlightedIndex].id);
        } else if (event.key === 'Enter' && !patronSuggestions.hidden) {
            event.preventDefault();
            if (highlightedIndex >= 0) {
                selectPatron(visibleOptions[highlightedIndex]);
            } else if (visibleOptions.length === 1) {
                selectPatron(visibleOptions[0]);
            }
        }
    });

    patronOptions.forEach((option) => {
        option.addEventListener('click', () => selectPatron(option));
    });

    document.addEventListener('click', (event) => {
        if (!autocomplete.contains(event.target)) {
            closeSuggestions();
        }
    });

    bookOptions.forEach((option) => option.addEventListener('change', updateBookLimit));

    const selectedOption = patronOptions.find((option) => option.dataset.userId === patronId.value);
    if (selectedOption) {
        activeLoans = Number(selectedOption.dataset.activeLoans || 0);
        updateBookLimit();
    } else {
        updateBookLimit();
    }
}
