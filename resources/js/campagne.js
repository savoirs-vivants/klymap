const modal = document.getElementById("modal-campagne");
if (!modal) {
} else {
    const btnOpen = document.getElementById("btn-lancer-campagne");
    const btnClose = document.getElementById("modal-campagne-close");
    const backdrop = document.getElementById("modal-campagne-backdrop");
    const form = document.getElementById("form-campagne");
    const step1 = document.getElementById("campagne-step1");
    const step2 = document.getElementById("campagne-step2");
    const codeDisplay = document.getElementById("campagne-code-display");

    function openModal() {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
        step1.classList.remove("hidden");
        step2.classList.add("hidden");
        form.reset();
        document.getElementById("campagne-error").textContent = "";
    }

    function closeModal() {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }

    btnOpen?.addEventListener("click", openModal);
    btnClose?.addEventListener("click", closeModal);
    backdrop?.addEventListener("click", closeModal);

    form?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const btn = form.querySelector("[type=submit]");
        btn.disabled = true;
        btn.textContent = "Création…";

        const errorEl = document.getElementById("campagne-error");
        errorEl.textContent = "";

        const data = {
            nom: document.getElementById("campagne-nom").value.trim(),
            nb_groupes: parseInt(
                document.getElementById("campagne-nb-groupes").value,
                10,
            ),
            date_fin: document.getElementById("campagne-date-fin").value,
            _token: document.querySelector('meta[name="csrf-token"]').content,
        };

        try {
            const res = await fetch("/campagne", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(data),
            });

            const json = await res.json();

            if (!res.ok) {
                errorEl.textContent =
                    json.message || "Erreur lors de la création.";
                btn.disabled = false;
                btn.textContent = "Créer la campagne";
                return;
            }

            codeDisplay.textContent = json.code;
            document.getElementById("campagne-groups-info").textContent =
                json.nb_groupes > 0
                    ? `${json.nb_groupes} groupe${json.nb_groupes > 1 ? "s" : ""} (${Array.from({ length: json.nb_groupes }, (_, i) => String.fromCharCode(65 + i)).join(", ")})`
                    : "Aucun groupe (mode individuel)";

            step1.classList.add("hidden");
            step2.classList.remove("hidden");
        } catch {
            errorEl.textContent = "Erreur réseau. Réessayez.";
            btn.disabled = false;
            btn.textContent = "Créer la campagne";
        }
    });

    document
        .getElementById("campagne-copy-btn")
        ?.addEventListener("click", () => {
            navigator.clipboard.writeText(codeDisplay.textContent).then(() => {
                const btn = document.getElementById("campagne-copy-btn");
                btn.textContent = "Copié !";
                setTimeout(() => {
                    btn.textContent = "Copier";
                }, 2000);
            });
        });
}
