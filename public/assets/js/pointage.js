/* Pointage QR sans connexion : numéro → confirmation nom → Démarrer / Terminer. */
(function () {
    const card  = document.getElementById('card');
    const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const URLS  = {
        identifier: card.dataset.urlIdentifier,
        demarrer:   card.dataset.urlDemarrer,
        terminer:   card.dataset.urlTerminer,
        cloturer:   card.dataset.urlCloturer,
        modifier:   card.dataset.urlModifier,
    };
    // Contexte « garde » : type imposé (ENFA) → on n'affiche pas le choix de service.
    const FORCE_TYPE = (card.dataset.forceType || '').toUpperCase();

    const elTel        = document.getElementById('tel');
    const etapeTel     = document.getElementById('etape-tel');
    const etapeOubli   = document.getElementById('etape-oubli');
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
    const btnModifier  = document.getElementById('btn-modifier');
    const modal        = document.getElementById('modal-heures');
    const btnIdentifier= document.getElementById('btn-identifier');
    const btnDemarrer  = document.getElementById('btn-demarrer');
    const btnTerminer  = document.getElementById('btn-terminer');
    const btnRetour    = document.getElementById('btn-retour');

    let selectedType = null;
    let enCours = false;
    let debutEnCours = null; // heure de début enregistrée (pour le pop-up de modification)

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

    // Heures à envoyer au Démarrer/Terminer normal : tout en automatique.
    //  - heure (arrondie, affichée au relevé) = maintenant arrondi au quart d'heure ;
    //  - heureReelle = heure réelle du téléphone (jamais modifiée, sert de trace).
    // (La correction manuelle se fait via le pop-up « Modifier les heures ».)
    function heuresAEnvoyer() {
        return { heure: heureMaintenant(), heureReelle: heureReelleMaintenant() };
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

    // Les 2 services sont TOUJOURS affichés ET cliquables. Il peut changer ; choisir un
    // service non prévu → « occasionnel » (serveur).
    // Pré-sélection par défaut (priorité) :
    //   1. le service planifié de la famille s'il est unique ;
    //   2. sinon le service de l'intervenant s'il n'en fait qu'un.
    // `planifies` = services planifiés de la famille ; `services` = services de l'intervenant.
    function renderTypes(planifies, services, occasionnel) {
        typeRow.innerHTML = '';
        selectedType = null;
        const prevus   = (Array.isArray(planifies) ? planifies : []).map(s => String(s).toUpperCase());
        const dispoInt = (Array.isArray(services)  ? services  : []).map(s => String(s).toUpperCase());

        // Quel service activer par défaut ?
        let defaut = null;
        if (prevus.length === 1)        defaut = prevus[0];
        else if (dispoInt.length === 1) defaut = dispoInt[0];
        // Occasionnel : la famille n'a pas de service planifié pour cet intervenant →
        // on active SON service par défaut (le service qu'il effectue).
        if (!defaut && occasionnel && dispoInt.length) defaut = dispoInt[0];

        show(typeSection);
        ['MENA', 'ENFA'].forEach(t => {
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
            if (defaut === t) {
                selectedType = t;
                b.classList.add('active');
                kmSection.classList.toggle('hidden', t !== 'ENFA');
            }
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

            // PRIORITÉ : un pointage précédent non terminé → on le fait clôturer d'abord.
            if (Array.isArray(data.oublis) && data.oublis.length > 0) {
                afficherOubli(data.nom, data.oublis[0]);
                hide(etapeTel);
                return;
            }

            confirmNom.textContent = data.nom;
            enCours = !!data.enCours;

            renderPlanning(data.creneaux);

            if (enCours) {
                confirmSub.textContent = 'Pointage en cours vous pouvez le terminer.';
                hide(btnDemarrer); show(btnTerminer);
                hide(typeSection);
                // km « avec enfant » uniquement pour la garde (ENFA), pas le ménage.
                kmSection.classList.toggle('hidden', data.typeEnCours !== 'ENFA');
                // Recharge le km déjà saisi (s'il y en a un) pour ne pas le perdre.
                document.getElementById('km').value = (data.km !== null && data.km !== undefined) ? data.km : '';
                // Récap horaires : début enregistré → fin = maintenant
                document.getElementById('h-debut-label').textContent = 'Début';
                document.getElementById('h-debut').textContent = data.debut || '—';
                document.getElementById('h-fin').textContent = heureMaintenant();
                show(document.getElementById('h-fin-line'));
                show(horairesSec);
            } else {
                confirmSub.textContent = data.occasionnel
                    ? 'Famille non planifiée → déclaration occasionnelle. Choisissez le type puis démarrez.'
                    : 'Confirmez puis démarrez votre pointage.';
                show(btnDemarrer); hide(btnTerminer);
                if (FORCE_TYPE) {
                    // Contexte garde : type imposé, aucun choix de service à afficher.
                    selectedType = FORCE_TYPE;
                    hide(typeSection);
                    confirmSub.textContent = 'Garde d\'enfant — confirmez puis démarrez.';
                    // km « avec enfant » pertinent pour la garde (ENFA).
                    kmSection.classList.toggle('hidden', FORCE_TYPE !== 'ENFA');
                } else {
                    // Défaut = service planifié de la famille, sinon service de l'intervenant ;
                    // les 2 restent cliquables (l'autre → occasionnel).
                    renderTypes(data.types, data.servicesIntervenant, data.occasionnel);
                    kmSection.classList.add('hidden');
                }
                // Avant de démarrer : on indique l'heure à laquelle le comptage va commencer.
                document.getElementById('h-debut-label').textContent = 'Vous commencez à compter à';
                document.getElementById('h-debut').textContent = heureMaintenant();
                hide(document.getElementById('h-fin-line'));
                show(horairesSec);
            }

            // Début courant (enregistré) mémorisé pour le pop-up de modification.
            debutEnCours = data.debut || null;
            // Le bouton « Modifier les heures » n'apparaît qu'une fois démarré (en cours).
            btnModifier.classList.toggle('hidden', !enCours);

            hide(etapeTel); show(etapeAction);
        } catch {
            erreur('Erreur réseau réessayez.');
        } finally {
            btnIdentifier.disabled = false;
        }
    }

    async function demarrer() {
        if (!selectedType) { erreur('Choisissez le type de prestation.'); return; }
        btnDemarrer.disabled = true;
        try {
            const h = heuresAEnvoyer();
            // km saisi dès le démarrage (garde) → enregistré pour rechargement.
            const km = document.getElementById('km').value || null;
            const data = await postJSON(URLS.demarrer, { tel: elTel.value.trim(), type: selectedType, heure: h.heure, heureReelle: h.heureReelle, km });
            if (!data.success) { erreur(data.error || 'Démarrage impossible.'); return; }
            document.getElementById('fin-msg').textContent = 'Pointage démarré. Revenez scanner pour le terminer.';
            hide(etapeAction); show(etapeFin);
        } catch {
            erreur('Erreur réseau réessayez.');
        } finally {
            btnDemarrer.disabled = false;
        }
    }

    async function terminer() {
        btnTerminer.disabled = true;
        try {
            const km = document.getElementById('km').value || null;
            const h = heuresAEnvoyer();
            const data = await postJSON(URLS.terminer, { tel: elTel.value.trim(), km, heure: h.heure, heureReelle: h.heureReelle });
            if (!data.success) { erreur(data.error || 'Clôture impossible.'); return; }
            document.getElementById('fin-msg').textContent = `Pointage enregistré : ${data.debut} → ${data.fin}.`;
            hide(etapeAction); show(etapeFin);
        } catch {
            erreur('Erreur réseau réessayez.');
        } finally {
            btnTerminer.disabled = false;
        }
    }

    function retour() {
        hide(etapeAction); show(etapeTel);
    }

    // ── Récupération d'oubli ────────────────────────────────────────────────
    let oubliCourant = null;

    // Remplit les sélecteurs heures (0-23) / minutes (pas de 5) et pré-sélectionne "HH:MM".
    function remplirHeureSelects(selH, selM, valeur) {
        selH.innerHTML = ''; selM.innerHTML = '';
        for (let h = 0; h < 24; h++) {
            const o = document.createElement('option');
            o.value = String(h).padStart(2, '0'); o.textContent = o.value;
            selH.appendChild(o);
        }
        for (let m = 0; m < 60; m += 5) {
            const o = document.createElement('option');
            o.value = String(m).padStart(2, '0'); o.textContent = o.value;
            selM.appendChild(o);
        }
        const [hh, mm] = (valeur || '').split(':');
        if (hh !== undefined) selH.value = String(parseInt(hh, 10)).padStart(2, '0');
        if (mm !== undefined) {
            const mArr = Math.round(parseInt(mm, 10) / 5) * 5; // aligne sur le pas de 5
            selM.value = String(Math.min(55, mArr)).padStart(2, '0');
        }
    }

    function afficherOubli(nom, o) {
        oubliCourant = o;
        document.getElementById('oubli-nom').textContent = nom;
        document.getElementById('oubli-fam').textContent = o.nomFam || o.numFam || 'Famille occasionnelle';
        document.getElementById('oubli-debut').textContent = o.heureDebut || '—';
        document.getElementById('oubli-date').textContent = o.aujourdhui ? "aujourd'hui" : o.date;
        document.getElementById('oubli-prevu').textContent = o.finPrevue ? `Heure de fin prévue : ${o.finPrevue}` : '';
        hide(etapeAction); hide(etapeFin); show(etapeOubli);
    }

    // Clôture d'un oubli : on ouvre le MÊME pop-up (début + fin modifiables).
    function ouvrirModalOubli() {
        if (!oubliCourant) return;
        ouvrirModal(
            oubliCourant.heureDebut,
            oubliCourant.finPrevue || oubliCourant.heureDebut,
            { type: 'oubli', id: oubliCourant.id }
        );
    }

    btnIdentifier.addEventListener('click', identifier);
    btnDemarrer.addEventListener('click', demarrer);
    btnTerminer.addEventListener('click', terminer);
    btnRetour.addEventListener('click', retour);
    document.getElementById('btn-cloturer').addEventListener('click', ouvrirModalOubli);
    elTel.addEventListener('keydown', e => { if (e.key === 'Enter') identifier(); });

    // ── Pop-up « Modifier les heures » (début + fin affichés ; l'heure réelle ne change pas) ──
    // Contexte : { type:'terminer' } (pointage courant) ou { type:'oubli', id } (oubli à clôturer).
    let modalCtx = { type: 'terminer' };

    function ouvrirModal(debutVal, finVal, ctx) {
        modalCtx = ctx || { type: 'terminer' };
        remplirHeureSelects(document.getElementById('mod-debut-h'), document.getElementById('mod-debut-m'),
            debutVal || heureMaintenant());
        remplirHeureSelects(document.getElementById('mod-fin-h'), document.getElementById('mod-fin-m'),
            finVal || heureMaintenant());
        modal.style.display = 'flex';
    }
    function fermerModal() { modal.style.display = 'none'; }

    async function enregistrerModal() {
        const debut = document.getElementById('mod-debut-h').value + ':' + document.getElementById('mod-debut-m').value;
        const fin   = document.getElementById('mod-fin-h').value + ':' + document.getElementById('mod-fin-m').value;
        const btn = document.getElementById('mod-save');
        btn.disabled = true;
        try {
            // Cas OUBLI : clôture d'un pointage précédent (début + fin corrigés, réelle intacte).
            if (modalCtx.type === 'oubli') {
                const data = await postJSON(URLS.cloturer, {
                    tel: elTel.value.trim(), horaireId: modalCtx.id, heure: fin, heureDebut: debut,
                });
                if (!data.success) { erreur(data.error || 'Clôture impossible.'); return; }
                if (window.toast) toast('Pointage précédent clôturé.', 'success');
                fermerModal(); hide(etapeOubli);
                identifier(); // oubli suivant, ou flux normal
                return;
            }

            // Cas NORMAL : MET À JOUR les heures déclarées SANS terminer (reste en cours).
            const data = await postJSON(URLS.modifier, {
                tel: elTel.value.trim(), heureDebut: debut, heureFin: fin,
            });
            if (!data.success) { erreur(data.error || 'Modification impossible.'); return; }
            // Reflète les nouvelles heures à l'écran ; le pointage CONTINUE.
            debutEnCours = data.debut || debut;
            document.getElementById('h-debut-label').textContent = 'Début';
            document.getElementById('h-debut').textContent = data.debut || debut;
            document.getElementById('h-fin').textContent = data.fin || fin;
            if (window.toast) toast('Heures mises à jour. Le comptage continue.', 'success');
            fermerModal();
        } catch {
            erreur('Erreur réseau — réessayez.');
        } finally {
            btn.disabled = false;
        }
    }

    btnModifier.addEventListener('click', () => ouvrirModal(debutEnCours, heureMaintenant(), { type: 'terminer' }));
    document.getElementById('mod-cancel').addEventListener('click', fermerModal);
    document.getElementById('mod-save').addEventListener('click', enregistrerModal);
    modal.addEventListener('click', e => { if (e.target === modal) fermerModal(); });

    // Intervenant CONNECTÉ : son numéro est pré-rempli côté serveur → identification
    // automatique (on saute l'étape « saisie du numéro »), puis même flux.
    const telPrefill = (card.dataset.telPrefill || '').trim();
    if (telPrefill) {
        elTel.value = telPrefill;
        identifier();
    }
})();
