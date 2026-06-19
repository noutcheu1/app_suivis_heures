console.log('=== [app.js] chargé ===');
console.log('[app.js] Bootstrap disponible :', typeof bootstrap !== 'undefined' ? 'OUI' : 'NON (chargement asynchrone possible)');
console.log('[app.js] HoursCalculator disponible :', typeof window.HoursCalculator !== 'undefined' ? 'OUI' : 'NON');

/**
 * Toast réutilisable — remplace les alert() natifs.
 * Usage : toast('Message'), toast('Erreur…', 'error'), toast('OK', 'success').
 * Disponible globalement via window.toast.
 */
(function () {
    if (window.toast) return; // évite la double définition

    const STYLE_ID = 'app-toast-style';
    if (!document.getElementById(STYLE_ID)) {
        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .app-toast-container{position:fixed;top:16px;left:50%;transform:translateX(-50%);
                z-index:99999;display:flex;flex-direction:column;gap:8px;
                width:max-content;max-width:90vw;pointer-events:none;}
            .app-toast{pointer-events:auto;padding:12px 18px;border-radius:10px;
                font-family:Inter,system-ui,sans-serif;font-size:14px;font-weight:500;
                color:#fff;box-shadow:0 6px 24px rgba(0,0,0,.18);
                opacity:0;transform:translateY(-8px);transition:opacity .25s,transform .25s;
                display:flex;align-items:center;gap:10px;}
            .app-toast.show{opacity:1;transform:translateY(0);}
            .app-toast--error{background:#dc2626;}
            .app-toast--success{background:#16a34a;}
            .app-toast--info{background:#334155;}
        `;
        document.head.appendChild(style);
    }

    function getContainer() {
        let c = document.querySelector('.app-toast-container');
        if (!c) {
            c = document.createElement('div');
            c.className = 'app-toast-container';
            document.body.appendChild(c);
        }
        return c;
    }

    window.toast = function (message, type = 'error', duree = 4000) {
        if (!message) return;
        const el = document.createElement('div');
        el.className = `app-toast app-toast--${type}`;
        el.setAttribute('role', 'alert');
        el.textContent = message;
        getContainer().appendChild(el);
        requestAnimationFrame(() => el.classList.add('show'));
        setTimeout(() => {
            el.classList.remove('show');
            setTimeout(() => el.remove(), 300);
        }, duree);
    };
})();