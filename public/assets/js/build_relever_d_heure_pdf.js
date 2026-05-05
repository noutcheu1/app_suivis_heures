function soulignementPDF(data, offset=0) {
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

    const y =
        cell.y +
        cell.height -
        cell.padding('bottom') -
        offset;

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
            data.cell.styles.fontSize = 15;
        }

        if (hasClass('title') || hasClass('bold') ) {
            data.cell.styles.fontStyle = 'bold';
        }

        // Ligne "dimanche" Historiquement grise
        //if (hasClass('dimanche')) {
        //    data.cell.styles.fillColor = [224, 224, 224];
        //    data.cell.styles.textColor = [51, 51, 51];
        //}
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
    
    let index = 1;
    let premierePage = true;

    const styles = {
        fontSize: 9,
        cellPadding: 0.70,
        valign: 'middle',
        halign: 'center',
        lineWidth: 1,
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
        fontStyle: [0,0,0],
        lineWidth: 0.3,
    };



    while (document.getElementById('monTableau1' + index)) {
        if (!premierePage) {
            doc.addPage();
        }
        premierePage = false;

        const tableHeader = document.getElementById('monTableau0' + index);

        if (tableHeader) {
            doc.autoTable({
                html: tableHeader,
                theme: 'plain',
                startY: 5,

                styles: styles,
                headStyles: headStyles,
                bodyStyles: bodyStyles,

                columnStyles: {
                    0: { cellWidth: 30},
                    1: { cellWidth: 50},
                    2: { cellWidth: 30},
                    3: { cellWidth: 70}
                },
                
                didParseCell: applyStylesByClass,
                didDrawCell: applyComplexStyleByClass
            });
        }

        
        const tableBody = document.getElementById('monTableau1' + index);

        if (tableBody) {
            doc.autoTable({
                html: tableBody,
                theme: 'plain',
                startY: doc.lastAutoTable.finalY + 5,

                margin: {
                    top: 5,
                    bottom: 0,   // 👈 supprime la marge basse
                    left: 10,
                    right: 10
                },
                
                styles: styles,
                headStyles: headStyles,
                bodyStyles: bodyStyles,

                didParseCell: applyStylesByClass,
                didDrawCell: applyComplexStyleByClass,

                columnStyles: {
                    0: { cellWidth: 10, minCellWidth: 5, maxCellWidth: 5},
                    1: { cellWidth: 5, minCellWidth: 5, maxCellWidth: 5},
                    2: { cellWidth: 20, minCellWidth: 10, maxCellWidth: 10},
                    3: { cellWidth: 30, minCellWidth: 25 },
                    4: { cellWidth: 30, minCellWidth: 25 },
                    5: { cellWidth: 30, minCellWidth: 25 },
                    6: { cellWidth: 30, minCellWidth: 25 },
                    7: { cellWidth: 30, minCellWidth: 25 },
                },
            });
        }

        index++;
    }
    // Sauvegarde
    //return
    doc.save(`Feuille d'Heures ${type_de_garde}  ${anne_file}.pdf`);
}

genererPDF(type_de_garde, anne_file); 