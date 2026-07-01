// Repli : si le helper global toast() n'est pas chargé sur cette page, on évite le crash
// (« toast is not defined ») en retombant sur une notification simple.
if (typeof window.toast !== 'function') {
    window.toast = (msg) => alert(msg);
}

let scanning = true;
let stream = null;
let scanInterval = null;
let currentFam = null;
let selectedType = 'ENFA';
let timerInterval = null;
let currentEnCours = null; // pointage en cours actif (données serveur)

// Heure réelle (non arrondie) du téléphone → "HH:MM"
function heureReelleNow() {
    const d = new Date();
    return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
}

// Affiche/masque le champ « Modifier l'heure » (début ou fin), pré-rempli.
function toggleModif(which) {
    const wrap = document.getElementById(which === 'fin' ? 'modifFinWrap' : 'modifDebutWrap');
    const input = document.getElementById(which === 'fin' ? 'modifFin' : 'modifDebut');
    if (!wrap) return;
    const hidden = wrap.style.display === 'none' || !wrap.style.display;
    wrap.style.display = hidden ? 'block' : 'none';
    if (hidden && !input.value) input.value = arrondiQuartHeure(new Date());
}

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
            ? 'Caméra non supportée par ce navigateur utilisez la saisie manuelle'
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
        let msg = 'Caméra non disponible utilisez la saisie manuelle';
        if (e && e.name === 'NotAllowedError') {
            msg = 'Autorisation caméra refusée. Activez-la dans les réglages du navigateur, ou utilisez la saisie manuelle.';
        } else if (e && e.name === 'NotFoundError') {
            msg = 'Aucune caméra détectée utilisez la saisie manuelle.';
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

    // Flux UNIQUE : on redirige vers la page de pointage /pointage/{code}.
    // (Connecté, le numéro y est récupéré automatiquement → identification directe.)
    status.textContent = 'Ouverture du pointage…';
    stopCamera();
    window.location.href = '/pointage/' + encodeURIComponent(code);
}

function afficherResultat(fam) {
    document.getElementById('scannerSection').style.display = 'none';
    document.getElementById('resultSection').style.display  = 'block';

    document.getElementById('resFamNom').textContent  = fam.nom;
    document.getElementById('resFamCode').textContent = fam.code;

    const now = new Date();
    document.getElementById('resHeure').textContent = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    document.getElementById('resDate').textContent  = now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

    // Pointage en cours = donnée SERVEUR (plus de localStorage).
    const enCours = getEnCoursServeur(fam.code);

    if (enCours) {
        currentEnCours = enCours;
        showFin(enCours);
    } else if (estDejaPointe(fam.code)) {
        showDejaPointe();
    } else {
        showDebut(fam);
    }
}

// Pointage en cours (serveur) du jour pour cette famille.
function getEnCoursServeur(code) {
    if (!Array.isArray(EN_COURS)) return null;
    return EN_COURS.find(e => e.aujourdhui && e.numFam
        && String(e.numFam).toUpperCase() === String(code).toUpperCase()) || null;
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

    const debut = enCours.heureDebut || '—';
    document.getElementById('heureDebutAffiche').textContent = debut;
    document.getElementById('kmSection').style.display = enCours.type === 'ENFA' ? 'block' : 'none';
    selectedType = enCours.type;

    // Timer durée : à partir de l'heure de début (aujourd'hui).
    clearInterval(timerInterval);
    const [dh, dm] = (enCours.heureDebut || '0:0').split(':').map(Number);
    const startTs = new Date(); startTs.setHours(dh, dm, 0, 0);
    timerInterval = setInterval(() => {
        const diff = Math.max(0, Date.now() - startTs.getTime());
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

async function postQr(payload) {
    const res = await fetch(QR_API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });
    return res.json().catch(() => ({ success: false, error: 'Réponse invalide du serveur' }));
}

async function demarrer() {
    // Heure arrondie (relevé) = saisie manuelle si fournie, sinon arrondi auto.
    const heure = (document.getElementById('modifDebut')?.value || '').trim() || arrondiQuartHeure(new Date());
    try {
        const data = await postQr({
            action: 'debut',
            numFam: currentFam.code,
            type: selectedType,
            heure: heure,                 // arrondie (modifiable) → affichée au relevé
            heureReelle: heureReelleNow(),// réelle (trace)
        });
        if (data.success) {
            currentEnCours = { numFam: currentFam.code, heureDebut: heure, type: selectedType, aujourdhui: true };
            showFin(currentEnCours);
        } else {
            toast(data.error || 'Démarrage impossible réessayez', 'error');
        }
    } catch {
        toast('Erreur réseau réessayez', 'error');
    }
}

async function terminer() {
    if (!currentEnCours) return;
    const hFin = (document.getElementById('modifFin')?.value || '').trim() || arrondiQuartHeure(new Date());
    const km   = document.getElementById('kmInput').value || null;
    try {
        const data = await postQr({
            action: 'fin',
            numFam: currentFam.code,
            type: currentEnCours.type,
            heure: hFin,                  // arrondie (modifiable)
            heureReelle: heureReelleNow(),// réelle (trace)
            km: km ? parseFloat(km) : null,
        });
        if (data.success) {
            clearInterval(timerInterval);
            document.getElementById('actionFin').style.display   = 'none';
            document.getElementById('actionSaved').style.display = 'block';
            document.getElementById('savedSummary').textContent =
                `${data.debut || currentEnCours.heureDebut} → ${data.fin || hFin} · ${currentEnCours.type === 'ENFA' ? "Garde d'enfants" : 'Ménage'}`;
            currentEnCours = null;
        } else {
            toast(data.error || 'Enregistrement impossible réessayez', 'error');
        }
    } catch {
        toast('Erreur réseau réessayez', 'error');
    }
}

function annuler() {
    rescan();
}

function rescan() {
    currentFam = null;
    currentEnCours = null;
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

// ── Récupération d'une intervention non terminée (données SERVEUR) ─────
const JOURS_FR = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
let recEc = null;
// Oublis = pointages en cours d'un AUTRE jour (non terminés).
let oublisRestants = (Array.isArray(EN_COURS) ? EN_COURS : []).filter(e => !e.aujourdhui);

function trouverEnCoursOublie() {
    return oublisRestants.length ? oublisRestants[0] : null;
}

function afficherRecuperation(ec) {
    recEc = ec;
    document.getElementById('scannerSection').style.display = 'none';
    document.getElementById('resultSection').style.display  = 'none';
    document.getElementById('recoverSection').style.display = 'block';

    document.getElementById('recFam').textContent   = ec.nomFam || ec.numFam;
    document.getElementById('recDate').textContent  = new Date(ec.date).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long' });
    document.getElementById('recDebut').textContent = ec.heureDebut;

    // Pré-remplir avec l'heure prévue (planning) si disponible
    const jour = JOURS_FR[new Date(ec.date).getDay()];
    const prevu = PLANNING_FIN[`${ec.numFam}|${jour}|${ec.type}`];
    const input = document.getElementById('recHeureFin');
    if (prevu) {
        input.value = prevu;
        document.getElementById('recPrevuNote').textContent = `(heure prévue : ${prevu})`;
    } else {
        input.value = ec.heureDebut;
        document.getElementById('recPrevuNote').textContent = '';
    }
}

async function validerRecuperation() {
    if (!recEc) { ignorerRecuperation(); return; }
    const hFin = document.getElementById('recHeureFin').value;
    if (!hFin) { toast('Veuillez indiquer l\'heure de fin.', 'error'); return; }

    try {
        const data = await postQr({
            action: 'fin',
            numFam: recEc.numFam,
            type: recEc.type,
            heure: hFin,          // arrondie (saisie)
            heureReelle: hFin,    // pas d'heure réelle connue après coup → = saisie
            km: null,
        });
        if (data.success) {
            oublisRestants.shift();
            document.getElementById('recoverSection').style.display = 'none';
            const autre = trouverEnCoursOublie();
            if (autre) afficherRecuperation(autre);
            else demarrerFlux();
        } else {
            toast(data.error || 'Enregistrement impossible.', 'error');
        }
    } catch {
        toast('Erreur réseau réessayez', 'error');
    }
}

function ignorerRecuperation() {
    // On ne supprime PAS le pointage serveur : on passe au suivant (récupérable plus tard).
    oublisRestants.shift();
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

const famParam = new URLSearchParams(location.search).get('fam');

// NB : la récupération d'oubli n'est PLUS gérée ici. Le scan redirige vers /pointage,
// qui prend en charge les pointages non terminés (modal « Modifier et clôturer »).
if (famParam && ouvrirFamillePreselectionnee(famParam)) {
    // compteur affiché directement, pas de caméra
} else {
    demarrerFlux();
}
