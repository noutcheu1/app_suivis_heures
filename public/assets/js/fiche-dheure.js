// ─── Paramètres URL ───────────────────────────────────────────────────────────
const params   = new URLSearchParams(window.location.search);
const mois     = parseInt(params.get('mois') ?? '0', 10);
const type     = (params.get('type') ?? 'ENFA').toUpperCase();

let periodeFin = null;

// ─── Peuplement select minutes (0, 5, 10 … 55) ───────────────────────────────
(function () {
    const sel = document.getElementById('minute');
    for (let i = 0; i <= 55; i += 5) {
        const opt = document.createElement('option');
        opt.value = opt.textContent = String(i).padStart(2, '0');
        sel.appendChild(opt);
    }
})();

// ─── Écouteurs ────────────────────────────────────────────────────────────────
document.getElementById('signerButton').addEventListener('click', signer);
document.getElementById('saveTime').addEventListener('click', function (e) {
    e.preventDefault();
    ajouterHeure(
        document.getElementById('heure').value,
        document.getElementById('minute').value
    );
});

// ─── Chargement du relevé ─────────────────────────────────────────────────────
async function chargerReleve() {
    let data;
    try {
        const res = await fetch(`/api/intervenants/releve/${ID}?type=${type}&mois=${mois}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        data = await res.json();
    } catch (err) {
        console.error('Erreur chargement relevé:', err);
        if (typeof activerBoutonTelechargement === 'function') activerBoutonTelechargement();
        return;
    }

    periodeFin = data.periode.fin;

    console.group(`[Relevé] ${data.type} — ${data.periode.mois} ${data.periode.anner}`);
    console.log('Période fin      :', data.periode.fin);
    console.log('Intervenant      :', data.intervenant);
    console.log('Familles (nb)    :', data.familles.length);
    console.log('Familles (détail):', data.familles);
    console.log('Jours (nb)       :', data.jours.length);
    console.log('Totaux           :', data.totaux);
    console.log('Signature        :', data.signer);
    console.groupEnd();

    const container = document.getElementById('tableContainer');
    container.innerHTML = '';

    try {
        if (!data.familles || data.familles.length === 0) {
            console.warn('[Relevé] Aucune prestation pour ce mois/type — aucun tableau généré.');
            const msg = document.createElement('p');
            msg.textContent = 'Aucune prestation enregistrée pour ce mois.';
            msg.style.padding = '1rem';
            container.appendChild(msg);
        } else {
            const nbPages = Math.ceil(data.familles.length / 5);
            console.log(`[Relevé] ${data.familles.length} famille(s) → ${nbPages} page(s)`);
            for (let page = 1; page <= nbPages; page++) {
                console.log(`[Relevé] Construction page ${page}…`);
                container.appendChild(buildEnTete(data, page));
                container.appendChild(buildCorps(data, page));
                console.log(`[Relevé] Page ${page} ajoutée au DOM.`);
            }
        }
        renderRecap(data);
        enableSignerButton(data);
    } catch (err) {
        console.error('[Relevé] Erreur rendu tableaux:', err);
        container.textContent = 'Erreur lors du rendu du relevé.';
    }

    if (typeof activerBoutonTelechargement === 'function') activerBoutonTelechargement();
}

// ─── Tableau en-tête intervenant  (id = monTableau0{page}) ───────────────────
function buildEnTete(data, page) {
    const table = document.createElement('table');
    table.id    = `monTableau0${page}`;
    const tbody = document.createElement('tbody');
    table.appendChild(tbody);

    const inter     = data.intervenant ?? {};
    const typeLabel = data.type === 'MENA' ? 'MÉNAGES' : "GARDES D'ENFANTS";
    const titre     = `${typeLabel}  -  RELEVÉ D'HEURES  -  ${data.periode.mois} ${data.periode.anner}`;
    const adresse   = [inter.adresse, inter['ville de résidence']].filter(Boolean).join(', ');
    const tel       = inter['Téléhone'] ?? inter['Telephone'] ?? '';

    // Titre
    tbody.appendChild(mkRow([mkTh(titre, { colSpan: 4, cls: 'no-borders title' })]));

    // Info signature
    tbody.appendChild(mkRow([mkTd(
        "LES FEUILLES D'HEURES DOIVENT ÊTRE SIGNÉES AU PLUS TARD LE 30/31 DU MOIS",
        { colSpan: 4, cls: 'no-borders', style: 'font-size:10px' }
    )]));

    // Nom + Tel (rowSpan=2 pour les deux premières cellules)
    const trNomTel = document.createElement('tr');
    trNomTel.className = 'cell-position-right bold';
    const tdNomLabel = mkTd('Nom :', { cls: 'title' }); tdNomLabel.rowSpan = 2;
    const tdNom      = mkTd(`${inter.nom ?? ''} ${inter.prenom ?? ''}`.trim()); tdNom.rowSpan = 2;
    trNomTel.append(tdNomLabel, tdNom, mkTd('Tel :'), mkTd(tel));
    tbody.appendChild(trNomTel);

    // Adresse (occupe les 2 colonnes restantes sur la même ligne logique)
    const trAdr = document.createElement('tr');
    trAdr.className = 'cell-position-right bold';
    trAdr.append(mkTd('Adresse :'), mkTd(adresse));
    tbody.appendChild(trAdr);

    // Absence
    tbody.appendChild(mkRow([mkTd(
        'Toute absence doit être justifiée',
        { colSpan: 4, cls: 'no-borders', style: 'font-size:10px' }
    )]));

    return table;
}

// ─── Tableau corps jours × familles  (id = monTableau1{page}) ────────────────
function buildCorps(data, page) {
    const table   = document.createElement('table');
    table.id      = `monTableau1${page}`;
    const tbody   = document.createElement('tbody');
    table.appendChild(tbody);

    const offset   = (page - 1) * 5;
    const familles = data.familles.slice(offset, offset + 5);

    // ── Ligne titre "FAMILLES" ───────────────────────────────────────────────
    const trFamTitre = document.createElement('tr');
    trFamTitre.append(
        mkTd('', { cls: 'no-borders' }),
        mkTd('', { cls: 'no-borders' }),
        mkTd('', { cls: 'no-borders' }),
        mkTh('FAMILLES', { colSpan: 5 })
    );
    tbody.appendChild(trFamTitre);

    // ── Ligne noms des familles ──────────────────────────────────────────────
    const trFamNoms = document.createElement('tr');
    trFamNoms.append(mkTd('', { cls: 'no-borders' }), mkTh('Date', { colSpan: 2 }));

    for (let i = 0; i < 5; i++) {
        const fam = familles[i];
        const th  = document.createElement('th');
        if (fam) {
            const ville = fam.numFam === '9998'
                ? 'OCCASIONNELLE'
                : (fam.ville_Famille ?? '').toUpperCase();
            th.innerHTML = `${i + 1}<br>${escHtml(fam.nomFam)}<br><span style="font-weight:normal">${escHtml(ville)}</span>`;
        } else {
            th.innerHTML = `${i + 1}<br>_ _ _ _ _<br>&nbsp;`;
        }
        trFamNoms.appendChild(th);
    }
    tbody.appendChild(trFamNoms);

    // ── Lignes jours ─────────────────────────────────────────────────────────
    let semaineActuelle = -1;
    let cellSemaine     = null;

    data.jours.forEach(jour => {
        const tr    = document.createElement('tr');
        const isDim = jour.jour === 'dimanche';
        if (isDim) tr.className = 'dimanche';

        if (jour.semaine !== semaineActuelle) {
            // Nouveau début de semaine
            cellSemaine         = mkTd(`S${jour.semaine}`);
            cellSemaine.rowSpan = 1;
            semaineActuelle     = jour.semaine;
            tr.appendChild(cellSemaine);
        } else if (!isDim) {
            // Même semaine, pas dimanche : étendre le rowspan
            cellSemaine.rowSpan++;
        } else {
            // Dimanche : cellule vide (fin visuelle de semaine)
            tr.appendChild(mkTd(''));
        }

        tr.append(
            mkTd(jour.jour[0].toUpperCase()),
            mkTd(String(jour.numeroJour))
        );

        for (let i = 0; i < 5; i++) {
            const fam    = familles[i];
            const presta = fam?.prestations?.[jour.date];
            tr.appendChild(mkTd(presta ? presta.join(' - ') : ''));
        }

        tbody.appendChild(tr);
    });

    // ── Total heures par famille ──────────────────────────────────────────────
    const trTotH = document.createElement('tr');
    trTotH.append(mkTd('', { cls: 'no-borders' }), mkTh('TOTAL Heures', { colSpan: 2 }));
    let totalSecondes = 0;
    for (let i = 0; i < 5; i++) {
        const fam = familles[i];
        if (fam) totalSecondes += fam.totalSecondes ?? 0;
        trTotH.appendChild(mkTd(fam ? hm(fam.totalSecondes ?? 0) : ''));
    }
    tbody.appendChild(trTotH);
    tbody.appendChild(ligneVide(8));

    // ── Total km (ENFA uniquement) ────────────────────────────────────────────
    if (data.type === 'ENFA') {
        const trKm = document.createElement('tr');
        trKm.append(
            mkTd('', { cls: 'no-borders' }),
            mkTh('TOTAL Km (avec enfants)', { colSpan: 2, cls: 'no-borders' })
        );
        for (let i = 0; i < 5; i++) {
            const fam = familles[i];
            trKm.appendChild(mkTd(fam ? `${fam.totalKm ?? 0} Km` : ''));
        }
        tbody.appendChild(trKm);
    }

    // ── Total mensuel ─────────────────────────────────────────────────────────
    const trMens = document.createElement('tr');
    trMens.innerHTML = `
        <td class="no-borders"></td>
        <td colspan="2" class="no-borders">TOTAL MENSUEL DES HEURES</td>
        <td>${hm(totalSecondes)}</td>
        <td colspan="2" class="no-borders"></td>
        <td class="no-borders">TOTAL MENSUEL DES KM (avec enfants)</td>
        <td>${escHtml(String(data.totaux?.kmMois ?? 0))}</td>
    `;
    tbody.appendChild(trMens);
    tbody.appendChild(ligneVide(8));

    // ── Ligne signature ───────────────────────────────────────────────────────
    const signer = data.signer ?? {};
    const trSign = document.createElement('tr');
    trSign.innerHTML = `
        <td colspan="4" class="no-borders"></td>
        <td colspan="2" class="no-borders">Date : ${escHtml(signer.date ?? '')}</td>
        <td colspan="2" class="no-borders">Signature : <i>${escHtml(signer.nom ?? '')}</i></td>
    `;
    tbody.appendChild(trSign);

    // ── Mention légale ────────────────────────────────────────────────────────
    tbody.appendChild(mkRow([mkTh(
        "VOUS DEVEZ NOUS INDIQUER LE NOMBRE TOTAL D'HEURES QUE VOUS AVEZ EFFECTUÉES DANS LE MOIS POUR TOUT VOS EMPLOYEURS QUI VOUS PAYENT DIRECTEMENT SOIT TOTAL DE VOTRE MOIS : 01h25***",
        { colSpan: 8 }
    )]));

    return table;
}

// ─── Récap mobile ─────────────────────────────────────────────────────────────
function renderRecap(data) {
    const tbody        = document.getElementById('familleRecap');
    const spanSigner   = document.getElementById('signerMobile');
    const kmParcourue  = document.getElementById('kmParcourue');
    const heureCumuler = document.getElementById('heureCumuler');

    if (tbody) tbody.innerHTML = '';
    let totalSecondes = 0;

    (data.familles ?? []).forEach(fam => {
        if (!tbody) return;
        const tr = document.createElement('tr');
        const tdN = mkTd(fam.nomFam ?? '');
        const tdH = mkTd(hm(fam.totalSecondes ?? 0));
        tr.append(tdN, tdH);
        tbody.appendChild(tr);
        totalSecondes += fam.totalSecondes ?? 0;
    });

    if (spanSigner)   spanSigner.textContent   = data.signer?.etat ? 'signé' : 'non signé';
    if (kmParcourue)  kmParcourue.textContent  = data.totaux?.kmMois ?? 0;
    if (heureCumuler) heureCumuler.textContent = hm(totalSecondes);
}

function enableSignerButton(_data) {
    // Réservé : désactiver le bouton si déjà signé, etc.
}

// ─── Appels API ───────────────────────────────────────────────────────────────
async function signer() {
    if (!periodeFin) { alert('Données non encore chargées.'); return; }
    try {
        const res = await fetch(
            `/api/intervenants/${ID}/signer?type=${type}&periode_fin=${periodeFin}`,
            { method: 'GET', credentials: 'same-origin' }
        );
        const result = await res.json();
        if (!result.success) {
            alert('Erreur : ' + result.message);
        } else {
            chargerReleve();
        }
    } catch (err) {
        console.error(err);
        alert('Erreur serveur');
    }
}

async function ajouterHeure(heure, minute) {
    if (!periodeFin) { alert('Données non encore chargées.'); return; }
    try {
        const res = await fetch(`/api/intervenants/${ID}/ajout_heure_de_hors`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                heure:      Number(heure),
                minute:     Number(minute),
                periodeFin,
                type:       'MENA'
            })
        });
        const result = await res.json();
        if (!result.success) alert('Erreur : ' + result.message);
    } catch (err) {
        console.error(err);
        alert('Erreur serveur');
    }
}

// ─── Utilitaires DOM ──────────────────────────────────────────────────────────
function mkTd(text, { colSpan, rowSpan, cls, style } = {}) {
    return _mkCell('td', text, { colSpan, rowSpan, cls, style });
}

function mkTh(text, { colSpan, rowSpan, cls, style } = {}) {
    return _mkCell('th', text, { colSpan, rowSpan, cls, style });
}

function _mkCell(tag, text, { colSpan, rowSpan, cls, style } = {}) {
    const el = document.createElement(tag);
    el.textContent = text;
    if (colSpan) el.colSpan   = colSpan;
    if (rowSpan) el.rowSpan   = rowSpan;
    if (cls)     el.className = cls;
    if (style)   el.style.cssText = style;
    return el;
}

function mkRow(cells) {
    const tr = document.createElement('tr');
    cells.forEach(c => tr.appendChild(c));
    return tr;
}

function ligneVide(colSpan) {
    const td = document.createElement('td');
    td.colSpan   = colSpan;
    td.className = 'no-borders';
    td.textContent = ' ';
    return mkRow([td]);
}

function hm(secondes) {
    const h = Math.floor(secondes / 3600);
    const m = Math.floor((secondes % 3600) / 60);
    return `${h}h${String(m).padStart(2, '0')}`;
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// ─── Démarrage ────────────────────────────────────────────────────────────────
chargerReleve();
