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
// Relevé signé → plus aucune modification possible (cases verrouillées).
let RELEVE_SIGNED = false;

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

if (_signerBtn) _signerBtn.addEventListener('click', ouvrirSignConfirm);
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
    RELEVE_SIGNED = !!(data.signer && data.signer.etat);

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
            // Max 5 familles par fiche → meilleur rendu responsive sur mobile.
            const chunks = chunkArray(data.familles, 5);
            const grandTotalSecondes = data.familles.reduce((s, f) => s + (f.totalSecondes ?? 0), 0);
            console.log(`[Rendu] ${data.familles.length} famille(s) → ${chunks.length} fiche(s) de 5 max`);

            container.appendChild(buildEnTete(data));

            const blocs = chunks.map((fams, idx) => {
                const bloc = document.createElement('div');
                bloc.className = 'fiche-block';
                if (chunks.length > 1) {
                    const lbl = document.createElement('div');
                    lbl.className = 'fiche-block-label';
                    const from = idx * 5 + 1;
                    const to   = idx * 5 + fams.length;
                    lbl.textContent = `Familles ${from}–${to}`;
                    bloc.appendChild(lbl);
                }
                bloc.appendChild(buildCorps(data, fams, {
                    showFooter: idx === chunks.length - 1,
                    grandTotalSecondes,
                }));
                container.appendChild(bloc);
                return bloc;
            });

            // Navigation entre fiches quand elles sont nombreuses (1 fiche affichée à la fois).
            if (blocs.length > 1) setupPager(container, blocs);
        }
        console.log('[Rendu] container.innerHTML final (200 chars) :', container.innerHTML.slice(0, 200));
        renderRecap(data);
        enableSignerButton(data);
        appliquerEtatSignature(data);
        // Période (pour récupérer les créneaux éditables via /heures-mvc/liste)
        if (data.jours && data.jours.length) {
            PERIODE_DEBUT = data.jours[0].date;
            PERIODE_FIN   = data.jours[data.jours.length - 1].date;
        }
        loadCreneaux();
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
function buildCorps(data, famillesSubset, opts = {}) {
    const familles  = famillesSubset ?? data.familles;
    const { showFooter = true, grandTotalSecondes = null } = opts;
    console.log(`[buildCorps] jours=${data.jours?.length}, familles=${familles?.length}, footer=${showFooter}`);
    const table   = document.createElement('table');
    table.id      = 'monTableau11';
    const tbody   = document.createElement('tbody');
    table.appendChild(tbody);

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
            const td = mkTd(presta ? presta.join(', ') : '');
            // Cases éditables uniquement si : il y a un créneau, ET le relevé n'est
            // pas signé (une fois signé, le relevé est figé). On ne peut que modifier.
            if (presta && !RELEVE_SIGNED) {
                td.classList.add('cell-editable');
                td.dataset.date   = jour.date;
                td.dataset.numfam = fam.numFam ?? '';
                td.dataset.nomfam = fam.nomFam ?? '';
                td.addEventListener('click', () =>
                    openCellEditor(td.dataset.date, td.dataset.numfam, td.dataset.nomfam));
            }
            tr.appendChild(td);
        });

        tbody.appendChild(tr);
    });

    // ── Total heures par famille ──────────────────────────────────────────────
    const trTotH = document.createElement('tr');
    trTotH.append(mkTd('', { cls: 'no-borders' }), mkTh('TOTAL Heures', { colSpan: 2 }));
    let totalSecondes = 0;
    familles.forEach(fam => {
        totalSecondes += fam.totalSecondes ?? 0;
        trTotH.appendChild(mkTd(centieme(fam.totalSecondes ?? 0)));
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

    // ── Pied (total mensuel, signature, mention) : seulement sur la dernière fiche ──
    if (showFooter) {
        const totalMois = grandTotalSecondes ?? totalSecondes;

        const trMens = document.createElement('tr');
        trMens.append(
            mkTd('', { cls: 'no-borders' }),
            mkTd('TOTAL MENSUEL DES HEURES', { colSpan: 2, cls: 'no-borders' }),
            mkTd(centieme(totalMois), { colSpan: Math.max(1, nFam - 2) }),
            mkTd('TOTAL MENSUEL DES KM', { cls: 'no-borders' }),
            mkTd(String(data.totaux?.kmMois ?? 0))
        );
        tbody.appendChild(trMens);
        tbody.appendChild(ligneVide(totalCols));

        // ── Ligne signature ───────────────────────────────────────────────────
        const signer = data.signer ?? {};
        const trSign = document.createElement('tr');
        trSign.innerHTML = `
            <td colspan="4" class="no-borders"></td>
            <td colspan="2" class="no-borders">Date : ${escHtml(signer.date ?? '')}</td>
            <td colspan="2" class="no-borders">Signature : <i>${escHtml(signer.nom ?? '')}</i></td>
        `;
        tbody.appendChild(trSign);

        // ── Mention légale ──────────────────────────────────────────────────────
        const heureDehorsTxt = data.heureDehors ?? '_ _ _ h _ _';
        const thMention = mkTh('', { colSpan: 8 });
        thMention.id = 'totalMoisHors';
        thMention.textContent = `VOUS DEVEZ NOUS INDIQUER LE NOMBRE TOTAL D'HEURES QUE VOUS AVEZ EFFECTUÉES DANS LE MOIS POUR TOUT VOS EMPLOYEURS QUI VOUS PAYENT DIRECTEMENT SOIT TOTAL DE VOTRE MOIS : ${heureDehorsTxt} ***`;
        tbody.appendChild(mkRow([thMention]));
    }

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
        const tdH = mkTd(centieme(fam.totalSecondes ?? 0));
        tr.append(tdN, tdH);
        tbody.appendChild(tr);
        totalSecondes += fam.totalSecondes ?? 0;
    });

    if (spanSigner)   spanSigner.textContent   = data.signer?.etat ? 'signé' : 'non signé';
    if (kmParcourue)  kmParcourue.textContent  = data.totaux?.kmMois ?? 0;
    if (heureCumuler) heureCumuler.textContent = centieme(totalSecondes);
    console.log('[renderRecap] ✅ total', hm(totalSecondes), '| signé:', data.signer?.etat);
}

function enableSignerButton(_data) {
    // Réservé : désactiver le bouton si déjà signé, etc.
}

// ─── Appels API ───────────────────────────────────────────────────────────────
// Statut inline (aucune alerte bloquante). kind: 'info' | 'ok' | 'error'
function setSignStatus(msg, kind = 'info') {
    let el = document.getElementById('signStatus');
    if (!el) {
        const btn = document.getElementById('signerButton');
        if (!btn) return;
        el = document.createElement('span');
        el.id = 'signStatus';
        el.style.cssText = 'font-size:13px;font-weight:600;margin-left:4px;align-self:center;';
        btn.parentNode.insertBefore(el, btn.nextSibling);
    }
    const colors = { info: 'var(--text-muted)', ok: '#16a34a', error: '#dc2626' };
    el.style.color = colors[kind] || colors.info;
    el.textContent = msg;
}

// Ouvre la modale de confirmation avant de signer.
function ouvrirSignConfirm() {
    if (!periodeFin)   { setSignStatus('Données non encore chargées.', 'error'); return; }
    if (RELEVE_SIGNED) { setSignStatus('Ce relevé est déjà signé.', 'info'); return; }
    const modal = document.getElementById('signConfirmModal');
    const btn   = document.getElementById('signConfirmBtn');
    if (btn) btn.onclick = () => { fermerSignConfirm(); signer(); };
    if (modal) modal.classList.add('open');
}
function fermerSignConfirm() {
    document.getElementById('signConfirmModal')?.classList.remove('open');
}

async function signer() {
    if (!periodeFin)   { setSignStatus('Données non encore chargées.', 'error'); return; }
    if (RELEVE_SIGNED) { setSignStatus('Ce relevé est déjà signé.', 'info'); return; }

    const btn = document.getElementById('signerButton');
    if (btn) { btn.disabled = true; btn.dataset._t = btn.innerHTML; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signature…'; }
    setSignStatus('Signature en cours…', 'info');

    let pdfBase64 = null;
    try { pdfBase64 = await genererRelevePdfViaTelechargement(); }
    catch (e) { console.warn('[signer] PDF non généré :', e); }
    if (!pdfBase64) console.warn('[signer] pdfBase64 vide — vérifier la page de téléchargement.');

    try {
        const res = await fetch(`/api/intervenants/${ID}/signer-email`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                type,
                periodeFin,
                pdfBase64,
                filename: `Releve_${type}_${periodeFin}.pdf`,
            }),
        });
        const result = await res.json();
        if (!result.success) {
            console.error('[signer] échec :', result);
            setSignStatus(result.message || 'Signature impossible.', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset._t; }
            return;
        }
        if (result.emailEnvoye === false) {
            console.warn('[signer] email non envoyé :', result.message);
            setSignStatus('Relevé signé (email non envoyé' + (result.message ? ' : ' + result.message : '') + ')', 'info');
        } else {
            setSignStatus('Relevé signé et envoyé par email ✓', 'ok');
        }
        chargerReleve(); // recharge → état signé → verrouillage
    } catch (err) {
        console.error('[signer] erreur réseau :', err);
        setSignStatus('Erreur réseau lors de la signature.', 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset._t; }
    }
}

// Génère le relevé EXACTEMENT comme le téléchargement : on charge la page PDF
// (releve_pdf) dans une iframe cachée en mode "email" ; elle renvoie le base64.
function genererRelevePdfViaTelechargement() {
    return new Promise((resolve) => {
        const url = `/releves-mvc/intervenant/${ID}/pdf?type=${encodeURIComponent(type)}&mois=${encodeURIComponent(mois)}&pdfmode=email`;
        const iframe = document.createElement('iframe');
        iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;left:-9999px;';
        let done = false;

        function onMessage(e) {
            if (!e.data || e.data.type !== 'relevePdfBase64') return;
            done = true;
            cleanup();
            resolve(e.data.data || null);
        }
        function cleanup() {
            window.removeEventListener('message', onMessage);
            if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
        }

        window.addEventListener('message', onMessage);
        iframe.src = url;
        document.body.appendChild(iframe);

        // Sécurité : si rien ne revient (page vide, erreur), on abandonne après 12s.
        setTimeout(() => { if (!done) { cleanup(); resolve(null); } }, 12000);
    });
}

// Bannière + verrouillage du bouton quand le relevé est signé.
function appliquerEtatSignature(data) {
    const signed = !!(data.signer && data.signer.etat);
    const btn = document.getElementById('signerButton');
    if (btn) {
        btn.disabled = signed;
        btn.innerHTML = signed
            ? '<i class="fas fa-circle-check"></i> Relevé signé'
            : '<i class="fas fa-signature"></i> Signer';
    }
    const container = document.getElementById('tableContainer');
    const ancienne = document.getElementById('signedBanner');
    if (ancienne) ancienne.remove();
    if (signed && container) {
        const b = document.createElement('div');
        b.id = 'signedBanner';
        b.style.cssText = 'margin-bottom:12px;padding:10px 14px;background:#f0fdf4;border-left:3px solid #22c55e;border-radius:8px;font-size:13px;color:#166534;';
        b.innerHTML = `<i class="fas fa-lock"></i> Relevé signé le <strong>${escHtml(data.signer.date ?? '')}</strong> — il ne peut plus être modifié.`;
        container.parentNode.insertBefore(b, container);
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

// Heures en centièmes (ex. 2h30 → "2,50") — cohérent avec les cellules du relevé.
function centieme(secondes) {
    return (Math.max(0, secondes || 0) / 3600).toFixed(2).replace('.', ',');
}

// Navigation entre les fiches (affiche une fiche à la fois + compteur « Fiche X / Y »).
function setupPager(container, blocs) {
    const total = blocs.length;
    let current = 0;

    const bar = document.createElement('div');
    bar.className = 'fiche-pager';
    bar.innerHTML = `
        <button type="button" class="btn btn-secondary btn-sm fiche-prev"><i class="fas fa-chevron-left"></i> Précédent</button>
        <span class="fiche-counter">Fiche <strong>1</strong> / ${total}</span>
        <button type="button" class="btn btn-secondary btn-sm fiche-next">Suivant <i class="fas fa-chevron-right"></i></button>
    `;
    container.insertBefore(bar, blocs[0]);

    const counter = bar.querySelector('.fiche-counter strong');
    const btnPrev = bar.querySelector('.fiche-prev');
    const btnNext = bar.querySelector('.fiche-next');

    function show(i, scroll = true) {
        current = Math.max(0, Math.min(total - 1, i));
        blocs.forEach((b, idx) => { b.style.display = idx === current ? '' : 'none'; });
        counter.textContent = current + 1;
        btnPrev.disabled = current === 0;
        btnNext.disabled = current === total - 1;
        if (scroll) bar.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    btnPrev.onclick = () => show(current - 1);
    btnNext.onclick = () => show(current + 1);
    show(0, false);
}

function chunkArray(arr, size) {
    const out = [];
    for (let i = 0; i < arr.length; i += size) out.push(arr.slice(i, i + size));
    return out;
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// ═══════════════════════════════════════════════════════════════════════════
//  ÉDITION EN PLACE DES CRÉNEAUX (cases jour × famille)
// ═══════════════════════════════════════════════════════════════════════════
let PERIODE_DEBUT = null;
let PERIODE_FIN   = null;
// Map des créneaux éditables : clé "date||nomFam" → [{id, debut, fin}]
let CRENEAUX_MAP  = {};
// Contexte de la case en cours d'édition
let CELL_CTX = { date: null, numFam: '', nomFam: '' };

const CSRF_AJAX = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function creneauKey(date, nomFam) { return `${date}||${nomFam}`; }

// Récupère les prestations de la période via l'endpoint existant et construit la map.
async function loadCreneaux() {
    CRENEAUX_MAP = {};
    if (!PERIODE_DEBUT || !PERIODE_FIN) return;
    try {
        const url = `/heures-mvc/liste/${ID}?dateDebut=${PERIODE_DEBUT}&dateFin=${PERIODE_FIN}`;
        const res = await fetch(url, { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.success) return;
        (json.data || []).forEach(p => {
            if (p.desactiver) return;
            if ((p.typePresta || '').toUpperCase() !== type) return;
            const key = creneauKey(p.datePresta, p.nomFam);
            (CRENEAUX_MAP[key] ||= []).push({
                id:    p.id,
                debut: p.heureDebutPresta,
                fin:   p.heureFinPresta,
            });
        });
    } catch (err) {
        console.error('[loadCreneaux] erreur', err);
    }
}

// ─── Helpers select heures/minutes ──────────────────────────────────────────
function fillHourOptions(sel, value) {
    sel.innerHTML = '';
    for (let h = 0; h <= 23; h++) {
        const v = String(h).padStart(2, '0');
        const o = document.createElement('option');
        o.value = o.textContent = v;
        if (v === value) o.selected = true;
        sel.appendChild(o);
    }
}
function fillMinuteOptions(sel, value) {
    sel.innerHTML = '';
    for (let m = 0; m <= 55; m += 5) {
        const v = String(m).padStart(2, '0');
        const o = document.createElement('option');
        o.value = o.textContent = v;
        if (v === value) o.selected = true;
        sel.appendChild(o);
    }
}

// ─── Ouverture / fermeture de la modale ─────────────────────────────────────
function openCellEditor(date, numFam, nomFam) {
    CELL_CTX = { date, numFam, nomFam };
    const modal = document.getElementById('cellEditModal');
    document.getElementById('cellModalTitle').textContent = `Créneaux — ${nomFam || 'Famille occasionnelle'}`;
    document.getElementById('cellModalSub').textContent =
        `${frDate(date)} · ${type === 'MENA' ? 'Ménage' : "Garde d'enfants"}`;
    document.getElementById('cellLockedNote').style.display = 'none';

    renderCreneauxList();
    modal.classList.add('open');
}
function fermerCellEditor() {
    document.getElementById('cellEditModal').classList.remove('open');
}

// Liste des créneaux existants de la case, chacun éditable/supprimable.
function renderCreneauxList() {
    const wrap = document.getElementById('cellCreneauxList');
    wrap.innerHTML = '';
    const list = CRENEAUX_MAP[creneauKey(CELL_CTX.date, CELL_CTX.nomFam)] || [];

    if (!list.length) {
        const p = document.createElement('p');
        p.style.cssText = 'color:var(--text-muted);font-size:13px;padding:8px 0;';
        p.textContent = 'Aucun créneau ce jour-là. Ajoutez-en un ci-dessous.';
        wrap.appendChild(p);
        return;
    }

    list.forEach(cr => {
        const [dH, dM] = cr.debut.split(':');
        const [fH, fM] = cr.fin.split(':');
        const row = document.createElement('div');
        row.className = 'cr-row';
        row.innerHTML = `
            <div class="cr-time">
                <select class="cr-dh"></select><span class="cr-sep">h</span><select class="cr-dm"></select>
            </div>
            <span class="cr-arrow"><i class="fas fa-arrow-right"></i></span>
            <div class="cr-time">
                <select class="cr-fh"></select><span class="cr-sep">h</span><select class="cr-fm"></select>
            </div>
            <div class="cr-actions">
                <button type="button" class="btn btn-secondary btn-sm cr-save" title="Enregistrer"><i class="fas fa-check"></i></button>
                <button type="button" class="btn btn-ghost btn-sm cr-del" title="Supprimer" style="color:var(--danger);"><i class="fas fa-trash"></i></button>
            </div>`;
        fillHourOptions(row.querySelector('.cr-dh'), dH);
        fillMinuteOptions(row.querySelector('.cr-dm'), dM);
        fillHourOptions(row.querySelector('.cr-fh'), fH);
        fillMinuteOptions(row.querySelector('.cr-fm'), fM);
        row.querySelector('.cr-save').onclick = () => modifierCreneau(cr.id, {
            dh: row.querySelector('.cr-dh').value, dm: row.querySelector('.cr-dm').value,
            fh: row.querySelector('.cr-fh').value, fm: row.querySelector('.cr-fm').value,
        });
        row.querySelector('.cr-del').onclick = () => supprimerCreneau(cr.id);
        wrap.appendChild(row);
    });
}

// ─── Actions serveur (réutilisent les endpoints /heures-mvc) ────────────────
async function rafraichirApresEdition() {
    await chargerReleve(); // recharge la grille + relance loadCreneaux()
    if (document.getElementById('cellEditModal').classList.contains('open')) {
        renderCreneauxList();
    }
}

function gererReponseVerrou(result, res) {
    if (res.status === 423 || result.locked) {
        document.getElementById('cellLockedNote').style.display = '';
        return true;
    }
    if (!result.success) {
        alert('Erreur : ' + (result.error || 'opération impossible'));
        return true;
    }
    return false;
}

async function modifierCreneau(id, { dh, dm, fh, fm }) {
    try {
        const res = await fetch(`/heures-mvc/modifier/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                date: CELL_CTX.date,
                heureDebut: dh, minuteDebut: dm,
                heureFin: fh, minuteFin: fm,
            }),
        });
        const result = await res.json();
        if (gererReponseVerrou(result, res)) return;
        await rafraichirApresEdition();
    } catch (err) { console.error(err); alert('Erreur serveur'); }
}

async function supprimerCreneau(id) {
    if (!confirm('Supprimer ce créneau ?')) return;
    try {
        const res = await fetch(`/heures-mvc/supprimer/${id}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': CSRF_AJAX },
        });
        const result = await res.json();
        if (gererReponseVerrou(result, res)) return;
        await rafraichirApresEdition();
    } catch (err) { console.error(err); alert('Erreur serveur'); }
}

function frDate(ymd) {
    const [y, m, d] = ymd.split('-');
    return `${d}/${m}/${y}`;
}

// Fermer la modale en cliquant à l'extérieur
document.getElementById('cellEditModal')?.addEventListener('click', e => {
    if (e.target.id === 'cellEditModal') fermerCellEditor();
});
document.getElementById('signConfirmModal')?.addEventListener('click', e => {
    if (e.target.id === 'signConfirmModal') fermerSignConfirm();
});

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
