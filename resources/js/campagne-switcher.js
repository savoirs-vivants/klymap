document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const post = (url, body = {}) => fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(body),
    });

    document.querySelectorAll('[data-dropdown-toggle]').forEach((btn) => {
        const wrapper = btn.closest('[data-dropdown-wrapper]');
        const menu = wrapper?.querySelector('[data-dropdown-menu]');
        if (!menu) return;

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
    });

    document.querySelectorAll('[data-campagne-activate]').forEach((item) => {
        item.addEventListener('click', () => {
            const id = item.dataset.campagneActivate;
            const url = id ? `/campagnes/${id}/activer` : '/campagnes/desactiver';
            post(url).then((r) => r.ok && location.reload());
        });
    });

    document.querySelectorAll('[data-participant-switch]').forEach((item) => {
        item.addEventListener('click', () => {
            post('/session/changer', { campagne_id: item.dataset.participantSwitch })
                .then((r) => r.ok && location.reload());
        });
    });

    document.querySelectorAll('[data-mode-libre-toggle]').forEach((item) => {
        item.addEventListener('click', () => {
            post('/session/mode-libre').then((r) => r.ok && location.reload());
        });
    });
});
