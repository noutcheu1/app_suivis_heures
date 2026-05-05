const params = new URLSearchParams(window.location.search);
const mois = Number(params.get("mois")) ?? 0;
const type = params.get("type") ?? "ENFA";

const signerButton = document.getElementById('signerButton');
const id = document.getElementById('signerButton');

const flecheGauche = document.getElementById('signerButton');

const minute = document.getElementById('minute');
populateTime(minute, 59, 5);
function populateTime(selectElement, max, step = 1) {
    for (let i = 0; i <= max; i += step) {
      const option = document.createElement('option');
      option.value = i.toString().padStart(2, '0');
      option.textContent = i.toString().padStart(2, '0');
      selectElement.appendChild(option);
    }
  }

document.getElementById('saveTime').addEventListener('click', function(e) {
    e.preventDefault(); // empêche le comportement normal du lien

    const heure = document.getElementById('heure').value;
    const minute = document.getElementById('minute').value;

    ajout_heure(heure, minute);
});

let periodeFin = null;

signerButton.addEventListener("click", signer);

async function chargerReleve() {
    const response = await fetch(`/api/intervenants/releve/${ID}?type=${type}&mois=${mois}`);
    const data = await response.json();

    console.log(data);

    const tableContainer = document.getElementById('tableContainer');
    tableContainer.innerHTML = '';

    periodeFin = data.periode.fin;

    const maxLoop =  Number(data.familles.length / 5);

    console.log(data.familles);
    for (let i = 0; i < maxLoop; i++) {
        const tableHeader = document.createElement("table");
        tableHeader.id =  `monTableau0${i+1}`;

        /* ------ Corps du tableau -------- */
        const table = document.createElement("table");
        table.id = `monTableau1${i+1}`;
        const offset = i*5;

        renderHeader(table, data, offset);
        renderDays(table, data, offset);
        renderTotals(table, data, offset);

        /* ------ insert tableau ---------*/
        renderTableHeader(tableHeader, data);
        tableContainer.appendChild(tableHeader);
        tableContainer.appendChild(table);
        
    }

    renderRecap(data);
    enableSignerButton(data);
}

async function signer() {
    try {
        const route = `/api/intervenants/${ID}/signer?type=${type}&periode_fin=${periodeFin}`;
        const res = await fetch(route, {
            method: "GET",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin" // garde la session Symfony
        });
        const result = await res.json();

        if (!result.success) {
            alert("Erreur : " + result.message);
        } 
    } catch (err) {
        console.error(err);
        alert("Erreur serveur " + err);
    }
}

async function ajout_heure(heure, minute) {
    try {
        const type = "mena"
        const route = `/api/intervenants/${ID}/ajout_heure_de_hors`;
        const res = await fetch(route, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin", // garde la session Symfony
            body: JSON.stringify({ heure, minute, periodeFin, type})
        });
        const result = await res.json();
        if (!result.success) {
            alert("Erreur : " + result.message);
        }
    } catch (err) {
        console.error(err);
        alert("Erreur serveur " + err);
    }
}

function renderTableHeader(table, data) {
    const tbody = document.createElement("tbody");

    /* --- Ligne 1 : Titre --- */
    const trTitle = document.createElement("tr");
    const thTitle = document.createElement("th");

    thTitle.colSpan = 4;
    thTitle.className = "no-borders title";
    let type = "GARDES D'ENFANTS "

    if (data.type == "MENA") {
        type = "MÉNAGES"
    }

    thTitle.textContent = `${type}  - RELEVE D'HEURES - ${data.periode.mois} ${data.periode.anner}`;

    trTitle.appendChild(thTitle);
    tbody.appendChild(trTitle);

    /* --- Ligne 2 : Info signature --- */
    const trInfo = document.createElement("tr");
    const tdInfo = document.createElement("td");

    tdInfo.colSpan = 4;
    tdInfo.className = "no-borders";
    tdInfo.style.fontSize = "10px";
    tdInfo.textContent =
    "LES FEUILLES D'HEURES DOIVENT ETRE SIGNER AU PLUS TARD LE 30/31 DU MOIS";

    trInfo.appendChild(tdInfo);
    tbody.appendChild(trInfo);

    /* --- Ligne 3 : Nom / Tel --- */
    const trNomTel = document.createElement("tr");
    trNomTel.className = "cell-position-right bold";

    const tdNomLabel = document.createElement("td");
    tdNomLabel.rowSpan = 2;
    tdNomLabel.className = "title";
    tdNomLabel.textContent = "Nom :";

    const tdNom = document.createElement("td");
    tdNom.rowSpan = 2;
    tdNom.textContent = `${data.intervenant.nom} ${data.intervenant.prenom}`;

    const tdTelLabel = document.createElement("td");
    tdTelLabel.textContent = "Tel :";

    const tdTel = document.createElement("td");
    tdTel.textContent = data.intervenant.Téléhone

    trNomTel.append(tdNomLabel, tdNom, tdTelLabel, tdTel);
    tbody.appendChild(trNomTel);

    /* --- Ligne 4 : Adresse --- */
    const trAdresse = document.createElement("tr");
    trAdresse.className = "cell-position-right bold";

    const tdAdresseLabel = document.createElement("td");
    tdAdresseLabel.textContent = "Adresse";

    const tdAdresse = document.createElement("td");
    tdAdresse.textContent = `${data.intervenant.adresse}, ${data.intervenant["ville de résidence"]}`;

    trAdresse.append(tdAdresseLabel, tdAdresse);
    tbody.appendChild(trAdresse);

    /* --- Ligne 5 : Absence --- */
    const trAbsence = document.createElement("tr");
    trAbsence.className = "cell-position-right";

    const tdAbsence = document.createElement("td");
    tdAbsence.colSpan = 4;
    tdAbsence.className = "no-borders";
    tdAbsence.style.fontSize = "10px";
    tdAbsence.textContent = "Toute absence doit être justifiée";

    trAbsence.appendChild(tdAbsence);
    tbody.appendChild(trAbsence);

    /* --- Ajout au tableau --- */
    table.appendChild(tbody);

}

function renderHeader(table, data, offset) {
    const tr1 = document.createElement('tr');
    tr1.innerHTML = `
        <td class="no-borders"></td>
        <td class="no-borders"></td>
        <td class="no-borders"></td>
        <th colspan="5">FAMILLES</th>
    `;
    table.appendChild(tr1);

    const tr2 = document.createElement('tr');
    tr2.innerHTML = `<td class="no-borders"></td><th colspan="2">Date</th>`;

    for (let i = 0; i < 5; i++) {
        const th = document.createElement('th');
        const fam = data.familles[i+offset];

        if (fam) {
            th.innerHTML = `
                ${i + 1}<br>
                ${fam.nomFam}<br>
                <span style="font-weight:normal">
                    ${fam.numFam === '9998'
                        ? 'OCCASIONNELLE'
                        : (fam.ville_Famille ?? '').toUpperCase()}
                </span>
            `;
        } else {
            // Colonne vide mais présente
            th.innerHTML = `${i + 1}<br>_ _ _ _ _ <br><span>&nbsp;</span>`;
        }

        tr2.appendChild(th);
    }


    table.appendChild(tr2);
}


function renderDays(table, data, offset) {
    let last_semaine = -1;
    let last_semaine_dom = null;
    data.jours.forEach(jour => {
        
        const tr = document.createElement('tr');

        let add = [];

        let isDimanche = jour.jour === 'dimanche';

        if (isDimanche) {
            tr.classList.add('dimanche');
        }
        if (last_semaine != jour.semaine) {
            last_semaine_dom = document.createElement("td");
            last_semaine_dom.rowSpan = 1; 
            last_semaine_dom.textContent = `S${jour.semaine}`;

            add.push(last_semaine_dom);
            last_semaine = jour.semaine;

        } else if(!isDimanche) {
            last_semaine_dom.rowSpan +=1;
        } else {
            last_semaine_dom = document.createElement("td");
            add.push(last_semaine_dom);
        }

        let td_jour = document.createElement("td");
        td_jour.textContent = jour.jour[0].toUpperCase();
        let td_numero_jour = document.createElement("td");
        td_numero_jour.textContent = jour.numeroJour;
        
        add.push(td_jour, td_numero_jour);

        tr.append(...add);

        for (let i = 0; i < 5; i++) {
            const td = document.createElement('td');
            const fam = data.familles[i+offset];


            if (fam) {
                const presta = fam.prestations[jour.date];
                if (presta) {
                    td.textContent = presta.join(' - ');
                }
            }

            tr.appendChild(td);
        }


        table.appendChild(tr);
    });
}

function emptyRow(sizeCol) {
    
    const row = document.createElement('tr');
    row.innerHTML = `<td class="no-borders" colspan="${sizeCol}" > </td>`;

    return row;
}

function renderTotals(table, data, offset) {
    const trHeure = document.createElement('tr');
    trHeure.innerHTML = `<td class="no-borders"></td><th colspan="2">TOTAL Heures</th>`;
    let totalFiche = 0;

    for (let i = 0; i < 5; i++) {
        const fam = data.familles[i+offset];
        let tdContent = '';

        if (fam) {
            totalFiche += fam.totalSecondes;
            tdContent = secondesEnHeuresMinutes(fam.totalSecondes);
        }

        const td = document.createElement('td');
        td.textContent = tdContent;
        trHeure.appendChild(td);
    }


    table.appendChild(trHeure);
    table.appendChild(emptyRow(5));

    if (data.type === 'ENFA') {
        const trKm = document.createElement('tr');
        trKm.innerHTML = `<td class="no-borders" ></td><th class="no-borders" colspan="2">TOTAL Km (avec enfants) </th>`;
        for (let i = 0; i < 5; i++) {
            const fam = data.familles[i+offset];
            const td = document.createElement('td');

            if (fam) {
                td.textContent = `${fam.totalKm} Km`;
            } else {
                td.textContent = ''; // cellule vide si pas de famille
            }

            trKm.appendChild(td);
        }

        table.appendChild(trKm);
    
    }
    const trTotaux = document.createElement('tr');
    const temps = secondesEnHeuresMinutes(totalFiche);


    trTotaux.innerHTML = `
        <td colspan="" class="no-borders"></td>
        <td colspan="2" class="no-borders"> TOTAL MENSUEL DES HEURES </td>
        <td colspan=""> ${temps} </td>
        <td colspan="2"  class="no-borders"></td>
        <td class="no-borders"> TOTAL MENSUEL DES KM (avec enfants) </td>
        <td colspan=""> ${data.totaux.kmMois} </td>
    `;

    table.appendChild(trTotaux);
    table.appendChild(emptyRow(5));

    const trSign = document.createElement('tr');
    trSign.innerHTML = `
        <td colspan="4" class="no-borders" ></td>
        <td colspan="2" class="no-borders" >Date : ${data.signer.date}</td>
        <td colspan="2" class="no-borders" >Signature : <i>${data.signer.nom}</i></td>
    `;


    table.appendChild(trSign);

    const legal = document.createElement('tr');


    legal.innerHTML = "<th colspan=8> VOUS DEVEZ NOUS INDIQUER LE NOMBRE TOTAL D'HEURES QUE VOUS AVEZ EFFECTUÉES DANS LE MOIS POUR TOUT VOS EMPLOYEURS QUI VOUS PAYENT DIRECTEMENT SOIT TOTAL DE VOTRE MOIS : 01h25***  </th>"
    table.appendChild(legal);
}

function renderRecap(data) {
    const spanSigner = document.getElementById('signerMobile');
    const kmParcourue = document.getElementById('kmParcourue');
    const heureCumuler = document.getElementById('heureCumuler');

    const familleRecap = document.getElementById('familleRecap');

    let totalSecondes = 0;

    /* ----- RECAP FIN ------- */

    data.familles.forEach((famille) => {
        console.log(famille);

        const tr = document.createElement('tr');
        const nomFamille = document.createElement('td');
        const heureFamille = document.createElement('td');

        heureFamille.innerHTML = secondesEnHeuresMinutes(famille.totalSecondes);
        nomFamille.innerHTML = famille.nomFam;

        tr.appendChild(nomFamille);
        tr.appendChild(heureFamille);

        familleRecap.appendChild(tr);

        totalSecondes += famille.totalSecondes;
    });

    let phraseSinger = "signer";
    if (!data.signer.etat) {
        phraseSinger = `non signer`
    } 

    spanSigner.textContent = phraseSinger;
    kmParcourue.textContent = data.totaux.kmMois;
    heureCumuler.textContent = secondesEnHeuresMinutes(totalSecondes);

}

function enableSignerButton(data) {
    let ok = "o";
    if (data.signer.etat) {
        
    }
}

function secondesEnHeuresMinutes(secondes) {
    const heures = Math.floor(secondes / 3600); // 3600 secondes dans 1 heure
    const minutes = Math.floor((secondes % 3600) / 60); // reste divisé par 60
    return `${heures}h${minutes.toString().padStart(2, '0')}`;
}

chargerReleve(mois);
