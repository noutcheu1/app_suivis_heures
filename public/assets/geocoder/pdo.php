<?php
class PdoBdChaudoudouxExtra{   		
    private $monPdo;
    /**
     * Crée l'instance de PDO qui sera sollicitée
     * par toutes les méthodes de la classe
     */				
    public function __construct($serveur, $bdd, $user, $mdp){
        // crée la chaîne de connexion mentionnant le type de sgbdr, l'hôte et la base
        $chaineConnexion = 'mysql:host=' . $serveur . ';dbname=' . $bdd;
        // demande que le dialogue se fasee en utilisant l'encodage utf-8
        // et le mode de gestion des erreurs soit les exceptions
        $params = array (   PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8", 
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);

        // crée une instance de PDO (connexion avec le serveur MySql) 
        $this->monPdo = new PDO($chaineConnexion, $user, $mdp, $params); 
    }
    public function _destruct(){
        $this->monPdo = null;
    }

    public function obtenirListeFamilleAPourvoir(){
        $req = "SELECT distinct Famille.numero_Famille,Pere.nom_Parents 
        as nom1, Mere.nom_Parents as nom2 from Famille join Parents as Pere 
        on Famille.numero_Famille=Pere.numero_Famille join Parents as Mere 
        on Famille.numero_Famille=Mere.numero_Famille left join Proposer 
        on Famille.numero_Famille=Proposer.numero_Famille where archive_Famille=0 
        and aPourvoir_Famille=1 group by famille.numero_Famille order by nom1 asc, nom2 asc;  ";
       $cmd = $this->monPdo->prepare($req);
       $cmd->execute();
       $lignes = $cmd->fetchAll(PDO::FETCH_ASSOC);
       $cmd->closeCursor();
       return $lignes; 
    }

    public function obtenirDetailFamille($num){
        $req = "SELECT adresse_Famille,cp_Famille,ville_Famille from Famille where numero_Famille=:num ;";
        $cmd = $this->monPdo->prepare($req);
        $cmd->bindValue('num', $num);
        $cmd->execute();
        $res = $cmd->fetch(PDO::FETCH_ASSOC);
        $cmd->closeCursor();
        return $res;
    }
	public function obtenirListeSalarie(){
        $req = "SELECT * from candidats as C join intervenants as I on I.candidats_numcandidat_candidats=C.numCandidat_Candidats 
        where I.archive_Intervenants=0 AND I.numSalarie_Intervenants != 99999 and I.numSalarie_Intervenants
         not in (SELECT numSalarie_Intervenants from proposer where dateFin_Proposer>date(now()) or dateFin_Proposer='0000-00-00' 
         and numero_Famille<>'9999') GROUP BY C.numCandidat_Candidats ORDER BY C.nom_Candidats ASC" ;
        $cmd = $this->monPdo->prepare($req);
        $cmd->execute();
        $lignes = $cmd->fetchAll(PDO::FETCH_NUM);
        $cmd->closeCursor();
        return $lignes;        
    }
}