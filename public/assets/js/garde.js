/* Déclaration garde d'enfant sans QR : téléphone → planning garde du jour → choix famille
   → bascule sur /pointage/{numFam} (qui auto-identifie via la session). */
(function () {
    const card = document.getElementById('card');
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const URL_PLANNING = card.dataset.urlPlanning;

    const elTel       = document.getElementById('tel');
    const etapeTel    = document.getElementById('etape-tel');
    const etapeListe  = document.getElementById('etape-liste');
    const confirmNom  = document.getElementById('confirm-nom');
    const liste       = document.getElementById('liste-creneaux');
    const aucun       = document.getElementById('aucun');
    const btnContinuer= document.getElementById('btn-continuer');
    const btnRetour   = document.getElementById('btn-retour');

    const show = el => el.classList.remove('hidden');
    const hide = el => el.classList.add('hidden');
    const erreur = (msg) => (window.toast ? toast(msg, 'error') : alert(msg));

    async function postJSON(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        return res.json().catch(() => ({ success: false, error: 'Réponse invalide du serveur.' }));
    }

    function rendreListe(creneaux) {
        liste.innerHTML = '';
        if (!creneaux || creneaux.length === 0) {
            show(aucun);
            return;
        }
        hide(aucun);
        creneaux.forEach(c => {
            const b = document.createElement('button');
            b.className = 'creneau';
            const plage = c.heureFin ? `${c.heureDebut} → ${c.heureFin}` : `dès ${c.heureDebut}`;
            b.innerHTML =
                `<span class="ic"><i class="fas fa-house-user"></i></span>` +
                `<span><span class="nom">${c.nomFam}</span><br><span class="plage">${plage}</span></span>` +
                (c.enCours ? `<span class="badge">en cours</span>` : '');
            // Choisir la famille → flux de pointage (auto-identifié via session),
            // en contexte GARDE (?garde=1) → type ENFA forcé, pas de choix de service.
            b.addEventListener('click', () => {
                window.location.href = `/pointage/${encodeURIComponent(c.numFam)}?garde=1`;
            });
            liste.appendChild(b);
        });
    }

    async function continuer() {
        const tel = elTel.value.trim();
        if (!tel) { erreur('Entrez votre numéro de téléphone.'); return; }
        btnContinuer.disabled = true;
        try {
            const data = await postJSON(URL_PLANNING, { tel });
            if (!data.success) { erreur(data.error || 'Identification impossible.'); return; }
            confirmNom.textContent = data.nom;
            rendreListe(data.creneaux);
            hide(etapeTel); show(etapeListe);
        } catch {
            erreur('Erreur réseau — réessayez.');
        } finally {
            btnContinuer.disabled = false;
        }
    }

    btnContinuer.addEventListener('click', continuer);
    btnRetour.addEventListener('click', () => { hide(etapeListe); show(etapeTel); });
    elTel.addEventListener('keydown', e => { if (e.key === 'Enter') continuer(); });
})();
