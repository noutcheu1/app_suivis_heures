/* Pointage QR sans connexion : numéro → confirmation nom → Démarrer / Terminer. */
(function () {
    const card  = document.getElementById('card');
    const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const URLS  = {
        identifier: card.dataset.urlIdentifier,
        demarrer:   card.dataset.urlDemarrer,
        terminer:   card.dataset.urlTerminer,
    };

    const elTel        = document.getElementById('tel');
    const etapeTel     = document.getElementById('etape-tel');
    const etapeAction  = document.getElementById('etape-action');
    const etapeFin     = document.getElementById('etape-fin');
    const confirmNom   = document.getElementById('confirm-nom');
    const confirmSub   = document.getElementById('confirm-sub');
    const typeSection  = document.getElementById('type-section');
    const typeRow      = document.getElementById('type-row');
    const kmSection    = document.getElementById('km-section');
    const planningSec  = document.getElementById('planning-section');
    const planningList = document.getElementById('planning-list');
    const horairesSec  = document.getElementById('horaires-section');
    const btnIdentifier= document.getElementById('btn-identifier');
    const btnDemarrer  = document.getElementById('btn-demarrer');
    const btnTerminer  = document.getElementById('btn-terminer');
    const btnRetour    = document.getElementById('btn-retour');

    let selectedType = null;
    let enCours = false;

    const erreur = (msg) => (window.toast ? toast(msg, 'error') : alert(msg));

    // Heure locale du téléphone, arrondie au quart d'heure le plus proche → "HH:MM".
    // (Évite le décalage de fuseau du serveur.)
    function heureMaintenant() {
        const d = new Date();
        const q = Math.round(d.getMinutes() / 15) * 15;
        d.setMinutes(0, 0, 0);
        d.setMinutes(q);
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    // Heure RÉELLE (non arrondie) du téléphone → "HH:MM".
    function heureReelleMaintenant() {
        const d = new Date();
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    async function postJSON(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({ success: false, error: 'Réponse invalide du serveur' }));
        return data;
    }

    function show(el) { el.classList.remove('hidden'); }
    function hide(el) { el.classList.add('hidden'); }

    function renderTypes(types) {
        typeRow.innerHTML = '';
        selectedType = null;
        if (!types || types.length === 0) { hide(typeSection); return; }
        if (types.length === 1) {
            selectedType = types[0];
            hide(typeSection);
            return;
        }
        show(typeSection);
        types.forEach(t => {
            const b = document.createElement('button');
            b.className = 'type-btn';
            b.textContent = t === 'MENA' ? 'Ménage' : "Garde d'enfants";
            b.onclick = () => {
                selectedType = t;
                kmSection.classList.toggle('hidden', !(t === 'ENFA'));
                [...typeRow.children].forEach(c => c.classList.remove('active'));
                b.classList.add('active');
            };
            typeRow.appendChild(b);
        });
    }

    function renderPlanning(creneaux) {
        if (!creneaux || creneaux.length === 0) { hide(planningSec); return; }
        planningList.innerHTML = '';
        creneaux.forEach(c => {
            const row = document.createElement('div');
            row.className = 'info-line';
            const label = c.type === 'MENA' ? 'Ménage' : "Garde d'enfants";
            const plage = c.heureFin ? `${c.heureDebut} → ${c.heureFin}` : `dès ${c.heureDebut}`;
            row.innerHTML = `<span>${label}</span><b>${plage}</b>`;
            planningList.appendChild(row);
        });
        show(planningSec);
    }

    async function identifier() {
        const tel = elTel.value.trim();
        if (!tel) { erreur('Entrez votre numéro de téléphone.'); return; }
        btnIdentifier.disabled = true;
        try {
            const data = await postJSON(URLS.identifier, { tel });
            if (!data.success) { erreur(data.error || 'Identification impossible.'); return; }

            confirmNom.textContent = data.nom;
            enCours = !!data.enCours;

            renderPlanning(data.creneaux);

            if (enCours) {
                confirmSub.textContent = 'Pointage en cours — vous pouvez le terminer.';
                hide(btnDemarrer); show(btnTerminer);
                hide(typeSection);
                // km « avec enfant » uniquement pour la garde (ENFA), pas le ménage.
                kmSection.classList.toggle('hidden', data.typeEnCours !== 'ENFA');
                // Récap horaires : début enregistré → fin = maintenant
                document.getElementById('h-debut').textContent = data.debut || '—';
                document.getElementById('h-fin').textContent = heureMaintenant();
                show(horairesSec);
            } else {
                confirmSub.textContent = data.occasionnel
                    ? 'Famille non planifiée → déclaration occasionnelle. Choisissez le type puis démarrez.'
                    : 'Confirmez puis démarrez votre pointage.';
                show(btnDemarrer); hide(btnTerminer);
                renderTypes(data.types);
                kmSection.classList.add('hidden');
                hide(horairesSec);
            }

            hide(etapeTel); show(etapeAction);
        } catch {
            erreur('Erreur réseau — réessayez.');
        } finally {
            btnIdentifier.disabled = false;
        }
    }

    async function demarrer() {
        if (!selectedType) { erreur('Choisissez le type de prestation.'); return; }
        btnDemarrer.disabled = true;
        try {
            const data = await postJSON(URLS.demarrer, { tel: elTel.value.trim(), type: selectedType, heure: heureMaintenant() });
            if (!data.success) { erreur(data.error || 'Démarrage impossible.'); return; }
            document.getElementById('fin-msg').textContent = 'Pointage démarré. Revenez scanner pour le terminer.';
            hide(etapeAction); show(etapeFin);
        } catch {
            erreur('Erreur réseau — réessayez.');
        } finally {
            btnDemarrer.disabled = false;
        }
    }

    async function terminer() {
        btnTerminer.disabled = true;
        try {
            const km = document.getElementById('km').value || null;
            const data = await postJSON(URLS.terminer, { tel: elTel.value.trim(), km, heure: heureMaintenant() });
            if (!data.success) { erreur(data.error || 'Clôture impossible.'); return; }
            document.getElementById('fin-msg').textContent = `Pointage enregistré : ${data.debut} → ${data.fin}.`;
            hide(etapeAction); show(etapeFin);
        } catch {
            erreur('Erreur réseau — réessayez.');
        } finally {
            btnTerminer.disabled = false;
        }
    }

    function retour() {
        hide(etapeAction); show(etapeTel);
    }

    btnIdentifier.addEventListener('click', identifier);
    btnDemarrer.addEventListener('click', demarrer);
    btnTerminer.addEventListener('click', terminer);
    btnRetour.addEventListener('click', retour);
    elTel.addEventListener('keydown', e => { if (e.key === 'Enter') identifier(); });
})();
