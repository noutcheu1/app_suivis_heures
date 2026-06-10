function soulignementPDF(data, offset = 0) {
    const { cell, doc } = data;
    const text = cell.text.join(' ');
    const textWidth = doc.getTextWidth(text);
    let x;

    switch (cell.styles.halign) {
        case 'center':
            x = cell.x + (cell.width - textWidth) / 2;
            break;
        case 'right':
            x = cell.x + cell.width - textWidth - cell.padding('right');
            break;
        case 'left':
        default:
            x = cell.x + cell.padding('left');
    }

    const y = cell.y + cell.height - cell.padding('bottom') - offset;
    doc.setLineWidth(0.25);
    doc.line(x, y, x + textWidth, y);
}


function applyStylesByClass(data) {
    const el = data.cell.raw;
    if (!el) return;

    const hasClass = (cls) =>
        el.classList?.contains(cls) ||
        el.parentElement?.classList?.contains(cls);

    if (hasClass('cell-position-left')) {
        data.cell.styles.halign = 'left';
    }
    if (hasClass('cell-position-right')) {
        data.cell.styles.halign = 'right';
    }
    if (hasClass('no-borders')) {
        data.cell.styles.lineWidth = 0;
    }
    if (hasClass('title')) {
        data.cell.styles.fontSize = 11;   // réduit de 15 à 11
    }
    if (hasClass('title') || hasClass('bold')) {
        data.cell.styles.fontStyle = 'bold';
    }

    // Titre (souligné + plus gros)
    if (hasClass('dim_villes')) {
        data.cell.styles.fontSize = 1;
       
        // Le soulignement n’existe pas directement dans autoTable, on peut ajouter un trait plus tard (optionnel)
    }
    if (hasClass('title')) {
        
        data.cell.styles.fontStyle = 'bold';
        data.cell.styles.halign = 'center';
        // Le soulignement n’existe pas directement dans autoTable, on peut ajouter un trait plus tard (optionnel)
    }
    // Optionnel : forcer couleur de fond pour dimanche (décommentez si besoin)
    if (hasClass('dim_villes')) {
        data.cell.styles.fillColor = [224, 224, 224];
    }
}

function applyComplexStyleByClass(data) {
    const el = data.cell.raw;
    if (!el) return;
    const hasClass = (cls) =>
        el.classList?.contains(cls) ||
        el.parentElement?.classList?.contains(cls);
    if (data.section === 'body' && hasClass('title')) {
        soulignementPDF(data);
    }
}

async function genererPDF(type_de_garde, anne_file) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const pageHeight = doc.internal.pageSize.getHeight(); // 297 mm
    const marginTop = 8;      // marge haute
    const marginBottom = 8;    // marge basse
    let index = 1;
    let premierePage = true;

    // Styles très compacts pour tenir sur une page
    const styles = {
        fontSize: 7,            // réduction de 9 à 7
        cellPadding: 0.5,       // réduit de 0.70 à 0.5
        valign: 'middle',
        halign: 'center',
        lineWidth: 0.1,
    };

    const headStyles = {
        fillColor: [0, 255, 255],
        textColor: [0, 75, 75],
        fontStyle: 'bold',
        lineColor: [51, 51, 51],
        lineWidth: 0.1,
    };

    const bodyStyles = {
        lineColor: [51, 51, 51],
        lineWidth: 0.1,
    };

    while (document.getElementById('monTableau1' + index)) {
        if (!premierePage) {
            doc.addPage();
        }
        premierePage = false;

        let currentY = marginTop;

        // Tableau d'en-tête
        const tableHeader = document.getElementById('monTableau0' + index);
        if (tableHeader) {
            doc.autoTable({
                html: tableHeader,
                theme: 'plain',
                startY: currentY,
                styles: styles,
                headStyles: headStyles,
                bodyStyles: bodyStyles,
                columnStyles: {
                    0: { cellWidth: 30 },
                    1: { cellWidth: 50 },
                    2: { cellWidth: 30 },
                    3: { cellWidth: 70 }
                },
                didParseCell: applyStylesByClass,
                didDrawCell: applyComplexStyleByClass,
                margin: { left: 10, right: 10, top: currentY, bottom: 0 }
            });
            currentY = doc.lastAutoTable.finalY + 1; // espace réduit entre les tableaux (1 mm)
        }

        // Tableau principal - forcé à rester sur la même page
        const tableBody = document.getElementById('monTableau1' + index);
        if (tableBody) {
            // Nombre réel de colonnes famille (total colonnes - 3 fixes : semaine, jour, date)
            let maxCols = 0;
            tableBody.querySelectorAll('tr').forEach(row => {
                let w = 0;
                row.querySelectorAll('td, th').forEach(cell => { w += (cell.colSpan || 1); });
                if (w > maxCols) maxCols = w;
            });
            const nFam    = Math.max(1, maxCols - 3);
            const usable  = doc.internal.pageSize.getWidth() - 20; // 210 - marges 10+10
            const fixed   = 10 + 10 + 18; // col0 + col1 + col2
            const famColW = Math.max(12, (usable - fixed) / nFam);

            const dynCols = { 0: { cellWidth: 10 }, 1: { cellWidth: 10 }, 2: { cellWidth: 18 } };
            for (let i = 0; i < nFam; i++) dynCols[3 + i] = { cellWidth: famColW };

            doc.autoTable({
                html: tableBody,
                theme: 'grid',
                startY: currentY,
                margin: { left: 10, right: 10, top: currentY, bottom: 0 },
                styles: styles,
                headStyles: headStyles,
                bodyStyles: bodyStyles,
                didParseCell: applyStylesByClass,
                didDrawCell: applyComplexStyleByClass,
                columnStyles: dynCols,
                pageBreak: 'avoid',
                didDrawPage: function (data) {
                    if (data.cursor.y > pageHeight - marginBottom) {
                        console.warn("Débordement détecté – le tableau ne tient pas sur une page.");
                    }
                }
            });
        }

        index++;
    }

    doc.save(`Feuille d'Heures ${type_de_garde} ${anne_file}.pdf`);
}

/**
 * Génère un PDF A4 one-page-per-fiche avec two-pass scaling.
 * Utilisée par generate.html.twig et releve_pdf.html.twig.
 * @param {string} filename  ex: 'Fiches_Vierges_GardeEnfants.pdf'
 */
function genererFichesPDF(filename) {
    if (!window.jspdf) { alert('jsPDF non chargée'); return; }
    const { jsPDF } = window.jspdf;

    const fiches = document.querySelectorAll('.fiche-page');
    if (!fiches.length) return;

    const doc   = new jsPDF('p', 'mm', 'a4');
    const pageH = doc.internal.pageSize.getHeight();
    const pageW = doc.internal.pageSize.getWidth();
    const marginX = 6;
    const famW = (pageW - 2 * marginX - 8 - 11 - 13) / 5;

    const baseStyles = {
        valign: 'middle', halign: 'center',
        textColor: [0, 0, 0], lineColor: [51, 51, 51], lineWidth: 0.2,
    };

    function renderFiche(targetDoc, hdrEl, bodyEl, sc) {
        let y = 5;
        if (hdrEl) {
            targetDoc.autoTable({
                html: hdrEl, startY: y,
                margin: { left: marginX, right: marginX, bottom: 0 },
                theme: 'plain',
                styles: { ...baseStyles, fontSize: 8 * sc, cellPadding: 1 * sc },
                didParseCell(data) {
                    applyStylesByClass(data);
                    if (data.cell.raw?.classList?.contains('title'))
                        data.cell.styles.fontSize = 15 * sc;
                },
                didDrawCell: applyComplexStyleByClass,
            });
            y = targetDoc.lastAutoTable.finalY + 2 * sc;
        }
        if (bodyEl) {
            targetDoc.autoTable({
                html: bodyEl, startY: y,
                margin: { left: marginX, right: marginX, bottom: 0 },
                theme: 'plain',
                styles: { ...baseStyles, fontSize: 7 * sc, cellPadding: 1.2 * sc },
                columnStyles: {
                    0: { cellWidth:  8 }, 1: { cellWidth: 11 }, 2: { cellWidth: 13 },
                    3: { cellWidth: famW }, 4: { cellWidth: famW }, 5: { cellWidth: famW },
                    6: { cellWidth: famW }, 7: { cellWidth: famW },
                },
                didParseCell(data) {
                    applyStylesByClass(data);
                    if (data.cell.raw?.classList?.contains('title'))
                        data.cell.styles.fontSize = 15 * sc;
                    const cell = data.cell.raw;
                    if (cell && cell.textContent.trim() === '' && cell.querySelector?.('br')) {
                        data.cell.styles.cellPadding   = 0.3 * sc;
                        data.cell.styles.minCellHeight = 1   * sc;
                    }
                    // Cellule famille (numéro + nom + ville) : vider le texte, redessiné manuellement
                    const villesEl = cell?.querySelector?.('i.villes');
                    if (villesEl) {
                        data.cell.text.splice(0, data.cell.text.length);
                        data.cell.styles.cellPadding   = 0;
                        data.cell.styles.minCellHeight = 11 * sc;
                    }
                },
                didDrawCell(data) {
                    const cell = data.cell.raw;
                    const villesEl = cell?.querySelector?.('i.villes');
                    if (!villesEl) return;

                    const cityText = villesEl.textContent.trim();
                    const spans    = cell.querySelectorAll('span');
                    const numText  = spans[0]?.textContent.trim() ?? '';
                    const nameText = spans[1]?.textContent.trim() ?? '';

                    const doc  = data.doc;
                    const cx   = data.cell.x + data.cell.width / 2;
                    const cy   = data.cell.y;
                    const ch   = data.cell.height;
                    const origSize = doc.getFontSize();
                    const origFont = doc.getFont();

                    // Numéro (en haut)
                    doc.setFontSize(7 * sc);
                    doc.setFont(undefined, 'normal');
                    doc.text(numText, cx, cy + 2.5 * sc, { align: 'center' });

                    doc.setFont(undefined, 'bold');

                    const mots = nameText.split(/[\s/\-]+/).filter(Boolean);

                    let nameLines;
                    if (mots.length > 2) {
                        nameLines = [
                            mots.slice(0, 2).join(' '),  // 2 premiers mots
                            mots.slice(2).join(' ')      // le reste (aucun mot perdu)
                        ];
                    } else {
                        nameLines = [nameText];
                    }

                    const nameSize = nameLines.length > 1 ? 6 : 7;

                    doc.setFontSize(nameSize * sc);

                    const lineH = (nameSize - 1) * 0.4 * sc;
                    const blockH = (nameLines.length - 1) * lineH;
                    const startY = cy + ch / 2 - blockH / 2;

                    nameLines.forEach((line, li) => {
                        doc.text(line, cx, startY + li * lineH, {
                            align: 'center'
                        });
                    });
                    // Ville (bas, italic petit)
                    doc.setFontSize(5 * sc);
                    doc.setFont(undefined, 'italic');
                    doc.text(cityText, cx, cy + ch - 2 * sc, { align: 'center' });

                    doc.setFontSize(origSize);
                    doc.setFont(origFont.fontName, origFont.fontStyle);
                },
            });
            y = targetDoc.lastAutoTable.finalY;
        }
        return y;
    }

    const total = fiches.length;
    fiches.forEach((fiche, i) => {
        if (i > 0) doc.addPage();
        const hdrEl  = fiche.querySelector('[id^="monTableau0"]');
        const bodyEl = fiche.querySelector('[id^="monTableau1"]');
        const probe  = new jsPDF('p', 'mm', 'a4');
        const usedY  = renderFiche(probe, hdrEl, bodyEl, 1.0);
        const sc     = (pageH - 4) / usedY;
        renderFiche(doc, hdrEl, bodyEl, sc);

        // Pagination en pied de page (uniquement s'il y a plusieurs pages)
        if (total > 1) {
            doc.setFontSize(8);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(0, 0, 0);
            doc.text(
                `Page ${i + 1} / ${total}`,
                pageW - marginX,
                8,
                { align: 'right' }
            );
            doc.setTextColor(0, 0, 0);
        }
    });

    doc.save(filename);
}