
/* ----- VERIFICATION DATE ----- */
const dateInput = document.getElementById('date');
const today = new Date();

// Format YYYY-MM-DD
const formatDate = (d) => d.toISOString().split('T')[0];

// Date maximum = aujourd'hui
dateInput.max = formatDate(today);

// Date minimum = 7 jours avant aujourd'hui
const minDate = new Date();
minDate.setDate(today.getDate() - 7);
dateInput.min = formatDate(minDate);


const form = document.getElementById("form_ajout_heure");
const familleSelect = document.getElementById("famille");
const nomRemplacementInput = document.getElementById("nomRemplacement");
const typeSelect = document.getElementById("type");
const trajetInput = document.getElementById("trajetInput");

// Récupérer les paramètres GET
const params = new URLSearchParams(window.location.search);

function formatHours(hoursBDD) {
    return hoursBDD.split(':');
}

function setEdit(data) {
    console.log(data);
    const heureAvant = formatHours(data.heureDebutPresta);
    const heureFin   = formatHours(data.heureFinPresta);

    form.heureDebut.value = heureAvant[0];
    form.heureFin.value   = heureFin[0];

    form.minuteDebut.value = heureAvant[1];
    form.minuteFin.value   = heureFin[1];

    if (data.numFam == 9998) {
        familleSelect.value = 0;
        nomRemplacementInput.value = data.nomFam ?? "";
    } else {
        familleSelect.value = data.numFam ?? '0'; // Filtrer 9998 = inconue
    }
    form.date.value = data.datePresta;

    trajetInput.value = data.kmAvecEnfant;
    typeSelect.value = data.typePresta;
    handleFamilleChange();
}

async function getInfoData(id_edit) {
    try {
        const route = "/api/intervenants/horaire/" + ID + "?id_edit=" + id_edit;
        const res = await fetch(route, {
            method: "GET",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin" // garde la session Symfony
        });
        const result = await res.json();
        if (!result.success) {
            showModal({
                title: "Erreur",
                body: "Erreur serveur : " + result.message,
                buttons: [{text: "Ok", class: "btn btn-danger", dismiss: true}]
            });
        } else {
            setEdit(result.data);
        }
    } catch (err) {
        console.error(err);
        showModal({
            title: "Erreur",
            body: "Erreur serveur : " + err,
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
                buttons: [{text: "Ok", class: "btn btn-success", onClick: () => window.location.reload()}]
            });
        } else {
            showModal({
                title: "Erreur",
                body: "Erreur serveur : " + result.message,
                buttons: [{text: "Ok", class: "btn btn-danger", dismiss: true}]
            });
        }
    } catch (err) {
        console.error(err);
        showModal({
            title: "Erreur",
            body: "Erreur serveur : " + err,
            buttons: [{text: "Ok", class: "btn btn-danger", dismiss: true}]
        });;
    }
}

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    let nomRemplacement = familleSelect.options[familleSelect.selectedIndex].dataset.nom;

    if ( familleSelect.selectedIndex == '1') {
       nomRemplacement = nomRemplacementInput.value; 
    }

    const id = params.get('edite') ?? -1;

    // Récupère les données
    const data = {
        id: id,
        date: form.date.value,
        famille: familleSelect.options[familleSelect.selectedIndex].dataset.num,
        nomRemplacement: nomRemplacement,
        type: typeSelect.value,
        trajet: trajetInput.value || 0,
        heureDebut: form.heureDebut.value,
        minuteDebut: form.minuteDebut.value,
        heureFin: form.heureFin.value,
        minuteFin: form.minuteFin.value
    };
    

    
    // Modal de confirmation simple
    const recap = `<h3> Veuillez vérifier la véracité des informations : </h3> 
        Date: ${data.date}
        Famille/Menage: ${nomRemplacement}
        Type: ${typeSelect.value}
        Début: ${data.heureDebut}:${data.minuteDebut}
        Fin: ${data.heureFin}:${data.minuteFin}
        Trajet: ${data.trajet} km
    `;

    const confirmed = await confirmModal(recap);

    if (!confirmed) return;

    if (params.has('edite')) {
        let route = "/api/intervenants/horaire/modifier/" + ID;
        sendData(route, data);
    } else {
        let route = "/api/intervenants/horaire/ajouter/" + ID;
        sendData(route, data);
    }
    
});



function handleFamilleChange() {
    const selectFamille = document.getElementById("famille");
    const prestations = selectFamille.selectedOptions[0].dataset.prestations;

    // Gestion du select type
    const select = document.getElementById("type");

    if (prestations === "MENA") {
        select.value = "MENA";
        Array.from(select.options).forEach(option => {
            if (option.value !== "MENA" && option.value !== "") {
                option.hidden = true;
            }
        });
    } else if (prestations === "ENFA") {
        select.value = "ENFA";
        Array.from(select.options).forEach(option => {
            if (option.value !== "ENFA" && option.value !== "") {
                option.hidden = true;
            }
        });
    } else {
        select.value = "";
        Array.from(select.options).forEach(option => {
            if (option.value !== "") {
                option.hidden = false;
            }
        });
    }

    updateVisibility();
}

// Fonction centralisée pour gérer toutes les visibilités
function updateVisibility() {
    const selectFamille = document.getElementById("famille");
    const selectType = document.getElementById("type");

    console.log('test,', selectFamille)

    // Gestion du champ nomRemplacement
    if (selectFamille.value == "0") {
        document.getElementById('nomRemplacement').hidden = false;
        document.getElementById('nomRemplacement').required = true;
    } else {
        document.getElementById('nomRemplacement').hidden = true;
        document.getElementById('nomRemplacement').required = false;
    }

    // Gestion des champs liés à ENFA
    if (selectType.value === 'ENFA') {
        document.getElementById('trajetTxt').hidden = false;
        document.getElementById('trajetInput').hidden = false;
    } else {
        document.getElementById('trajetTxt').hidden = true;
        document.getElementById('trajetInput').hidden = true;
    }
}

// Ajout des écouteurs
document.getElementById("famille").addEventListener("change", handleFamilleChange);
document.getElementById('type').addEventListener('change', updateVisibility);

// Fonction pour peupler les selects de temps
function populateTime(selectElement, max, step = 1) {
    for (let i = 0; i <= max; i += step) {
        
        const option = document.createElement('option');
        option.value = i.toString().padStart(2, '0');
        option.textContent = i.toString().padStart(2, '0');
        
        if (selectElement.id == "heureDebut" && i.toString().padStart(2, '0') == "99" ) {
            option.selected = true;
        } else if (selectElement.id == "minuteDebut" && i.toString().padStart(2, '0') == "99" ) {
            option.selected = true;
        } else if (selectElement.id == "heureFin" && i.toString().padStart(2, '0') == "99" ) {
            option.selected = true;
        } else if (selectElement.id == "minuteFin" && i.toString().padStart(2, '0') == "99" ) {
            option.selected = true;
        }
        
        selectElement.appendChild(option);
    }
}

const heureDebut = document.getElementById('heureDebut');
const minuteDebut = document.getElementById('minuteDebut');
const heureFin = document.getElementById('heureFin');
const minuteFin = document.getElementById('minuteFin');

populateTime(heureDebut, 23);
populateTime(heureFin, 23);
populateTime(minuteDebut, 59, 5);
populateTime(minuteFin, 59, 5);

// Initialisation
updateVisibility();

// Vérifier si 'edite' n'existe pas
if (!params.has('edite')) {
    handleFamilleChange();
} else {
    getInfoData(params.get('edite'));
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