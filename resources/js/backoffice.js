window.openModal = function (id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    el.classList.add('flex');
};

window.closeModal = function (id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    el.classList.remove('flex');
};

window.openUserEditModal = function (userDataString) {
    const user = JSON.parse(userDataString);

    const modal = document.getElementById('modal-edit');
    if (!modal) { console.error('modal-edit not found'); return; }

    const form = document.getElementById('form-edit-user');
    if (!form) { console.error('form-edit-user not found'); return; }

    form.setAttribute('action', `/backoffice/utilisateurs/${user.id}`);

    const set = (name, val) => {
        const el = form.querySelector(`[name="${name}"]`);
        if (el) el.value = val ?? '';
    };

    set('firstname', user.firstname);
    set('name', user.name);
    set('email', user.email);
    set('role', user.role);
    set('password', '');
    set('password_confirmation', '');

    window.openModal('modal-edit');
};

window.openDeleteModal = function (id, name) {
    const form = document.getElementById('form-delete');
    if (form) form.action = `/backoffice/utilisateurs/${id}`;
    const nameEl = document.getElementById('delete-name');
    if (nameEl) nameEl.textContent = name;
    window.openModal('modal-delete');
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.flex').forEach((m) => {
                m.classList.add('hidden');
                m.classList.remove('flex');
            });
        }
    });
});
