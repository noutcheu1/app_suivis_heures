/* ===========================================================================
 * Relevés intervenants filtres, sélection, tri, modales de téléchargement.
 * Le mois courant est exposé par le template via window.RELEVES_MOIS.
 * ======================================================================== */
const RELEVES_MOIS = window.RELEVES_MOIS || '';

let currentType = 'ALL';

// Conservé pour compatibilité (les sélections en masse l'utilisent)
const selectionOrder = [];
function trackSelection(id, checked) {
    const i = selectionOrder.indexOf(id);
    if (checked && i === -1) selectionOrder.push(id);
    else if (!checked && i !== -1) selectionOrder.splice(i, 1);
}

// Retourne les lignes cochées ET VISIBLES, dans l'ordre actuel du tableau.
// → reflète le tri (DOM réordonné) ; une ligne cochée puis exclue par un
//   filtre (masquée) n'est PAS téléchargée.
function getCheckedRowsOrdered() {
    return Array.from(document.querySelectorAll('#relevesBody tr'))
        .filter(tr => tr.style.display !== 'none' && tr.querySelector('.row-check')?.checked);
}

// Filtres actifs par groupe combinables
const activeFilters = { heures: '', type: '', statut: '', pdf: '', progression: '', dlDate: '', volume: '' };

function setFilter(groupe, val) {
    activeFilters[groupe] = activeFilters[groupe] === val ? '' : val;
    document.querySelectorAll(`.flt-btn[data-groupe="${groupe}"]`).forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === activeFilters[groupe]);
    });
    filterRows();
}

function resetFiltres() {
    Object.keys(activeFilters).forEach(k => activeFilters[k] = '');
    document.querySelectorAll('.flt-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('searchInput').value = '';
    filterRows();
}

/* ── Filtre combiné ── */
function filterRows() {
    const search   = document.getElementById('searchInput').value.toLowerCase();
    const fHeures  = activeFilters.heures;
    const fType    = activeFilters.type;
    const fStatut  = activeFilters.statut;
    const fPdf     = activeFilters.pdf;
    const fProg    = activeFilters.progression;
    const fDlDate  = activeFilters.dlDate;
    const fVolume  = activeFilters.volume;
    let visible    = 0;

    const now    = Math.floor(Date.now() / 1000);
    const today  = Math.floor(new Date().setHours(0,0,0,0) / 1000);
    const week7  = now - 7 * 86400;
    const d      = new Date();
    const month1 = Math.floor(new Date(d.getFullYear(), d.getMonth(), 1).getTime() / 1000);

    document.querySelectorAll('#relevesBody tr').forEach(row => {
        const nom        = row.dataset.nom       || '';
        const id         = row.dataset.id        || '';
        const mena       = parseFloat(row.dataset.mena       || 0);
        const enfa       = parseFloat(row.dataset.enfa       || 0);
        const typeMena   = row.dataset.typeMena  === '1';
        const typeEnfa   = row.dataset.typeEnfa  === '1';
        const signe      = row.dataset.signe     === '1';
        const telecharge = row.dataset.telecharge=== '1';
        const dlTs       = parseInt(row.dataset.dlTs         || 0, 10);
        const complet    = row.dataset.complet   === '1';
        const nonTraite  = row.dataset.nonTraite === '1';
        const enCours    = row.dataset.enCours   === '1';
        const deuxTypes  = row.dataset.deuxTypes === '1';
        const tot        = mena + enfa;

        const matchSearch = !search || nom.includes(search) || id.includes(search);

        const matchHeures = !fHeures
            || (fHeures === 'actif' && tot > 0)
            || (fHeures === 'vide'  && tot === 0);

        // Type = planning proposer OU heures réelles (inclut l'occasionnel sans planning)
        const aMena = typeMena || mena > 0;
        const aEnfa = typeEnfa || enfa > 0;
        const matchType = !fType
            || (fType === 'mena'      && aMena)
            || (fType === 'enfa'      && aEnfa)
            || (fType === 'deuxTypes' && aMena && aEnfa);

        const matchStatut = !fStatut
            || (fStatut === 'signe'    && tot > 0 && signe)
            || (fStatut === 'nonsigne' && tot > 0 && !signe);

        const matchPdf = !fPdf
            || (fPdf === 'telecharge'    && tot > 0 && telecharge)
            || (fPdf === 'nontelecharge' && tot > 0 && !telecharge);

        const matchProg = !fProg
            || (fProg === 'complet'   && complet)
            || (fProg === 'nontraite' && nonTraite)
            || (fProg === 'encours'   && enCours);

        const matchDlDate = !fDlDate
            || (fDlDate === 'today'  && dlTs >= today)
            || (fDlDate === 'week'   && dlTs >= week7)
            || (fDlDate === 'month'  && dlTs >= month1)
            || (fDlDate === 'jamais' && tot > 0 && dlTs === 0);

        const matchVolume = !fVolume
            || (fVolume === 'low'  && tot > 0  && tot < 20)
            || (fVolume === 'mid'  && tot >= 20 && tot <= 60)
            || (fVolume === 'high' && tot > 60);

        const show = matchSearch && matchHeures && matchType && matchStatut && matchPdf
                  && matchProg && matchDlDate && matchVolume;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('emptyMsg').style.display = visible === 0 ? 'block' : 'none';
    document.getElementById('resultCount').textContent = visible + ' résultat' + (visible > 1 ? 's' : '');
    saveFilterState();
    updateCount();
}

/* ── Persistance des filtres (survit au rechargement, ex. changement de mois) ── */
const FILTER_LS_KEY = 'releves_filtres_v1';

function saveFilterState() {
    try {
        localStorage.setItem(FILTER_LS_KEY, JSON.stringify({
            mois:    RELEVES_MOIS,
            filters: { ...activeFilters },
            search:  document.getElementById('searchInput').value,
        }));
    } catch (e) {}
}

function loadFilterState() {
    try {
        const raw = localStorage.getItem(FILTER_LS_KEY);
        if (!raw) return;
        const state = JSON.parse(raw);
        if (state.mois !== RELEVES_MOIS) return; // filtres propres à chaque mois

        Object.keys(activeFilters).forEach(k => {
            if (state.filters && state.filters[k]) {
                activeFilters[k] = state.filters[k];
                document.querySelectorAll(`.flt-btn[data-groupe="${k}"]`).forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.filter === activeFilters[k]);
                });
            }
        });
        if (state.search) document.getElementById('searchInput').value = state.search;

        filterRows();
    } catch (e) {}
}

/* Filtre rapide depuis l'icône colonne téléchargé */
function toggleFilterTelecharge() {
    const sel = document.getElementById('filterType');
    sel.value = sel.value === 'nontelecharge' ? 'telecharge' : 'nontelecharge';
    filterRows();
}

/* ── Sélection ── */
function toggleAll(master) {
    document.querySelectorAll('#relevesBody tr').forEach(row => {
        if (row.style.display !== 'none') row.querySelector('.row-check').checked = master.checked;
    });
    updateCount();
}
function selectVisible() {
    document.querySelectorAll('#relevesBody tr').forEach(row => {
        if (row.style.display !== 'none') row.querySelector('.row-check').checked = true;
    });
    document.getElementById('checkAll').checked = true;
    updateCount();
}
function selectionnerType(type) {
    // Active le filtre type puis sélectionne toutes les lignes visibles
    setFilter('type', type);
    // Si le filtre heures n'est pas actif, l'activer automatiquement (inutile de cocher les vides)
    if (!activeFilters.heures) setFilter('heures', 'actif');
    filterRows();
    document.querySelectorAll('#relevesBody tr').forEach(row => {
        if (row.style.display !== 'none') row.querySelector('.row-check').checked = true;
    });
    updateCount();
}

/* Presets combinés */
function presetAImprimer() {
    // Avec heures + non téléchargé → lignes prêtes à être imprimées par l'admin
    resetFiltres();
    activeFilters.heures = 'actif';
    activeFilters.pdf    = 'nontelecharge';
    document.querySelectorAll('.flt-btn[data-groupe="heures"][data-filter="actif"]').forEach(b => b.classList.add('active'));
    document.querySelectorAll('.flt-btn[data-groupe="pdf"][data-filter="nontelecharge"]').forEach(b => b.classList.add('active'));
    filterRows();
    // Sélectionner automatiquement les lignes visibles
    document.querySelectorAll('#relevesBody tr').forEach(row => {
        if (row.style.display !== 'none') row.querySelector('.row-check').checked = true;
    });
    updateCount();
}

function presetASigner() {
    // Avec heures + non signé → lignes en attente de signature
    resetFiltres();
    activeFilters.heures = 'actif';
    activeFilters.statut = 'nonsigne';
    document.querySelectorAll('.flt-btn[data-groupe="heures"][data-filter="actif"]').forEach(b => b.classList.add('active'));
    document.querySelectorAll('.flt-btn[data-groupe="statut"][data-filter="nonsigne"]').forEach(b => b.classList.add('active'));
    filterRows();
    document.querySelectorAll('#relevesBody tr').forEach(row => {
        if (row.style.display !== 'none') row.querySelector('.row-check').checked = true;
    });
    updateCount();
}
function deselectAll() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    document.getElementById('checkAll').checked = false;
    selectionOrder.length = 0;
    updateCount();
}

function updateCount() {
    const n   = getCheckedRowsOrdered().length; // cochés ET visibles
    const bar = document.getElementById('floatBar');
    document.getElementById('floatCount').textContent = n + ' intervenant' + (n > 1 ? 's' : '') + ' sélectionné' + (n > 1 ? 's' : '');
    bar.classList.toggle('visible', n > 0);
    document.querySelectorAll('#relevesBody tr').forEach(row => {
        row.classList.toggle('selected-row', !!row.querySelector('.row-check')?.checked);
    });
}

/* ── Modal format ── */
function ouvrirModal(type) {
    const checked = getCheckedRowsOrdered(); // cochés ET visibles
    if (!checked.length) return;
    currentType = type;
    const label = type === 'MENA' ? 'ménage' : type === 'ENFA' ? 'garde d\'enfant' : 'ménage + garde';
    document.getElementById('modalFormatSub').textContent =
        checked.length + ' intervenant(s) sélectionné(s) Type : ' + label;
    document.getElementById('modalFormat').classList.add('open');
}
function fermerModal() {
    document.getElementById('modalFormat').classList.remove('open');
}
function telecharger(format) {
    fermerModal();
    // Lignes cochées DANS L'ORDRE DE SÉLECTION
    const rows    = getCheckedRowsOrdered();
    const params  = new URLSearchParams();
    params.set('type', currentType);
    params.set('mois', RELEVES_MOIS);
    rows.forEach(row => params.append('ids[]', row.dataset.id));

    if (format === 'groupe') {
        window.location.href = '/admin-mvc/releves-batch?' + params.toString();
    } else {
        // PDFs séparés : construire une liste de liens cliquables
        // (window.open asynchrone = bloqué par le popup blocker des navigateurs)
        const list = document.getElementById('modalIndividuelList');
        list.innerHTML = '';
        let count = 0;

        rows.forEach(row => {
            const nomEl = row.querySelector('td:nth-child(2)');
            const nom   = nomEl ? nomEl.firstElementChild?.textContent.trim() || nomEl.textContent.trim() : '#' + row.dataset.id;
            const mena  = parseFloat(row.dataset.mena || 0);
            const enfa  = parseFloat(row.dataset.enfa || 0);

            const addLink = (url, label, color) => {
                const a = document.createElement('a');
                a.href   = url;
                a.target = '_blank';
                a.rel    = 'noopener';
                a.style.cssText = `display:flex;align-items:center;gap:10px;padding:9px 14px;
                    border-radius:8px;background:${color}10;border:1px solid ${color}40;
                    color:#111;text-decoration:none;font-size:13px;font-weight:500;
                    transition:opacity .2s;`;
                a.innerHTML = `<i class="fas fa-file-arrow-down" style="color:${color};font-size:14px;flex-shrink:0;"></i>
                    <span>${nom}</span>
                    <span style="margin-left:auto;font-size:11px;color:${color};font-weight:600;white-space:nowrap;">${label}</span>`;
                a.addEventListener('click', () => {
                    setTimeout(() => { a.style.opacity = '0.45'; }, 100);
                });
                list.appendChild(a);
                count++;
            };

            if ((currentType === 'MENA' || currentType === 'ALL') && mena > 0)
                addLink(row.dataset.urlMena, 'Ménage', '#3b82f6');
            if ((currentType === 'ENFA' || currentType === 'ALL') && enfa > 0)
                addLink(row.dataset.urlEnfa, "Garde d'enfants", '#22c55e');
        });

        document.getElementById('modalIndividuelCount').textContent =
            count + ' PDF' + (count > 1 ? 's' : '') + ' à ouvrir';
        document.getElementById('modalIndividuel').style.display = 'flex';
    }
}

/* Ouvre tous les liens du modal individuels en une fois (certains navigateurs l'autorisent) */
function ouvrirTousLesPdfs() {
    document.querySelectorAll('#modalIndividuelList a').forEach(a => {
        window.open(a.href, '_blank', 'noopener');
        a.style.opacity = '0.45';
    });
}

/* ── Tri ── */
let sortDir = {};
function sortTable(col) {
    const tbody = document.getElementById('relevesBody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    sortDir[col] = !sortDir[col];
    const dir = sortDir[col] ? 1 : -1;
    rows.sort((a, b) => {
        const ca = a.querySelectorAll('td')[col];
        const cb = b.querySelectorAll('td')[col];
        // Priorité au data-sort (ex. timestamp pour la date de dernière saisie)
        const sa = ca?.dataset.sort, sb = cb?.dataset.sort;
        if (sa !== undefined && sb !== undefined) {
            return ((parseFloat(sa) || 0) - (parseFloat(sb) || 0)) * dir;
        }
        const ta = ca?.textContent.trim() ?? '';
        const tb = cb?.textContent.trim() ?? '';
        const na = parseFloat(ta), nb = parseFloat(tb);
        if (!isNaN(na) && !isNaN(nb)) return (na - nb) * dir;
        return ta.localeCompare(tb, 'fr') * dir;
    });
    rows.forEach(r => tbody.appendChild(r));
}

// Tri dédié à la colonne « Type de prestation » (basé sur le planning proposer,
// via data-type-mena / data-type-enfa de la ligne pas de cellule numérique).
// Rang : Ménage seul (1) < Les deux (2) < Garde seule (3) < Aucun (4).
let sortTypeDir = false;
function sortType() {
    const tbody = document.getElementById('relevesBody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    sortTypeDir = !sortTypeDir;
    const dir = sortTypeDir ? 1 : -1;
    const rank = (tr) => {
        const m = tr.dataset.typeMena === '1';
        const e = tr.dataset.typeEnfa === '1';
        if (m && !e) return 1;
        if (m && e)  return 2;
        if (!m && e) return 3;
        return 4;
    };
    rows.sort((a, b) => {
        const diff = (rank(a) - rank(b)) * dir;
        if (diff !== 0) return diff;
        // À type égal : tri alphabétique sur le nom (colonne 1)
        const na = a.querySelectorAll('td')[1]?.textContent.trim() ?? '';
        const nb = b.querySelectorAll('td')[1]?.textContent.trim() ?? '';
        return na.localeCompare(nb, 'fr');
    });
    rows.forEach(r => tbody.appendChild(r));
}

/* ── Initialisation ── */
document.addEventListener('DOMContentLoaded', () => {
    // Restaurer les filtres sauvegardés
    loadFilterState();

    // Fermer modal en cliquant à l'extérieur
    const modalFormat = document.getElementById('modalFormat');
    if (modalFormat) {
        modalFormat.addEventListener('click', e => {
            if (e.target === modalFormat) fermerModal();
        });
    }

    // Suivi de l'ordre de sélection (clics individuels sur les cases)
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', () => trackSelection(cb.dataset.id, cb.checked));
    });
});
