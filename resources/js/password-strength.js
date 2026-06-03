const LEVELS = [
    { label: 'Très faible', class: 'strength--1', width: '20%' },
    { label: 'Faible',      class: 'strength--2', width: '40%' },
    { label: 'Moyen',       class: 'strength--3', width: '60%' },
    { label: 'Fort',        class: 'strength--4', width: '80%' },
    { label: 'Très fort',   class: 'strength--5', width: '100%' },
];

const RULES = {
    length:    (v) => v.length >= 8,
    lowercase: (v) => /[a-z]/.test(v),
    uppercase: (v) => /[A-Z]/.test(v),
    number:    (v) => /[0-9]/.test(v),
    symbol:    (v) => /[^a-zA-Z0-9]/.test(v),
};

function score(value) {
    if (!value) return -1;
    let passed = Object.values(RULES).filter((fn) => fn(value)).length;
    if (value.length >= 12 && passed === 5) return 4;
    if (passed >= 4) return 3;
    if (passed === 3) return 2;
    if (passed === 2) return 1;
    return 0;
}

function initStrengthMeter(input) {
    const bar    = input.closest('.form-group').querySelector('[data-strength-bar]');
    const fill   = bar?.querySelector('[data-strength-fill]');
    const label  = bar?.querySelector('[data-strength-label]');
    const ruleEls = input.closest('.form-group').querySelectorAll('[data-rule]');

    if (!bar) return;

    input.addEventListener('input', () => {
        const value = input.value;
        const level = score(value);

        fill.className  = 'strength-bar-fill';
        label.textContent = '';
        fill.style.width  = '0%';

        if (level >= 0) {
            const l = LEVELS[level];
            fill.classList.add(l.class);
            fill.style.width    = l.width;
            label.textContent   = l.label;
        }

        ruleEls.forEach((el) => {
            const ruleFn = RULES[el.dataset.rule];
            el.classList.toggle('rule-ok', !!ruleFn?.(value));
        });
    });
}

function initTogglePassword() {
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const id    = btn.dataset.togglePassword;
            const input = document.getElementById(id);
            if (!input) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            btn.querySelector('.icon-eye')?.classList.toggle('hidden', isHidden);
            btn.querySelector('.icon-eye-off')?.classList.toggle('hidden', !isHidden);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-input]').forEach(initStrengthMeter);
    initTogglePassword();
});
