let scanning = true;
let stream = null;
let scanInterval = null;
let currentFam = null;
let selectedType = 'ENFA';
let timerInterval = null;

const video    = document.getElementById('video');
const canvas   = document.getElementById('canvas');
const ctx      = canvas.getContext('2d');
const status   = document.getElementById('scanStatus');

// ── Démarrage caméra ─────────────────────────────────
async function startCamera() {
    // Contexte sécurisé requis : HTTPS ou http://localhost.
    // Sur une IP en http:// (ex. mobile via le réseau local), l'API est indisponible.
    if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
        status.textContent = location.protocol === 'https:'
            ? 'Caméra non supportée par ce navigateur — utilisez la saisie manuelle'
            : 'La caméra nécessite une connexion sécurisée (HTTPS). Sur mobile, utilisez la saisie manuelle.';
        document.getElementById('manuelSection').style.display = 'block';
        return;
    }

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } }
        });
        video.srcObject = stream;
        video.play();
        status.textContent = 'Pointez la caméra vers le QR code de la famille';
        scanInterval = setInterval(scanFrame, 300);
    } catch (e) {
        // Message selon la vraie cause du refus
        let msg = 'Caméra non disponible — utilisez la saisie manuelle';
        if (e && e.name === 'NotAllowedError') {
            msg = 'Autorisation caméra refusée. Activez-la dans les réglages du navigateur, ou utilisez la saisie manuelle.';
        } else if (e && e.name === 'NotFoundError') {
            msg = 'Aucune caméra détectée — utilisez la saisie manuelle.';
        }
        status.textContent = msg;
        document.getElementById('manuelSection').style.display = 'block';
    }
}

function stopCamera() {
    clearInterval(scanInterval);
    if (stream) stream.getTracks().forEach(t => t.stop());
}

function scanFrame() {
    if (!scanning || video.readyState !== video.HAVE_ENOUGH_DATA) return;
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(img.data, img.width, img.height);
    if (code) handleScan(code.data.trim());
}

// ── Traitement du scan ───────────────────────────────
function handleScan(raw) {
    scanning = false;

    // Supporte les deux formats de QR :
    //  - URL  : http://host/declarer/M7156  → on extrait "M7156"
    //  - brut : M7156
    let code = (raw || '').trim();
    const m = code.match(/\/declarer\/([^/?#]+)/i);
    if (m) code = decodeURIComponent(m[1]);
    code = code.trim();

    // Recherche insensible à la casse parmi les familles de l'intervenant
    let key = FAMILLES[code] ? code
            : Object.keys(FAMILLES).find(k => k.toUpperCase() === code.toUpperCase());

    const fam = key ? FAMILLES[key] : null;
    if (!fam) {
        // Non reconnu → on bascule vers le formulaire de saisie avec la famille
        // OCCASIONNELLE pré-sélectionnée (l'intervenant y saisit le nom).
        // Le serveur connaît la famille (par son numéro) : il résout le nom et
        // ouvre le formulaire pré-rempli (occasionnel avec le nom si non assigné).
        status.textContent = `Famille "${code}" — ouverture de la saisie…`;
        window.location.href = '/declarer/' + encodeURIComponent(code);
        return;
    }
    currentFam = { code: key, ...fam };
    afficherResultat(currentFam);
}

function afficherResultat(fam) {
    document.getElementById('scannerSection').style.display = 'none';
    document.getElementById('resultSection').style.display  = 'block';

    document.getElementById('resFamNom').textContent  = fam.nom;
    document.getElementById('resFamCode').textContent = fam.code;

    const now = new Date();
    document.getElementById('resHeure').textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    document.getElementById('resDate').textContent  = now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

    // Check localStorage pour intervention en cours
    const enCours = getEnCours(fam.code);

    if (enCours) {
        showFin(enCours);
    } else if (estDejaPointe(fam.code)) {
        showDejaPointe();
    } else {
        showDebut(fam);
    }
}

// Famille déjà pointée aujourd'hui (règle : 1 créneau/famille/jour)
function estDejaPointe(code) {
    if (typeof DEJA_POINTE === 'undefined' || !Array.isArray(DEJA_POINTE)) return false;
    return DEJA_POINTE.some(c => String(c).toUpperCase() === String(code).toUpperCase());
}

function showDejaPointe() {
    document.getElementById('typeSelect').style.display  = 'none';
    document.getElementById('actionDebut').style.display = 'none';
    document.getElementById('actionFin').style.display   = 'none';
    document.getElementById('actionSaved').style.display = 'none';

    let el = document.getElementById('actionDeja');
    if (!el) {
        el = document.createElement('div');
        el.id = 'actionDeja';
        el.style.textAlign = 'center';
        el.innerHTML = `
            <i class="fas fa-circle-info" style="font-size:30px;color:#f59e0b;margin-bottom:8px;"></i>
            <p style="font-weight:600;color:#b45309;margin-bottom:4px;">Déjà pointé aujourd'hui</p>
            <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">
                Une prestation pour cette famille existe déjà pour aujourd'hui (1 créneau par jour).
            </p>
            <button class="btn btn-ghost btn-sm" onclick="rescan()"><i class="fas fa-qrcode"></i> Scanner une autre famille</button>`;
        document.querySelector('.result-card').appendChild(el);
    }
    el.style.display = 'block';
}

function showDebut(fam) {
    // Type selector
    const ts = document.getElementById('typeSelect');
    ts.style.display = (fam.ge && fam.mena) ? 'flex' : 'none';
    if (!fam.ge && fam.mena) selectedType = 'MENA';
    else selectedType = 'ENFA';
    document.querySelectorAll('.type-pill').forEach(p => p.classList.toggle('active', p.dataset.type === selectedType));

    document.getElementById('actionDebut').style.display = 'block';
    document.getElementById('actionFin').style.display   = 'none';
    document.getElementById('actionSaved').style.display = 'none';
}

function showFin(enCours) {
    document.getElementById('actionDebut').style.display = 'none';
    document.getElementById('actionFin').style.display   = 'block';
    document.getElementById('actionSaved').style.display = 'none';
    document.getElementById('typeSelect').style.display  = 'none';

    document.getElementById('heureDebutAffiche').textContent = enCours.heure;
    document.getElementById('kmSection').style.display = enCours.type === 'ENFA' ? 'block' : 'none';
    selectedType = enCours.type;

    // Timer durée
    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        const diff = Date.now() - enCours.ts;
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        document.getElementById('dureeCourante').textContent = `${h}h${String(m).padStart(2,'0')}`;
    }, 10000);
}

// Arrondit une date au quart d'heure le plus proche → "HH:MM"
function arrondiQuartHeure(date) {
    const d = new Date(date);
    const r = Math.round(d.getMinutes() / 15) * 15; // 0,15,30,45 (60 → heure suivante, géré par Date)
    d.setMinutes(r, 0, 0);
    return d.toTimeString().slice(0, 5);
}

// ── Actions ──────────────────────────────────────────
function selectType(btn) {
    selectedType = btn.dataset.type;
    document.querySelectorAll('.type-pill').forEach(p => p.classList.toggle('active', p === btn));
}

function demarrer() {
    const now = new Date();
    const hh  = arrondiQuartHeure(now); // début arrondi au quart d'heure le plus proche
    setEnCours(currentFam.code, {
        heure: hh,
        type: selectedType,
        ts: now.getTime(),
        date: now.toISOString().slice(0,10),
        nom: currentFam.nom,
    });
    showFin(getEnCours(currentFam.code));
}

async function terminer() {
    const enCours = getEnCours(currentFam.code);
    if (!enCours) return;

    const now  = new Date();
    const hFin = arrondiQuartHeure(now); // fin arrondie au quart d'heure le plus proche
    const km   = document.getElementById('kmInput').value || null;

    try {
        const res = await fetch(QR_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'fin',
                numFam: currentFam.code,
                nomFam: currentFam.nom,
                heureDebut: enCours.heure,
                heureFin: hFin,
                date: enCours.date,
                type: enCours.type,
                km: km ? parseFloat(km) : null,
            })
        });
        const data = await res.json().catch(() => ({ success: false, error: 'Réponse invalide du serveur' }));
        if (data.success) {
            clearEnCours(currentFam.code);
            clearInterval(timerInterval);
            document.getElementById('actionFin').style.display   = 'none';
            document.getElementById('actionSaved').style.display = 'block';
            document.getElementById('savedSummary').textContent =
                `${enCours.heure} → ${hFin} · ${enCours.type === 'ENFA' ? "Garde d'enfants" : 'Ménage'}`;
        } else {
            // Affiche la vraie raison renvoyée par le serveur (chevauchement, date, etc.)
            toast(data.error || 'Enregistrement impossible — réessayez', 'error');
        }
    } catch {
        toast('Erreur réseau — réessayez');
    }
}

function annuler() {
    rescan();
}

function rescan() {
    currentFam = null;
    clearInterval(timerInterval);
    document.getElementById('resultSection').style.display  = 'none';
    document.getElementById('scannerSection').style.display = 'block';
    document.getElementById('actionDebut').style.display    = 'none';
    document.getElementById('actionFin').style.display      = 'none';
    document.getElementById('actionSaved').style.display    = 'none';
    scanning = true;
    status.textContent = 'Pointez la caméra vers le QR code de la famille';
}

// ── Saisie manuelle ──────────────────────────────────
function toggleManuel() {
    const s = document.getElementById('manuelSection');
    s.style.display = s.style.display === 'none' ? 'block' : 'none';
}
function scanManuel() {
    const v = document.getElementById('manuelCode').value.trim().toUpperCase();
    if (v) handleScan(v);
}

// ── LocalStorage helpers ─────────────────────────────
const LS_KEY = `qr_inter_${INTERVENANT_ID}`;
function getEnCours(code) {
    try { const d = JSON.parse(localStorage.getItem(LS_KEY) || '{}'); return d[code] || null; }
    catch { return null; }
}
function setEnCours(code, data) {
    try { const d = JSON.parse(localStorage.getItem(LS_KEY) || '{}'); d[code] = data; localStorage.setItem(LS_KEY, JSON.stringify(d)); }
    catch {}
}
function clearEnCours(code) {
    try { const d = JSON.parse(localStorage.getItem(LS_KEY) || '{}'); delete d[code]; localStorage.setItem(LS_KEY, JSON.stringify(d)); }
    catch {}
}

// ── Récupération d'une intervention non terminée ─────
const JOURS_FR = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
let recoverCode = null;

function trouverEnCoursOublie() {
    let all = {};
    try { all = JSON.parse(localStorage.getItem(LS_KEY) || '{}'); } catch { return null; }
    const todayStr = new Date().toISOString().slice(0, 10);
    const now = Date.now();
    for (const [code, ec] of Object.entries(all)) {
        if (!ec || !ec.ts) continue;
        // Oublié = d'un autre jour, ou démarré il y a plus de 12h
        const stale = (ec.date && ec.date !== todayStr) || (now - ec.ts > 12 * 3600 * 1000);
        if (stale) return { code, ...ec };
    }
    return null;
}

function afficherRecuperation(ec) {
    recoverCode = ec.code;
    document.getElementById('scannerSection').style.display = 'none';
    document.getElementById('resultSection').style.display  = 'none';
    document.getElementById('recoverSection').style.display = 'block';

    document.getElementById('recFam').textContent   = ec.nom || ec.code;
    document.getElementById('recDate').textContent  = new Date(ec.date).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long' });
    document.getElementById('recDebut').textContent = ec.heure;

    // Pré-remplir avec l'heure prévue (proposer) si disponible
    const jour = JOURS_FR[new Date(ec.date).getDay()];
    const prevu = PLANNING_FIN[`${ec.code}|${jour}|${ec.type}`];
    const input = document.getElementById('recHeureFin');
    if (prevu) {
        input.value = prevu;
        document.getElementById('recPrevuNote').textContent = `(heure prévue : ${prevu})`;
    } else {
        input.value = ec.heure;
        document.getElementById('recPrevuNote').textContent = '';
    }
}

async function validerRecuperation() {
    const ec = getEnCours(recoverCode);
    if (!ec) { ignorerRecuperation(); return; }
    const hFin = document.getElementById('recHeureFin').value;
    if (!hFin) { toast('Veuillez indiquer l\'heure de fin.'); return; }

    try {
        const res = await fetch(QR_API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                action: 'fin',
                numFam: recoverCode,
                nomFam: ec.nom,
                heureDebut: ec.heure,
                heureFin: hFin,
                date: ec.date,
                type: ec.type,
                km: null,
            })
        });
        const data = await res.json().catch(() => ({ success:false, error:'Réponse invalide du serveur' }));
        if (data.success) {
            clearEnCours(recoverCode);
            document.getElementById('recoverSection').style.display = 'none';
            // Y a-t-il un autre oubli ? sinon reprendre le scan normal
            const autre = trouverEnCoursOublie();
            if (autre) afficherRecuperation(autre);
            else demarrerFlux();
        } else {
            toast(data.error || 'Enregistrement impossible.', 'error');
        }
    } catch {
        toast('Erreur réseau — réessayez');
    }
}

function ignorerRecuperation() {
    if (recoverCode) clearEnCours(recoverCode);
    document.getElementById('recoverSection').style.display = 'none';
    const autre = trouverEnCoursOublie();
    if (autre) afficherRecuperation(autre);
    else demarrerFlux();
}

// Au chargement : récupérer un éventuel oubli, sinon démarrer le scan
function demarrerFlux() {
    document.getElementById('scannerSection').style.display = 'block';
    startCamera();
}

// Famille pré-sélectionnée via ?fam= (redirection depuis un scan natif assigné) :
// on affiche directement le compteur, sans démarrer la caméra.
function ouvrirFamillePreselectionnee(famCode) {
    const key = FAMILLES[famCode] ? famCode
              : Object.keys(FAMILLES).find(k => k.toUpperCase() === famCode.toUpperCase());
    if (!key) return false;
    currentFam = { code: key, ...FAMILLES[key] };
    afficherResultat(currentFam); // masque le scanner, affiche Démarrer/Terminer
    return true;
}

const oubli   = trouverEnCoursOublie();
const famParam = new URLSearchParams(location.search).get('fam');

if (oubli) {
    afficherRecuperation(oubli);
} else if (famParam && ouvrirFamillePreselectionnee(famParam)) {
    // compteur affiché directement, pas de caméra
} else {
    demarrerFlux();
}
