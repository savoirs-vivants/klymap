window.openModal = function (id) {
    document.getElementById(id)?.classList.remove('hidden');
};

window.closeModal = function (id) {
    document.getElementById(id)?.classList.add('hidden');
};

window.openEditModal = function (user) {
    const form = document.getElementById('form-edit');
    form.action = `/backoffice/utilisateurs/${user.id}`;

    form.querySelector('[name="firstname"]').value   = user.firstname;
    form.querySelector('[name="name"]').value        = user.name;
    form.querySelector('[name="email"]').value       = user.email;
    form.querySelector('[name="role"]').value        = user.role;
    form.querySelector('[name="password"]').value    = '';
    form.querySelector('[name="password_confirmation"]').value = '';

    openModal('modal-edit');
}

window.openDeleteModal = function (id, name) {
    document.getElementById('form-delete').action = `/backoffice/utilisateurs/${id}`;
    document.getElementById('delete-name').textContent = name;
    openModal('modal-delete');
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.modal-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.add('hidden');
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay:not(.hidden)').forEach((m) => m.classList.add('hidden'));
        }
    });
});
