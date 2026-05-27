/**
 * Utilitaire de calcul d'heures pour l'application Chaudoudoux
 * Solution hybride : SQL pour les agrégations, JavaScript pour l'affichage
 */

class HoursCalculator {
    /**
     * Calcule la durée en heures entre deux heures
     * @param {string} heureDebut - Format "H:i:SS"
     * @param {string} heureFin - Format "H:i:SS"
     * @returns {number} - Durée en heures avec décimales
     */
    static calculerDuree(heureDebut, heureFin) {
        if (!heureDebut || !heureFin) return 0;
        
        // Parse les heures en minutes depuis minuit
        const [h1, m1, s1] = heureDebut.split(':').map(Number);
        const [h2, m2, s2] = heureFin.split(':').map(Number);
        
        const debutMinutes = h1 * 60 + m1 + (s1 || 0) / 60;
        const finMinutes = h2 * 60 + m2 + (s2 || 0) / 60;
        
        // Gère le cas où la fin est le lendemain
        let dureeMinutes = finMinutes - debutMinutes;
        if (dureeMinutes < 0) {
            dureeMinutes += 24 * 60; // Ajoute 24h
        }
        
        return dureeMinutes / 60; // Convertit en heures
    }
    
    /**
     * Formate les heures pour l'affichage
     * @param {number} heures - Nombre d'heures
     * @param {boolean} showDecimals - Afficher les décimales
     * @returns {string} - Heures formatées
     */
    static formaterHeures(heures, showDecimals = true) {
        if (heures === 0) return '0h';
        
        const heuresEntieres = Math.floor(heures);
        const minutes = Math.round((heures - heuresEntieres) * 60);
        
        if (showDecimals && minutes > 0) {
            return `${heuresEntieres}h${minutes.toString().padStart(2, '0')}`;
        }
        return `${heuresEntieres}h`;
    }
    
    /**
     * Calcule le total d'heures pour un tableau de prestations
     * @param {Array} prestations - Tableau d'objets avec heureDebutPresta et heureFinPresta
     * @returns {number} - Total des heures
     */
    static calculerTotal(prestations) {
        if (!Array.isArray(prestations)) return 0;
        
        return prestations.reduce((total, prestation) => {
            return total + this.calculerDuree(prestation.heureDebutPresta, prestation.heureFinPresta);
        }, 0);
    }
    
    /**
     * Calcule les heures par type de prestation
     * @param {Array} prestations - Tableau de prestations
     * @returns {Object} - Totals par type (ENFA, MENA, etc.)
     */
    static calculerParType(prestations) {
        if (!Array.isArray(prestations)) return {};
        
        return prestations.reduce((result, prestation) => {
            const type = prestation.typePresta || 'AUTRE';
            const duree = this.calculerDuree(prestation.heureDebutPresta, prestation.heureFinPresta);
            
            if (!result[type]) {
                result[type] = 0;
            }
            result[type] += duree;
            
            return result;
        }, {});
    }
    
    /**
     * Valide une heure au format H:i:sou H:i:SS
     * @param {string} heure - Heure à valider
     * @returns {boolean} - True si valide
     */
    static validerHeure(heure) {
        if (!heure) return false;
        
        const regex = /^([01]?[0-9]|2[0-3]):([0-5][0-9])(?::([0-5][0-9]))?$/;
        return regex.test(heure);
    }
    
    /**
     * Calcule la durée depuis maintenant (pour les prestations en cours)
     * @param {string} heureDebut - Heure de début
     * @returns {number} - Durée en heures depuis le début
     */
    static calculerDureeEnCours(heureDebut) {
        if (!heureDebut) return 0;
        
        const maintenant = new Date();
        const [h, m, s] = heureDebut.split(':').map(Number);
        
        const debut = new Date();
        debut.setHours(h, m, s || 0, 0);
        
        // Si l'heure de début est après maintenant, c'est probablement hier
        if (debut > maintenant) {
            debut.setDate(debut.getDate() - 1);
        }
        
        const diffMs = maintenant - debut;
        return diffMs / (1000 * 60 * 60); // Convertit en heures
    }
    
    /**
     * Génère un rapport d'heures détaillé
     * @param {Array} prestations - Liste des prestations
     * @returns {Object} - Rapport détaillé
     */
    static genererRapport(prestations) {
        if (!Array.isArray(prestations)) {
            return {
                total: 0,
                totalFormate: '0h',
                parType: {},
                nbPrestations: 0,
                moyenne: 0
            };
        }
        
        const total = this.calculerTotal(prestations);
        const parType = this.calculerParType(prestations);
        
        return {
            total: total,
            totalFormate: this.formaterHeures(total),
            parType: Object.fromEntries(
                Object.entries(parType).map(([type, heures]) => [type, this.formaterHeures(heures)])
            ),
            nbPrestations: prestations.length,
            moyenne: prestations.length > 0 ? total / prestations.length : 0
        };
    }
}

// Export pour utilisation globale
window.HoursCalculator = HoursCalculator;

// Fonctions raccourcies pour les templates
window.calculerHeures = (debut, fin) => HoursCalculator.calculerDuree(debut, fin);
window.formaterHeures = (heures) => HoursCalculator.formaterHeures(heures);
