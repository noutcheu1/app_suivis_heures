// ─── Paramètres URL ───────────────────────────────────────────────────────────
const params   = new URLSearchParams(window.location.search);
const mois     = parseInt(params.get('mois') ?? '0', 10);
const type     = (params.get('type') ?? 'ENFA').toUpperCase();

console.log('=== [fiche-dheure.js] INIT ===');
console.log('[PARAMS] URL complète :', window.location.href);
console.log('[PARAMS] mois offset  :', mois);
console.log('[PARAMS] type         :', type);
console.log('[PARAMS] ID intervenant (depuis Twig) :', typeof ID !== 'undefined' ? ID : '⚠️ ID NON DÉFINI !');

if (typeof ID === 'undefined') {
    alert('[fiche-dheure.js] ⚠️ La variable ID est indéfinie ! Vérifier le bloc <script> dans sheet.html.twig');
}

let periodeFin = null;

// ─── Peuplement select minutes (0, 5, 10 … 55) ───────────────────────────────
(function () {
    const sel = document.getElementById('minute');
    if (!sel) {
        console.error('[DOM] ⚠️ #minute introuvable dans le DOM');
    } else {
        console.log('[DOM] #minute trouvé, peuplement des options…');
        for (let i = 0; i <= 55; i += 5) {
            const opt = document.createElement('option');
            opt.value = opt.textContent = String(i).padStart(2, '0');
            sel.appendChild(opt);
        }
    }
})();

// ─── Écouteurs ────────────────────────────────────────────────────────────────
const _signerBtn = document.getElementById('signerButton');
const _saveBtn   = document.getElementById('saveTime');
console.log('[DOM] #signerButton :', _signerBtn ? 'trouvé' : '⚠️ INTROUVABLE');
console.log('[DOM] #saveTime     :', _saveBtn   ? 'trouvé' : '⚠️ INTROUVABLE');
console.log('[DOM] #tableContainer :', document.getElementById('tableContainer') ? 'trouvé' : '⚠️ INTROUVABLE');
console.log('[DOM] #familleRecap   :', document.getElementById('familleRecap')   ? 'trouvé' : '⚠️ INTROUVABLE');
console.log('[DOM] #heureCumuler   :', document.getElementById('heureCumuler')   ? 'trouvé' : '⚠️ INTROUVABLE');
console.log('[DOM] #donwload       :', document.getElementById('donwload')       ? 'trouvé' : '⚠️ INTROUVABLE');

if (_signerBtn) _signerBtn.addEventListener('click', signer);
if (_saveBtn)   _saveBtn.addEventListener('click', function (e) {
    e.preventDefault();
    ajouterHeure(
        document.getElementById('heure').value,
        document.getElementById('minute').value
    );
});

// ─── Chargement du relevé ─────────────────────────────────────────────────────
async function chargerReleve() {
    const apiUrl = `/api/intervenants/releve/${ID}?type=${type}&mois=${mois}`;
    console.log('[API] ▶ GET', apiUrl);

    let data;
    try {
        const res = await fetch(apiUrl);
        console.log('[API] ◀ status HTTP :', res.status, res.statusText);
        if (!res.ok) {
            const body = await res.text();
            console.error('[API] Corps de la réponse d\'erreur :', body);
            alert(`[chargerReleve] Erreur HTTP ${res.status}\n${body}`);
            throw new Error(`HTTP ${res.status}`);
        }
        const rawText = await res.text();
        console.log('[API] Corps brut (500 premiers chars) :', rawText.slice(0, 500));
        try {
            data = JSON.parse(rawText);
        } catch (jsonErr) {
            console.error('[API] ⚠️ Réponse non-JSON :', rawText.slice(0, 200));
            alert('[chargerReleve] La réponse API n\'est pas du JSON valide !\n' + rawText.slice(0, 200));
            throw jsonErr;
        }
    } catch (err) {
        console.error('[chargerReleve] Erreur chargement relevé :', err);
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
    if (!container) {
        console.error('[DOM] ⚠️ #tableContainer est NULL — impossible d\'insérer les tableaux !');
        alert('[chargerReleve] #tableContainer introuvable dans le DOM !');
        if (typeof activerBoutonTelechargement === 'function') activerBoutonTelechargement();
        return;
    }
    console.log('[DOM] #tableContainer trouvé, innerHTML initial :', container.innerHTML.slice(0, 100));
    container.innerHTML = '';

    try {
        console.log('[Rendu] data.familles :', data.familles);
        console.log('[Rendu] data.jours    :', data.jours?.length, 'jours');

        if (!data.familles || data.familles.length === 0) {
            console.warn('[Rendu] ⚠️ Aucune famille pour ce mois/type → aucun tableau généré.');
            const msg = document.createElement('p');
            msg.textContent = 'Aucune prestation enregistrée pour ce mois.';
            msg.style.padding = '1rem';
            container.appendChild(msg);
        } else {
            console.log(`[Rendu] ${data.familles.length} famille(s) → 1 tableau`);
            container.appendChild(buildEnTete(data));
            container.appendChild(buildCorps(data));
        }
        console.log('[Rendu] container.innerHTML final (200 chars) :', container.innerHTML.slice(0, 200));
        renderRecap(data);
        enableSignerButton(data);
    } catch (err) {
        console.error('[Rendu] ⚠️ Erreur lors du rendu des tableaux :', err);
        alert('[chargerReleve] Erreur rendu :\n' + err.message + '\n\n' + err.stack);
        container.textContent = 'Erreur lors du rendu du relevé.';
    }

    if (typeof activerBoutonTelechargement === 'function') activerBoutonTelechargement(data);
}

// ─── Tableau en-tête intervenant ─────────────────────────────────────────────
function buildEnTete(data) {
    const page = 1;
    console.log(`[buildEnTete] intervenant=`, data.intervenant);
    const table = document.createElement('table');
    table.id    = `monTableau0${page}`;
    const tbody = document.createElement('tbody');
    table.appendChild(tbody);

    const inter     = data.intervenant ?? {};
    const typeLabel = data.type === 'MENA' ? 'MÉNAGES' : "GARDES D'ENFANTS";
    const titre     = `${typeLabel}  -  RELEVÉ D'HEURES  -  ${data.periode.mois} ${data.periode.anner}`;
    const adresse   = [inter.adresse, inter['ville de résidence']].filter(Boolean).join(', ');
    const tel       = inter['Téléhone'] ?? inter['Telephone'] ?? '';
    console.log(`[buildEnTete] titre="${titre}", adresse="${adresse}", tel="${tel}"`);

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

// ─── Tableau corps jours × familles ──────────────────────────────────────────
function buildCorps(data) {
    console.log(`[buildCorps] jours=${data.jours?.length}, familles=${data.familles?.length}`);
    const table   = document.createElement('table');
    table.id      = 'monTableau11';
    const tbody   = document.createElement('tbody');
    table.appendChild(tbody);

    const familles  = data.familles;
    const nFam      = familles.length;
    const totalCols = 3 + nFam;

    // ── Ligne titre "FAMILLES" ───────────────────────────────────────────────
    const trFamTitre = document.createElement('tr');
    trFamTitre.append(
        mkTd('', { cls: 'no-borders' }),
        mkTd('', { cls: 'no-borders' }),
        mkTd('', { cls: 'no-borders' }),
        mkTh('FAMILLES', { colSpan: nFam })
    );
    tbody.appendChild(trFamTitre);

    // ── Ligne noms des familles ──────────────────────────────────────────────
    const trFamNoms = document.createElement('tr');
    trFamNoms.append(mkTd('', { cls: 'no-borders' }), mkTh('Date', { colSpan: 2 }));

    familles.forEach((fam, i) => {
        const th    = document.createElement('th');
        const ville = fam.numFam === '9998'
            ? 'OCCASIONNELLE'
            : (fam.ville_Famille ?? '').toUpperCase();
        th.innerHTML = `${i + 1}<br>${escHtml(fam.nomFam)}<br><span style="font-weight:normal">${escHtml(ville)}</span>`;
        trFamNoms.appendChild(th);
    });
    tbody.appendChild(trFamNoms);

    // ── Lignes jours ─────────────────────────────────────────────────────────
    let semaineActuelle = -1;
    let cellSemaine     = null;

    data.jours.forEach(jour => {
        const tr    = document.createElement('tr');
        const isDim = jour.jour === 'dimanche';
        if (isDim) tr.className = 'dimanche';

        if (jour.semaine !== semaineActuelle) {
            cellSemaine         = mkTd(`S${jour.semaine}`);
            cellSemaine.rowSpan = 1;
            semaineActuelle     = jour.semaine;
            tr.appendChild(cellSemaine);
        } else if (!isDim) {
            cellSemaine.rowSpan++;
        } else {
            tr.appendChild(mkTd(''));
        }

        tr.append(
            mkTd(jour.jour[0].toUpperCase()),
            mkTd(String(jour.numeroJour))
        );

        familles.forEach(fam => {
            const presta = fam?.prestations?.[jour.date];
            tr.appendChild(mkTd(presta ? presta.join(', ') : ''));
        });

        tbody.appendChild(tr);
    });

    // ── Total heures par famille ──────────────────────────────────────────────
    const trTotH = document.createElement('tr');
    trTotH.append(mkTd('', { cls: 'no-borders' }), mkTh('TOTAL Heures', { colSpan: 2 }));
    let totalSecondes = 0;
    familles.forEach(fam => {
        totalSecondes += fam.totalSecondes ?? 0;
        trTotH.appendChild(mkTd(hm(fam.totalSecondes ?? 0)));
    });
    tbody.appendChild(trTotH);
    tbody.appendChild(ligneVide(totalCols));

    // ── Total km (ENFA uniquement) ────────────────────────────────────────────
    if (data.type === 'ENFA') {
        const trKm = document.createElement('tr');
        trKm.append(
            mkTd('', { cls: 'no-borders' }),
            mkTh('TOTAL Km (avec enfants)', { colSpan: 2, cls: 'no-borders' })
        );
        familles.forEach(fam => {
            trKm.appendChild(mkTd(`${fam.totalKm ?? 0} Km`));
        });
        tbody.appendChild(trKm);
    }

    // ── Total mensuel ─────────────────────────────────────────────────────────
    const trMens = document.createElement('tr');
    trMens.append(
        mkTd('', { cls: 'no-borders' }),
        mkTd('TOTAL MENSUEL DES HEURES', { colSpan: 2, cls: 'no-borders' }),
        mkTd(hm(totalSecondes), { colSpan: Math.max(1, nFam - 2) }),
        mkTd('TOTAL MENSUEL DES KM', { cls: 'no-borders' }),
        mkTd(String(data.totaux?.kmMois ?? 0))
    );
    tbody.appendChild(trMens);
    tbody.appendChild(ligneVide(totalCols));

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
    const heureDehorsTxt = data.heureDehors ?? '_ _ _ h _ _';
    const thMention = mkTh(
        `VOUS DEVEZ NOUS INDIQUER LE NOMBRE TOTAL D'HEURES QUE VOUS AVEZ EFFECTUÉES DANS LE MOIS POUR TOUT VOS EMPLOYEURS QUI VOUS PAYENT DIRECTEMENT SOIT TOTAL DE VOTRE MOIS : `,
        { colSpan: 8 }
    );
    thMention.id = 'totalMoisHors';
    thMention.textContent = `VOUS DEVEZ NOUS INDIQUER LE NOMBRE TOTAL D'HEURES QUE VOUS AVEZ EFFECTUÉES DANS LE MOIS POUR TOUT VOS EMPLOYEURS QUI VOUS PAYENT DIRECTEMENT SOIT TOTAL DE VOTRE MOIS : ${heureDehorsTxt} ***`;
    tbody.appendChild(mkRow([thMention]));

    return table;
}

// ─── Récap mobile ─────────────────────────────────────────────────────────────
function renderRecap(data) {
    console.log('[renderRecap] ▶ début');
    const tbody        = document.getElementById('familleRecap');
    const spanSigner   = document.getElementById('signerMobile');
    const kmParcourue  = document.getElementById('kmParcourue');
    const heureCumuler = document.getElementById('heureCumuler');
    console.log('[renderRecap] #familleRecap :', tbody   ? 'OK' : '⚠️ MANQUANT');
    console.log('[renderRecap] #signerMobile :', spanSigner ? 'OK' : '⚠️ MANQUANT');
    console.log('[renderRecap] #kmParcourue  :', kmParcourue  ? 'OK' : '⚠️ MANQUANT');
    console.log('[renderRecap] #heureCumuler :', heureCumuler ? 'OK' : '⚠️ MANQUANT');

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
    console.log('[renderRecap] ✅ total', hm(totalSecondes), '| signé:', data.signer?.etat);
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
                type:       TYPE_PRESTA
            })
        });
        const result = await res.json();
        if (result.success) {
            await chargerReleve();
        } else {
            alert('Erreur : ' + result.message);
        }
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
console.log('[fiche-dheure.js] ▶ chargerReleve() appelé');
chargerReleve().then(() => {
    console.log('[fiche-dheure.js] ✅ chargerReleve() terminé');
    const container = document.getElementById('tableContainer');
    console.log('[fiche-dheure.js] tableContainer.children.length après chargement :', container?.children.length ?? 'N/A');
    console.log('[fiche-dheure.js] monTableau01 dans le DOM :', !!document.getElementById('monTableau01'));
    console.log('[fiche-dheure.js] monTableau11 dans le DOM :', !!document.getElementById('monTableau11'));
}).catch(err => {
    console.error('[fiche-dheure.js] ❌ chargerReleve() rejeté :', err);
    alert('Erreur inattendue dans chargerReleve() :\n' + err.message);
});
