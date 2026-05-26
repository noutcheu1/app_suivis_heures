
/* ----- VERIFICATION DATE ----- */
const dateInput = document.getElementById('date');
const today = new Date();

// Format YYYY-MM-DD
const formatDate = (d) => d.toISOString().split('T')[0];

// Date minimum = 250 jours avant aujourd'hui
const minDate = new Date();
minDate.setDate(today.getDate() - 250);
dateInput.min = formatDate(minDate);


const form = document.getElementById("form_ajout_heure");
const familleSelect = document.getElementById("famille");
const nomRemplacementInput = document.getElementById("nomRemplacement");
const familleOccaContainer = document.getElementById("familleOccaContainer");
const familleOccaSearch = document.getElementById("familleOccaSearch");
const familleOccaSuggestions = document.getElementById("familleOccaSuggestions");
const typeSelect = document.getElementById("type");

// Récupérer les paramètres GET
const params = new URLSearchParams(window.location.search);

// Helper : affecte les selects heure + minute depuis une chaîne "HH:MM"
function setTimeSelects(heureId, minuteId, hhmm) {
    const [hh, mm] = (hhmm ?? '').split(':');
    const hSel = document.getElementById(heureId);
    const mSel = document.getElementById(minuteId);
    if (hSel && hh !== undefined) hSel.value = hh;
    if (mSel && mm !== undefined) {
        // Arrondir au multiple de 5 le plus proche
        const mVal = String(Math.round(parseInt(mm || '0') / 5) * 5).padStart(2, '0');
        mSel.value = mVal === '60' ? '55' : mVal;
    }
}

function setEdit(data) {
    console.log(data);
    setTimeSelects('heureDebut', 'minuteDebut', data.heureDebutPresta ?? '');
    setTimeSelects('heureFin',   'minuteFin',   data.heureFinPresta   ?? '');

    if (data.numFam == 9998 || data.numFam == null) {
        familleSelect.value = 0;
        nomRemplacementInput.value = data.nomFam ?? "";
        familleOccaSearch.value = data.nomFam ?? "";
    } else {
        familleSelect.value = data.numFam ?? '0';
    }
    form.date.value = data.datePresta;

    typeSelect.value = data.typePresta;

    const trajetInput = document.getElementById('trajet');
    if (trajetInput) trajetInput.value = data.kmAvecEnfant ?? '';

    handleFamilleChange();
}

async function getInfoData(id_edit) {
    try {
        const route = "/heures-mvc/get/" + id_edit;
        const res = await fetch(route, {
            method: "GET",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin" // garde la session Symfony
        });
        const result = await res.json();
        if (!result.success) {
            showModal({
                title: "Erreur",
                body: "Erreur  : " + result.message,
                buttons: [{text: "Ok", class: "btn btn-danger", dismiss: true}]
            });
        } else if (result.data.verrouille) {
            showModal({
                title: "Mois clôturé",
                body: "Cette prestation appartient à un mois passé et ne peut plus être modifiée.",
                buttons: [{text: "Retour", class: "btn btn-secondary", onClick: () => history.back()}]
            });
            document.querySelector('button[type="submit"]').disabled = true;
            document.querySelectorAll('input, select').forEach(el => el.disabled = true);
            setEdit(result.data);
        } else {
            setEdit(result.data);
        }
    } catch (err) {
        console.error(err);
        showModal({
            title: "Erreur",
            body: "Erreur  : " + err,
            buttons: [{text: "Ok", class: "btn btn-danger", dismiss: true}]
        });
    }
}

async function sendData(route, data) {
    // Envoi à l'API
    console.log(data);
    try {
        const res = await fetch(route, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data),
            credentials: "same-origin" // garde la session Symfony
        });
        const result = await res.json();
        if (result.success) {
            showModal({
                title: "Succès",
                body: "La saisie a été enregistrée avec succès !",
                buttons: [{text: "Ok", class: "btn btn-success", onClick: () => {
                    if (typeof PLANNING_URL !== 'undefined' && PLANNING_URL) {
                        window.location.href = PLANNING_URL;
                    } else {
                        window.location.reload();
                    }
                }}]
            });
        } else if (result.locked) {
            showModal({
                title: "Mois clôturé",
                body: "Ce mois est clôturé — la prestation ne peut plus être modifiée ni supprimée.",
                buttons: [{text: "Ok", class: "btn btn-secondary", dismiss: true}]
            });
        } else {
            showModal({
                title: "Erreur",
                body: "Erreur  : " + result.error,
                buttons: [{text: "Ok", class: "btn btn-danger", dismiss: true}]
            });
        }
    } catch (err) {
        console.error(err);
        showModal({
            title: "Erreur",
            body: "Erreur  : " + err,
            buttons: [{text: "Ok", class: "btn btn-danger", dismiss: true}]
        });;
    }
}

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const selectedOption = familleSelect.options[familleSelect.selectedIndex];
    const famValue       = familleSelect.value;
    const famNum         = selectedOption?.dataset.num ?? null;
    const famPrests      = selectedOption?.dataset.prestations ?? '';

    // Garde : famille non occasionnelle sans prestation proposer → bloqué
    const isOccasionnelle = (famValue === '0' || famValue === '');
    if (!isOccasionnelle && famNum && !famPrests) {
        showModal({
            title: 'Non autorisé',
            body: 'Vous n\'êtes pas assigné à cette famille. Vous ne pouvez saisir des heures que pour vos familles ou en tant que famille occasionnelle.',
            buttons: [{ text: 'Fermer', class: 'btn btn-danger', dismiss: true }]
        });
        return;
    }

    let nomRemplacement = selectedOption?.dataset.nom ?? '';
    if (isOccasionnelle) {
        nomRemplacement = nomRemplacementInput.value.trim();
        if (!nomRemplacement) {
            showModal({
                title: 'Champ manquant',
                body: 'Veuillez choisir une famille dans la liste.',
                buttons: [{ text: 'Fermer', class: 'btn btn-secondary', dismiss: true }]
            });
            return;
        }
    }

    const id = params.get('edite') ?? -1;

    // Lire les selects heure/minute séparément
    const heureDebutH = document.getElementById('heureDebut').value;
    const heureDebutM = document.getElementById('minuteDebut').value;
    const heureFinH   = document.getElementById('heureFin').value;
    const heureFinM   = document.getElementById('minuteFin').value;

    if (!heureDebutH || !heureFinH) {
        showModal({
            title: 'Heures manquantes',
            body: 'Veuillez renseigner l\'heure de début et l\'heure de fin.',
            buttons: [{ text: 'Fermer', class: 'btn btn-danger', dismiss: true }]
        });
        return;
    }

    const debutMinutes = parseInt(heureDebutH) * 60 + parseInt(heureDebutM || '0');
    const finMinutes   = parseInt(heureFinH)   * 60 + parseInt(heureFinM   || '0');

    if (finMinutes <= debutMinutes) {
        showModal({
            title: 'Intervalle invalide',
            body: `L'heure de fin (${heureFinH}h${heureFinM}) doit être strictement supérieure à l'heure de début (${heureDebutH}h${heureDebutM}).`,
            buttons: [{ text: 'Corriger', class: 'btn btn-danger', dismiss: true }]
        });
        return;
    }

    const trajetInput = document.getElementById('trajet');
    const data = {
        id: id,
        date: form.date.value,
        famille: famNum,
        nomRemplacement: nomRemplacement,
        type: typeSelect.value,
        heureDebut:  heureDebutH,
        minuteDebut: heureDebutM,
        heureFin:    heureFinH,
        minuteFin:   heureFinM,
        trajet: (trajetInput && trajetInput.value) ? parseFloat(trajetInput.value) : 0,
    };

    // Modal de confirmation simple
    const recap = `<h3> Veuillez vérifier la véracité des informations : </h3>
        Date: ${data.date}
        Famille/Menage: ${nomRemplacement}
        Type: ${typeSelect.value}
        Début: ${data.heureDebut}h${data.minuteDebut}
        Fin: ${data.heureFin}h${data.minuteFin}
    `;

    const confirmed = await confirmModal(recap);

    if (!confirmed) return;
    if (params.has('edite')) {
        let route = "/heures-mvc/modifier/" + id;
        sendData(route, data);
    } else {
        let route = "/heures-mvc/ajouter";
        sendData(route, data);
    }

});



// Sélection d'une famille → pré-sélectionne le type selon proposer
function handleFamilleChange() {
    const selectFamille = document.getElementById("famille");
    const prestations   = selectFamille.selectedOptions[0]?.dataset.prestations ?? '';
    const typeSelect    = document.getElementById("type");
    const types         = prestations ? prestations.split(',').filter(Boolean) : [];

    if (types.length === 1) {
        // Un seul type proposé pour cette famille : sélection automatique
        typeSelect.value = types[0];
        Array.from(typeSelect.options).forEach(o => {
            if (o.value) o.hidden = o.value !== types[0];
        });
    } else {
        // Plusieurs types ou inconnu : tout afficher, forcer le choix
        Array.from(typeSelect.options).forEach(o => { o.hidden = false; });
        if (types.length === 0) typeSelect.value = '';
    }

    updateVisibility();
}

// Sélection d'un type → filtre les familles qui ont ce type dans proposer
function filterFamillesByType() {
    const type         = document.getElementById("type").value;
    const selectFamille = document.getElementById("famille");

    Array.from(selectFamille.options).forEach(o => {
        if (!o.value || o.value === '0') { o.hidden = false; return; }
        if (!type) { o.hidden = false; return; }
        const prests = o.dataset.prestations ? o.dataset.prestations.split(',').filter(Boolean) : [];
        o.hidden = prests.length > 0 && !prests.includes(type);
    });

    // Si la famille sélectionnée est maintenant masquée, réinitialiser
    const cur = selectFamille.selectedOptions[0];
    if (cur && cur.hidden) selectFamille.value = '';

    updateVisibility();
}

// Fonction centralisée pour gérer toutes les visibilités
function updateVisibility() {
    const selectFamille = document.getElementById("famille");
    const selectType    = document.getElementById("type");

    // Champ famille occasionnelle : champ de recherche avec suggestions
    if (selectFamille.value == "0") {
        familleOccaContainer.hidden = false;
        familleOccaSearch.focus();
    } else {
        familleOccaContainer.hidden = true;
        familleOccaSuggestions.style.display = 'none';
        familleOccaSearch.value = '';
        nomRemplacementInput.value = '';
    }

    // Champ km trajet : visible uniquement pour Garde d'enfants (ENFA)
    const trajetContainer = document.getElementById('trajetContainer');
    if (trajetContainer) {
        trajetContainer.hidden = (selectType.value !== 'ENFA');
        if (trajetContainer.hidden) {
            const trajetInput = document.getElementById('trajet');
            if (trajetInput) trajetInput.value = '';
        }
    }
}

// Écouteurs
document.getElementById("famille").addEventListener("change", handleFamilleChange);
document.getElementById("type").addEventListener("change", filterFamillesByType);

// ── Autocomplete famille occasionnelle ─────────────────────────────────────
familleOccaSearch.addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    nomRemplacementInput.value = this.value.trim(); // autorise aussi la saisie libre

    if (!query) {
        familleOccaSuggestions.style.display = 'none';
        return;
    }

    const matches = (typeof FAMILLES_OCC !== 'undefined' ? FAMILLES_OCC : [])
        .filter(f => f.toLowerCase().includes(query))
        .slice(0, 10);

    if (matches.length === 0) {
        familleOccaSuggestions.style.display = 'none';
        return;
    }

    familleOccaSuggestions.innerHTML = '';
    matches.forEach(label => {
        const item = document.createElement('div');
        item.textContent = label;
        item.style.cssText = 'padding:10px 14px;cursor:pointer;font-size:14px;border-bottom:1px solid var(--border);transition:background .15s;';
        item.addEventListener('mouseenter', () => { item.style.background = 'var(--bg-secondary)'; });
        item.addEventListener('mouseleave', () => { item.style.background = ''; });
        item.addEventListener('mousedown', (e) => {
            e.preventDefault(); // évite le blur avant la sélection
            familleOccaSearch.value = label;
            nomRemplacementInput.value = label;
            familleOccaSuggestions.style.display = 'none';
        });
        familleOccaSuggestions.appendChild(item);
    });
    familleOccaSuggestions.style.display = 'block';
});

familleOccaSearch.addEventListener('blur', () => {
    setTimeout(() => { familleOccaSuggestions.style.display = 'none'; }, 150);
});

familleOccaSearch.addEventListener('keydown', (e) => {
    const items = familleOccaSuggestions.querySelectorAll('div');
    const active = familleOccaSuggestions.querySelector('.occ-active');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        const next = active ? active.nextElementSibling : items[0];
        if (active) active.classList.remove('occ-active');
        if (next) { next.classList.add('occ-active'); next.style.background = 'var(--bg-secondary)'; }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        const prev = active ? active.previousElementSibling : items[items.length - 1];
        if (active) active.classList.remove('occ-active');
        if (prev) { prev.classList.add('occ-active'); prev.style.background = 'var(--bg-secondary)'; }
    } else if (e.key === 'Enter' && active) {
        e.preventDefault();
        familleOccaSearch.value = active.textContent;
        nomRemplacementInput.value = active.textContent;
        familleOccaSuggestions.style.display = 'none';
    } else if (e.key === 'Escape') {
        familleOccaSuggestions.style.display = 'none';
    }
});

// Initialisation
updateVisibility();

if (params.has('edite')) {
    getInfoData(params.get('edite'));
} else {
    const famParam  = params.get('famille');
    const typeParam = params.get('type');
    const dateParam = params.get('date');
    const hdebParam = params.get('hdeb');
    const hfinParam = params.get('hfin');
    console.log({famParam, typeParam, dateParam, hdebParam, hfinParam});
    if (famParam || typeParam) {
        // Pré-remplissage depuis le planning — valeurs fixées directement,
        // sans déclencher les filtres en cascade.
        Array.from(typeSelect.options).forEach(o => { o.hidden = false; });
        if (typeParam) typeSelect.value = typeParam;

        if (famParam) {
            Array.from(familleSelect.options).forEach(o => {
                if (o.value === famParam) o.hidden = false;
            });
            familleSelect.value = famParam;
        }

        if (dateParam) document.getElementById('date').value = dateParam;

        if (hdebParam) setTimeSelects('heureDebut', 'minuteDebut', hdebParam);
        if (hfinParam) setTimeSelects('heureFin',   'minuteFin',   hfinParam);

        updateVisibility();
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        handleFamilleChange();
    }
}


/* -------------- MODAL --------------------*/

function showModal({title = "Info", body = "", buttons = []}) {
    const modalEl = document.getElementById('modalMessage');
    const modalTitle = modalEl.querySelector('#modalTitle');
    const modalBody = modalEl.querySelector('#modalBody');
    const modalFooter = modalEl.querySelector('#modalFooter');

    modalTitle.textContent = title;
    modalBody.innerHTML = body;

    // Reset footer, on garde le bouton Fermer
    modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>';

    buttons.forEach(btn => {
        const buttonEl = document.createElement('button');
        buttonEl.textContent = btn.text;
        buttonEl.className = btn.class || "btn btn-primary";
        if (btn.dismiss) buttonEl.setAttribute('data-bs-dismiss', 'modal');
        if (btn.onClick) buttonEl.addEventListener('click', btn.onClick);
        modalFooter.appendChild(buttonEl);
    });

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function confirmModal(message) {
    return new Promise((resolve) => {
        showModal({
            title: "Confirmez",
            body: message.replace(/\n/g, '<br>'),
            buttons: [
                {text: "Annuler", class: "btn btn-secondary", onClick: () => resolve(false), dismiss: true},
                {text: "Confirmer", class: "btn btn-primary", onClick: () => resolve(true), dismiss: true}
            ]
        });
    });
}
