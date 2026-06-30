/**
 * html-to-pdf.js
 * Utilitaire générique HTML → PDF via html2canvas + jsPDF.
 *
 * Dépendances (à charger avant ce script) :
 *   jsPDF      : https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js
 *   html2canvas: https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js
 *
 * Usage :
 *   await pagesToPDF('.doc-page', 'releve.pdf');
 *   await pagesToPDF('.doc-page', 'releve.pdf', { scale: 3, margin: 5 });
 *
 * Options :
 *   scale       {number}   Résolution html2canvas (défaut 2 → qualité × 2)
 *   orientation {string}   'p' portrait | 'l' paysage (défaut 'p')
 *   format      {string}   Format papier jsPDF (défaut 'a4')
 *   margin      {number}   Marges en mm (défaut 8)
 *   quality     {number}   Qualité JPEG 0–1 (défaut 0.92)
 *   onProgress  {Function} Callback(pageActuelle, totalPages)
 *   onError     {Function} Callback(erreur) si absent, l'erreur est relancée
 */
async function pagesToPDF(selector, filename = 'document.pdf', options = {}) {
    const {
        scale       = 2,
        orientation = 'p',
        format      = 'a4',
        margin      = 8,
        quality     = 0.92,
        onProgress  = null,
        onError     = null,
    } = options;

    if (!window.jspdf)      { const e = new Error('jsPDF non chargée ajoutez le script jsPDF avant html-to-pdf.js');      if (onError) { onError(e); return; } throw e; }
    if (!window.html2canvas){ const e = new Error('html2canvas non chargée ajoutez le script html2canvas avant html-to-pdf.js'); if (onError) { onError(e); return; } throw e; }

    const elements = [...document.querySelectorAll(selector)];
    if (!elements.length) {
        const e = new Error(`Aucun élément trouvé pour le sélecteur : "${selector}"`);
        if (onError) { onError(e); return; } throw e;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF(orientation, 'mm', format);

    const pageW  = doc.internal.pageSize.getWidth();
    const pageH  = doc.internal.pageSize.getHeight();
    const printW = pageW  - margin * 2;
    const printH = pageH  - margin * 2;

    let firstPdfPage = true;

    for (let i = 0; i < elements.length; i++) {
        onProgress?.(i + 1, elements.length);

        const canvas = await html2canvas(elements[i], {
            scale,
            useCORS:         true,
            allowTaint:      false,
            logging:         false,
            backgroundColor: '#ffffff',
        });

        const canvasW   = canvas.width;
        const canvasH   = canvas.height;
        const pxPerMm   = canvasW / printW;   // pixels canvas par mm PDF
        const sliceHpx  = printH * pxPerMm;   // hauteur d'une tranche A4 en pixels canvas

        let yOffset = 0;

        while (yOffset < canvasH) {
            if (!firstPdfPage) doc.addPage();
            firstPdfPage = false;

            // Découpe la portion de canvas correspondant à une page A4
            const sliceCanvas = document.createElement('canvas');
            const sliceHActual = Math.min(sliceHpx, canvasH - yOffset);
            sliceCanvas.width  = canvasW;
            sliceCanvas.height = sliceHActual;

            const ctx = sliceCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvasW, sliceHActual);
            ctx.drawImage(canvas, 0, -yOffset);

            const imgData = sliceCanvas.toDataURL('image/jpeg', quality);
            const imgHmm  = sliceHActual / pxPerMm;

            doc.addImage(imgData, 'JPEG', margin, margin, printW, imgHmm);
            yOffset += sliceHpx;
        }
    }

    doc.save(filename);
}
