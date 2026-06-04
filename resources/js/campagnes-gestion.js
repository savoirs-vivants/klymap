const csrf = window.__csrfToken;

window.openEditModal = function (id, nom, nbGroupes, dateFin) {
    document.getElementById("edit-id").value = id;
    document.getElementById("edit-nom").value = nom;
    document.getElementById("edit-nb-groupes").value = nbGroupes;
    document.getElementById("edit-date-fin").value = dateFin;
    document.getElementById("edit-error").textContent = "";

    const modal = document.getElementById("modal-edit");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
};

window.closeEditModal = function () {
    const modal = document.getElementById("modal-edit");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
};

document.getElementById("form-edit")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector("[type=submit]");
    btn.disabled = true;
    btn.textContent = "Enregistrement…";

    const id = document.getElementById("edit-id").value;
    const error = document.getElementById("edit-error");
    error.textContent = "";

    try {
        const res = await fetch(`/campagnes/${id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrf,
            },
            body: JSON.stringify({
                nom: document.getElementById("edit-nom").value.trim(),
                nb_groupes: parseInt(
                    document.getElementById("edit-nb-groupes").value,
                    10,
                ),
                date_fin: document.getElementById("edit-date-fin").value,
            }),
        });
        const json = await res.json();

        if (!res.ok) {
            error.textContent = json.message || "Erreur.";
            btn.disabled = false;
            btn.textContent = "Enregistrer";
            return;
        }

        const row = document.querySelector(`[data-campagne-id="${id}"]`);
        if (row) {
            row.querySelector(".campagne-nom-display").textContent =
                json.campagne.nom;
        }
        closeEditModal();
        location.reload();
    } catch {
        error.textContent = "Erreur réseau.";
        btn.disabled = false;
        btn.textContent = "Enregistrer";
    }
});

window.showParticipants = async function (id, nom) {
    document.getElementById("participants-campagne-nom").textContent = nom;
    document.getElementById("participants-list").innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="flex gap-1.5">
                <span class="w-2 h-2 rounded-full bg-slate-300 animate-bounce" style="animation-delay:0s"></span>
                <span class="w-2 h-2 rounded-full bg-slate-300 animate-bounce" style="animation-delay:.15s"></span>
                <span class="w-2 h-2 rounded-full bg-slate-300 animate-bounce" style="animation-delay:.3s"></span>
            </div>
        </div>`;

    const modal = document.getElementById("modal-participants");
    modal.classList.remove("hidden");
    modal.classList.add("flex");

    try {
        const res = await fetch(`/campagnes/${id}/participants`, {
            headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf },
        });
        const data = await res.json();
        const list = document.getElementById("participants-list");

        if (!data.length) {
            list.innerHTML =
                '<p class="text-sm text-slate-400 italic text-center py-6">Aucun participant inscrit.</p>';
            return;
        }

        list.innerHTML = `
            <p class="text-[11px] font-mono font-bold uppercase tracking-widest text-slate-400 mb-3">${data.length} participant${data.length > 1 ? "s" : ""}</p>
            <div class="space-y-1.5">
                ${data
                    .map(
                        (p) => `
                    <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="w-7 h-7 rounded-full bg-[#222a60]/10 flex items-center justify-center text-[#222a60] text-xs font-bold shrink-0">
                            ${p.pseudo.charAt(0).toUpperCase()}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-700 truncate">${p.pseudo}</p>
                            <p class="text-[10px] text-slate-400 font-mono">${p.groupe_label} · ${p.joined_at}</p>
                        </div>
                    </div>
                `,
                    )
                    .join("")}
            </div>`;
    } catch {
        document.getElementById("participants-list").innerHTML =
            '<p class="text-sm text-red-400 italic text-center py-6">Erreur lors du chargement.</p>';
    }
};

window.closeParticipantsModal = function () {
    const modal = document.getElementById("modal-participants");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
};

window.endCampagne = async function (id, nom) {
    if (
        !confirm(
            `Voulez-vous vraiment mettre fin à la campagne "${nom}" dès maintenant ?\n\nLe code de cette campagne sera immédiatement désactivé.`,
        )
    )
        return;

    try {
        const res = await fetch(`/campagnes/${id}/terminer`, {
            method: "PUT",
            headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf },
        });
        const json = await res.json();

        if (!res.ok) {
            alert(json.message || "Erreur lors de la clôture de la campagne.");
            return;
        }
        location.reload();
    } catch {
        alert("Erreur réseau. Réessayez.");
    }
};

window.deleteCampagne = async function (id, nom) {
    if (
        !confirm(
            `Supprimer la campagne "${nom}" et tous ses participants ?\n\nCette action est irréversible.`,
        )
    )
        return;

    try {
        const res = await fetch(`/campagnes/${id}`, {
            method: "DELETE",
            headers: { Accept: "application/json", "X-CSRF-TOKEN": csrf },
        });
        const json = await res.json();

        if (!res.ok) {
            alert(json.message || "Erreur lors de la suppression.");
            return;
        }

        document.querySelector(`[data-campagne-id="${id}"]`)?.remove();

        if (!document.querySelector("[data-campagne-id]")) {
            location.reload();
        }
    } catch {
        alert("Erreur réseau. Réessayez.");
    }
};
