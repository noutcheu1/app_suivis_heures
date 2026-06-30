/**
 * Page d'attente de signature du relevé.
 * Orchestration uniquement (aucune règle métier ici) :
 *   1. génère le PDF (iframe "pdfmode=email")
 *   2. appelle l'endpoint existant POST /api/intervenants/{id}/signer-email
 *   3. affiche l'état : en cours (horloge) → succès / échec
 */
(function () {
    const el      = document.getElementById('signePage');
    if (!el) return;
    const ID      = el.dataset.id;
    const type    = (el.dataset.type || 'ENFA').toUpperCase();
    const periode = el.dataset.periode || '';   // = periodeFin (dernier jour de période)
    const mois    = el.dataset.mois || '0';

    let enCours = false; // anti double-envoi

    function afficherEtat(etat) {
        ['etat-encours', 'etat-ok', 'etat-ko'].forEach(s => {
            const node = document.getElementById(s);
            if (node) node.style.display = (s === 'etat-' + etat) ? 'block' : 'none';
        });
    }
    // emailEchoue=true → on propose le bouton « Réessayer l'envoi ».
    function ok(message, emailEchoue) {
        document.getElementById('ok-msg').textContent = message;
        const btn = document.getElementById('btn-renvoyer');
        if (btn) btn.style.display = emailEchoue ? 'inline-flex' : 'none';
        afficherEtat('ok');
    }
    function ko(message)  { document.getElementById('ko-msg').textContent = message || 'Une erreur est survenue.'; afficherEtat('ko'); }

    // (a) Génération du PDF même technique que la fiche (iframe cachée → base64).
    function genererPdf() {
        return new Promise((resolve) => {
            const url = `/releves-mvc/intervenant/${ID}/pdf?type=${encodeURIComponent(type)}&mois=${encodeURIComponent(mois)}&pdfmode=email`;
            const iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:absolute;width:0;height:0;border:0;left:-9999px;';
            let done = false;
            function onMessage(e) {
                if (!e.data || e.data.type !== 'relevePdfBase64') return;
                done = true; cleanup(); resolve(e.data.data || null);
            }
            function cleanup() {
                window.removeEventListener('message', onMessage);
                if (iframe.parentNode) iframe.parentNode.removeChild(iframe);
            }
            window.addEventListener('message', onMessage);
            iframe.src = url;
            document.body.appendChild(iframe);
            setTimeout(() => { if (!done) { cleanup(); resolve(null); } }, 15000); // garde-fou
        });
    }

    // (b) Appel de l'endpoint existant (aucune logique métier ici).
    async function envoyer(pdfBase64) {
        const res = await fetch(`/api/intervenants/${ID}/signer-email`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                type,
                periodeFin: periode,
                pdfBase64,
                filename: `Releve_${type}_${periode}.pdf`,
            }),
        });
        return res.json().catch(() => ({ success: false, message: 'Réponse invalide du serveur.' }));
    }

    // (c) Orchestrateur.
    async function run() {
        if (enCours) return;
        enCours = true;
        afficherEtat('encours');

        if (!periode) { ko('Contexte manquant (période).'); return; }

        let pdf = null;
        try { pdf = await genererPdf(); } catch (e) { console.warn('[signe] PDF non généré', e); }

        try {
            const res = await envoyer(pdf);
            if (!res.success) {
                // Relevé déjà signé / hors période → message renvoyé par le service.
                ko(res.message);
                return;
            }
            // On n'affiche JAMAIS l'erreur technique (SMTP, etc.) à l'utilisateur :
            // la signature a réussi, c'est le seul message qui compte.
            const emailEchoue = res.emailEnvoye === false;
            ok(emailEchoue
                ? 'Votre relevé est bien signé. (L\'email de confirmation n\'a pas pu être envoyé.)'
                : 'Votre relevé est signé et envoyé par email.', emailEchoue);
        } catch (err) {
            console.error('[signe] erreur réseau', err);
            ko('Erreur réseau lors de la signature.');
        } finally {
            enCours = false; // autorise un renvoi
        }
    }

    // Bouton « Réessayer l'envoi » : re-génère le PDF et rappelle l'endpoint
    // (signerReleve est idempotent → ne re-signe pas en double, renvoie juste l'email).
    const btnRenvoyer = document.getElementById('btn-renvoyer');
    if (btnRenvoyer) {
        btnRenvoyer.addEventListener('click', async () => {
            btnRenvoyer.disabled = true;
            btnRenvoyer.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi…';
            await run();
            btnRenvoyer.disabled = false;
            btnRenvoyer.innerHTML = '<i class="fas fa-paper-plane"></i> Réessayer l\'envoi de l\'email';
        });
    }

    run();
})();
