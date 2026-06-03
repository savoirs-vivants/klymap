document.addEventListener('DOMContentLoaded', () => {
    const btn     = document.getElementById('user-menu-btn');
    const menu    = document.getElementById('user-menu');
    const wrapper = document.getElementById('user-menu-wrapper');

    if (!btn || !menu) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = !menu.classList.contains('hidden');
        menu.classList.toggle('hidden', isOpen);
        btn.setAttribute('aria-expanded', String(!isOpen));
    });

    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            menu.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            menu.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
});
