const step1El = document.getElementById("join-step1");
const step2El = document.getElementById("join-step2");
const errorEl = document.getElementById("join-error");

let campagneId = null;
let nbGroupes = 0;
let campagneNom = "";

document.getElementById("form-code")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = e.target.querySelector("[type=submit]");
    btn.disabled = true;
    btn.textContent = "Vérification…";
    errorEl.textContent = "";

    const code = document
        .getElementById("input-code")
        .value.trim()
        .toUpperCase();

    try {
        const res = await fetch("/code/valider", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            body: JSON.stringify({ code }),
        });

        const json = await res.json();

        if (!res.ok) {
            errorEl.textContent = json.error || "Code invalide.";
            btn.disabled = false;
            btn.textContent = "Valider le code";
            return;
        }

        campagneId = json.campagne_id;
        nbGroupes = json.nb_groupes;
        campagneNom = json.nom;

        document.getElementById("join-campagne-nom").textContent = campagneNom;
        buildGroupSelect(nbGroupes);

        step1El.classList.add("hidden");
        step2El.classList.remove("hidden");
        errorEl.textContent = "";
    } catch {
        errorEl.textContent = "Erreur réseau. Réessayez.";
        btn.disabled = false;
        btn.textContent = "Valider le code";
    }
});

function buildGroupSelect(nb) {
    const wrapper = document.getElementById("groupe-select-wrapper");
    const select = document.getElementById("input-groupe");

    if (nb === 0) {
        wrapper.classList.add("hidden");
        select.innerHTML = '<option value="0">—</option>';
        return;
    }

    wrapper.classList.remove("hidden");
    select.innerHTML = "";

    for (let i = 1; i <= nb; i++) {
        const opt = document.createElement("option");
        opt.value = i;
        opt.textContent = "Groupe " + String.fromCharCode(64 + i);
        select.appendChild(opt);
    }
}

document
    .getElementById("form-register")
    ?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector("[type=submit]");
        btn.disabled = true;
        btn.textContent = "Inscription…";
        errorEl.textContent = "";

        const pseudo = document.getElementById("input-pseudo").value.trim();
        const idGroupe = parseInt(
            document.getElementById("input-groupe").value,
            10,
        );

        try {
            const res = await fetch("/session/rejoindre", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]',
                    ).content,
                },
                body: JSON.stringify({
                    campagne_id: campagneId,
                    pseudo,
                    id_groupe: idGroupe,
                }),
            });

            const json = await res.json();

            if (!res.ok) {
                errorEl.textContent =
                    json.message || "Erreur lors de l'inscription.";
                btn.disabled = false;
                btn.textContent = "Rejoindre";
                return;
            }

            window.location.href = json.redirect;
        } catch {
            errorEl.textContent = "Erreur réseau. Réessayez.";
            btn.disabled = false;
            btn.textContent = "Rejoindre";
        }
    });

document.getElementById("btn-back-step1")?.addEventListener("click", () => {
    step2El.classList.add("hidden");
    step1El.classList.remove("hidden");
    errorEl.textContent = "";
});
