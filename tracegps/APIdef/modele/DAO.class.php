<?php
// Projet TraceGPS
// fichier : modele/DAO.class.php   (DAO : Data Access Object)
// Rôle : fournit des méthodes d'accès à la bdd tracegps (projet TraceGPS) au moyen de l'objet PDO
// modifié par dP le 12/8/2021

// liste des méthodes déjà développées (dans l'ordre d'apparition dans le fichier) :

// __construct() : le constructeur crée la connexion $cnx à la base de données
// __destruct() : le destructeur ferme la connexion $cnx à la base de données
// getNiveauConnexion($login, $mdp) : fournit le niveau (0, 1 ou 2) d'un utilisateur identifié par $login et $mdp
// existePseudoUtilisateur($pseudo) : fournit true si le pseudo $pseudo existe dans la table tracegps_utilisateurs, false sinon
// getUnUtilisateur($login) : fournit un objet Utilisateur à partir de $login (son pseudo ou son adresse mail)
// getTousLesUtilisateurs() : fournit la collection de tous les utilisateurs (de niveau 1)
// creerUnUtilisateur($unUtilisateur) : enregistre l'utilisateur $unUtilisateur dans la bdd
// modifierMdpUtilisateur($login, $nouveauMdp) : enregistre le nouveau mot de passe $nouveauMdp de l'utilisateur $login daprès l'avoir hashé en SHA1
// supprimerUnUtilisateur($login) : supprime l'utilisateur $login (son pseudo ou son adresse mail) dans la bdd, ainsi que ses traces et ses autorisations
// envoyerMdp($login, $nouveauMdp) : envoie un mail à l'utilisateur $login avec son nouveau mot de passe $nouveauMdp

// liste des méthodes restant à développer :

// existeAdrMailUtilisateur($adrmail) : fournit true si l'adresse mail $adrMail existe dans la table tracegps_utilisateurs, false sinon
// getLesUtilisateursAutorises($idUtilisateur) : fournit la collection  des utilisateurs (de niveau 1) autorisés à suivre l'utilisateur $idUtilisateur
// getLesUtilisateursAutorisant($idUtilisateur) : fournit la collection  des utilisateurs (de niveau 1) autorisant l'utilisateur $idUtilisateur à voir leurs parcours
// autoriseAConsulter($idAutorisant, $idAutorise) : vérifie que l'utilisateur $idAutorisant) autorise l'utilisateur $idAutorise à consulter ses traces
// creerUneAutorisation($idAutorisant, $idAutorise) : enregistre l'autorisation ($idAutorisant, $idAutorise) dans la bdd
// supprimerUneAutorisation($idAutorisant, $idAutorise) : supprime l'autorisation ($idAutorisant, $idAutorise) dans la bdd
// getLesPointsDeTrace($idTrace) : fournit la collection des points de la trace $idTrace
// getUneTrace($idTrace) : fournit un objet Trace à partir de identifiant $idTrace
// getToutesLesTraces() : fournit la collection de toutes les traces
// getMesTraces($idUtilisateur) : fournit la collection des traces de l'utilisateur $idUtilisateur
// getLesTracesAutorisees($idUtilisateur) : fournit la collection des traces que l'utilisateur $idUtilisateur a le droit de consulter
// creerUneTrace(Trace $uneTrace) : enregistre la trace $uneTrace dans la bdd
// terminerUneTrace($idTrace) : enregistre la fin de la trace d'identifiant $idTrace dans la bdd ainsi que la date de fin
// supprimerUneTrace($idTrace) : supprime la trace d'identifiant $idTrace dans la bdd, ainsi que tous ses points
// creerUnPointDeTrace(PointDeTrace $unPointDeTrace) : enregistre le point $unPointDeTrace dans la bdd


// certaines méthodes nécessitent les classes suivantes :
include_once ('Utilisateur.class.php');
include_once ('Trace.class.php');
include_once ('PointDeTrace.class.php');
include_once ('Point.class.php');
include_once ('Outils.class.php');

// inclusion des paramètres de l'application
include_once ('parametres.php');

// début de la classe DAO (Data Access Object)
class DAO
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Membres privés de la classe ---------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    private $cnx;				// la connexion à la base de données
    
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Constructeur et destructeur ---------------------------------------
    // ------------------------------------------------------------------------------------------------------
    public function __construct() {
        global $PARAM_HOTE, $PARAM_PORT, $PARAM_BDD, $PARAM_USER, $PARAM_PWD;
        try
        {	$this->cnx = new PDO ("mysql:host=" . $PARAM_HOTE . ";port=" . $PARAM_PORT . ";dbname=" . $PARAM_BDD,
            $PARAM_USER,
            $PARAM_PWD);
        return true;
        }
        catch (Exception $ex)
        {	echo ("Echec de la connexion a la base de donnees <br>");
        echo ("Erreur numero : " . $ex->getCode() . "<br />" . "Description : " . $ex->getMessage() . "<br>");
        echo ("PARAM_HOTE = " . $PARAM_HOTE);
        return false;
        }
    }
    
    public function __destruct() {
        // ferme la connexion à MySQL :
        unset($this->cnx);
    }
    
    // ------------------------------------------------------------------------------------------------------
    // -------------------------------------- Méthodes d'instances ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    // fournit le niveau (0, 1 ou 2) d'un utilisateur identifié par $pseudo et $mdpSha1
    // cette fonction renvoie un entier :
    //     0 : authentification incorrecte
    //     1 : authentification correcte d'un utilisateur (pratiquant ou personne autorisée)
    //     2 : authentification correcte d'un administrateur
    // modifié par Jim le 11/1/2018
    public function getNiveauConnexion($pseudo, $mdpSha1) {
        // préparation de la requête de recherche
        $txt_req = "Select niveau from tracegps_utilisateurs";
        $txt_req .= " where pseudo = :pseudo";
        $txt_req .= " and mdpSha1 = :mdpSha1";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        $req->bindValue("mdpSha1", $mdpSha1, PDO::PARAM_STR);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        // traitement de la réponse
        $reponse = 0;
        if ($uneLigne) {
        	$reponse = $uneLigne->niveau;
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la réponse
        return $reponse;
    }
    
    
    // fournit true si le pseudo $pseudo existe dans la table tracegps_utilisateurs, false sinon
    // modifié par Jim le 27/12/2017
    public function existePseudoUtilisateur($pseudo) {
        // préparation de la requête de recherche
        $txt_req = "Select count(*) from tracegps_utilisateurs where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // exécution de la requête
        $req->execute();
        $nbReponses = $req->fetchColumn(0);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // fourniture de la réponse
        if ($nbReponses == 0) {
            return false;
        }
        else {
            return true;
        }
    }
    
    
    // fournit un objet Utilisateur à partir de son pseudo $pseudo
    // fournit la valeur null si le pseudo n'existe pas
    // modifié par Jim le 9/1/2018
    public function getUnUtilisateur($pseudo) {
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs";
        $txt_req .= " where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        // traitement de la réponse
        if ( ! $uneLigne) {
            return null;
        }
        else {
            // création d'un objet Utilisateur
            $unId = utf8_encode($uneLigne->id);
            $unPseudo = utf8_encode($uneLigne->pseudo);
            $unMdpSha1 = utf8_encode($uneLigne->mdpSha1);
            $uneAdrMail = utf8_encode($uneLigne->adrMail);
            $unNumTel = utf8_encode($uneLigne->numTel);
            $unNiveau = utf8_encode($uneLigne->niveau);
            $uneDateCreation = utf8_encode($uneLigne->dateCreation);
            $unNbTraces = utf8_encode($uneLigne->nbTraces);
            $uneDateDerniereTrace = utf8_encode($uneLigne->dateDerniereTrace);
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            return $unUtilisateur;
        }
    }
    
    
    // fournit la collection  de tous les utilisateurs (de niveau 1)
    // le résultat est fourni sous forme d'une collection d'objets Utilisateur
    // modifié par Jim le 27/12/2017
    public function getTousLesUtilisateurs() {
        // préparation de la requête de recherche
        $txt_req = "Select id, pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation, nbTraces, dateDerniereTrace";
        $txt_req .= " from tracegps_vue_utilisateurs";
        $txt_req .= " where niveau = 1";
        $txt_req .= " order by pseudo";
        
        $req = $this->cnx->prepare($txt_req);
        // extraction des données
        $req->execute();
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        // construction d'une collection d'objets Utilisateur
        $lesUtilisateurs = array();
        // tant qu'une ligne est trouvée :
        while ($uneLigne) {
            // création d'un objet Utilisateur
            $unId = utf8_encode($uneLigne->id);
            $unPseudo = utf8_encode($uneLigne->pseudo);
            $unMdpSha1 = utf8_encode($uneLigne->mdpSha1);
            $uneAdrMail = utf8_encode($uneLigne->adrMail);
            $unNumTel = utf8_encode($uneLigne->numTel);
            $unNiveau = utf8_encode($uneLigne->niveau);
            $uneDateCreation = utf8_encode($uneLigne->dateCreation);
            $unNbTraces = utf8_encode($uneLigne->nbTraces);
            $uneDateDerniereTrace = utf8_encode($uneLigne->dateDerniereTrace);
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
            // extrait la ligne suivante
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la collection
        return $lesUtilisateurs;
    }

    
    // enregistre l'utilisateur $unUtilisateur dans la bdd
    // fournit true si l'enregistrement s'est bien effectué, false sinon
    // met à jour l'objet $unUtilisateur avec l'id (auto_increment) attribué par le SGBD
    // modifié par Jim le 9/1/2018
    public function creerUnUtilisateur($unUtilisateur) {
        // on teste si l'utilisateur existe déjà
        if ($this->existePseudoUtilisateur($unUtilisateur->getPseudo())) return false;
        
        // préparation de la requête
        $txt_req1 = "insert into tracegps_utilisateurs (pseudo, mdpSha1, adrMail, numTel, niveau, dateCreation)";
        $txt_req1 .= " values (:pseudo, :mdpSha1, :adrMail, :numTel, :niveau, :dateCreation)";
        $req1 = $this->cnx->prepare($txt_req1);
        // liaison de la requête et de ses paramètres
        $req1->bindValue("pseudo", utf8_decode($unUtilisateur->getPseudo()), PDO::PARAM_STR);
        $req1->bindValue("mdpSha1", utf8_decode(sha1($unUtilisateur->getMdpsha1())), PDO::PARAM_STR);
        $req1->bindValue("adrMail", utf8_decode($unUtilisateur->getAdrmail()), PDO::PARAM_STR);
        $req1->bindValue("numTel", utf8_decode($unUtilisateur->getNumTel()), PDO::PARAM_STR);
        $req1->bindValue("niveau", utf8_decode($unUtilisateur->getNiveau()), PDO::PARAM_INT);
        $req1->bindValue("dateCreation", utf8_decode($unUtilisateur->getDateCreation()), PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req1->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        // recherche de l'identifiant (auto_increment) qui a été attribué à la trace
        $unId = $this->cnx->lastInsertId();
        $unUtilisateur->setId($unId);
        return true;
    }
    
    
    // enregistre le nouveau mot de passe $nouveauMdp de l'utilisateur $pseudo daprès l'avoir hashé en SHA1
    // fournit true si la modification s'est bien effectuée, false sinon
    // modifié par Jim le 9/1/2018
    public function modifierMdpUtilisateur($pseudo, $nouveauMdp) {
        // préparation de la requête
        $txt_req = "update tracegps_utilisateurs set mdpSha1 = :nouveauMdp";
        $txt_req .= " where pseudo = :pseudo";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("nouveauMdp", sha1($nouveauMdp), PDO::PARAM_STR);
        $req->bindValue("pseudo", $pseudo, PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req->execute();
        return $ok;
    }
    
    
    // supprime l'utilisateur $pseudo dans la bdd, ainsi que ses traces et ses autorisations
    // fournit true si l'effacement s'est bien effectué, false sinon
    // modifié par Jim le 9/1/2018
    public function supprimerUnUtilisateur($pseudo) {
        $unUtilisateur = $this->getUnUtilisateur($pseudo);
        if ($unUtilisateur == null) {
            return false;
        }
        else {
            $idUtilisateur = $unUtilisateur->getId();
            
            // suppression des traces de l'utilisateur (et des points correspondants)
            $lesTraces = $this->getLesTraces($idUtilisateur);
			if($lesTraces != null)
			{
				foreach ($lesTraces as $uneTrace)
				{
                $this->supprimerUneTrace($uneTrace->getId());
				}
			}
            
            // préparation de la requête de suppression des autorisations
            $txt_req1 = "delete from tracegps_autorisations" ;
            $txt_req1 .= " where idAutorisant = :idUtilisateur or idAutorise = :idUtilisateur";
            $req1 = $this->cnx->prepare($txt_req1);
            // liaison de la requête et de ses paramètres
            $req1->bindValue("idUtilisateur", utf8_decode($idUtilisateur), PDO::PARAM_INT);
            // exécution de la requête
            $ok = $req1->execute();
            
            // préparation de la requête de suppression de l'utilisateur
            $txt_req2 = "delete from tracegps_utilisateurs" ;
            $txt_req2 .= " where pseudo = :pseudo";
            $req2 = $this->cnx->prepare($txt_req2);
            // liaison de la requête et de ses paramètres
            $req2->bindValue("pseudo", utf8_decode($pseudo), PDO::PARAM_STR);
            // exécution de la requête
            $ok = $req2->execute();
            return $ok;
        }
    }
    
    
    // envoie un mail à l'utilisateur $pseudo avec son nouveau mot de passe $nouveauMdp
    // retourne true si envoi correct, false en cas de problème d'envoi
    // modifié par Jim le 9/1/2018
    public function envoyerMdp($pseudo, $nouveauMdp) {
        global $ADR_MAIL_EMETTEUR;
        // si le pseudo n'est pas dans la table tracegps_utilisateurs :
        if ( $this->existePseudoUtilisateur($pseudo) == false ) return false;
        
        // recherche de l'adresse mail
        $adrMail = $this->getUnUtilisateur($pseudo)->getAdrMail();
        
        // envoie un mail à l'utilisateur avec son nouveau mot de passe
        $sujet = "Modification de votre mot de passe d'accès au service TraceGPS";
        $message = "Cher(chère) " . $pseudo . "\n\n";
        $message .= "Votre mot de passe d'accès au service service TraceGPS a été modifié.\n\n";
        $message .= "Votre nouveau mot de passe est : " . $nouveauMdp ;
        $ok = Outils::envoyerMail ($adrMail, $sujet, $message, $ADR_MAIL_EMETTEUR);
        return $ok;
    }
    
    
    // Le code restant à développer va être réparti entre les membres de l'équipe de développement.
    // Afin de limiter les conflits avec GitHub, il est décidé d'attribuer une zone de ce fichier à chaque développeur.
    // Développeur 1 : lignes 350 à 549
    // Développeur 2 : lignes 550 à 749
    // Développeur 3 : lignes 750 à 949
    // Développeur 4 : lignes 950 à 1150
    
    // Quelques conseils pour le travail collaboratif :
    // avant d'attaquer un cycle de développement (début de séance, nouvelle méthode, ...), faites un Pull pour récupérer 
    // la dernière version du fichier.
    // Après avoir testé et validé une méthode, faites un commit et un push pour transmettre cette version aux autres développeurs.
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 1 (Brault Natan) : lignes 350 à 549
    // --------------------------------------------------------------------------------------
    

    public function existeAdrMailUtilisateur($adrMail) {
        
        // préparation de la requête de recherche de l'adresse mail
        $txt_req = "SELECT adrMail FROM tracegps_vue_utilisateurs" ;
        $txt_req .= " WHERE adrMail = :adr";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("adr", utf8_encode($adrMail), PDO::PARAM_STR);
        // exécution de la requête
        $req->execute();
        //$req->setFetchMode(PDO::FETCH_OBJ);
        
        $ligne = $req->fetch();
        // libère les ressources du jeu de données
        $req->closeCursor();
        // fourniture de la réponse
        if ($ligne == null || $ligne == "") {
            return false;
        }
        else 
        {
            return true;
        }
       
            
        
    }
    
    
    
    
    
    public function getLesUtilisateursAutorisant($idUtilisateur)
    {
        // préparation de la requête de recherche de l'adresse mail
        $txt_req = "SELECT id,pseudo,mdpSha1,adrMail,numTel,niveau,dateCreation,nbTraces,dateDerniereTrace FROM tracegps_autorisations join tracegps_vue_utilisateurs on tracegps_vue_utilisateurs.id = tracegps_autorisations.idAutorisant" ;
        $txt_req .= " WHERE idAutorise = :idUtilisateur AND niveau = 1";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("idUtilisateur", utf8_encode($idUtilisateur), PDO::PARAM_INT);
        $req->execute();
        //$req->setFetchMode(PDO::FETCH_OBJ);
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        $lesUtilisateurs = array();
        // tant qu'une ligne est trouvée :
        
        //echo("fetch : ".$uneLigne->id);
        
        while ($uneLigne) {
            // création d'un objet Utilisateur
            $unId = utf8_encode($uneLigne->id);
            $unPseudo = utf8_encode($uneLigne->pseudo);
            $unMdpSha1 = utf8_encode($uneLigne->mdpSha1);
            $uneAdrMail = utf8_encode($uneLigne->adrMail);
            $unNumTel = utf8_encode($uneLigne->numTel);
            $unNiveau = utf8_encode($uneLigne->niveau);
            $uneDateCreation = utf8_encode($uneLigne->dateCreation);
            $unNbTraces = utf8_encode($uneLigne->nbTraces);
            $uneDateDerniereTrace = utf8_encode($uneLigne->dateDerniereTrace);
            
            
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
            // extrait la ligne suivante
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        return $lesUtilisateurs;
    }
    
    
    public function getLesUtilisateursAutorises($idUtilisateur)
    {
        // préparation de la requête de recherche de l'adresse mail
        $txt_req = "SELECT id,pseudo,mdpSha1,adrMail,numTel,niveau,dateCreation,nbTraces,dateDerniereTrace FROM tracegps_autorisations join tracegps_vue_utilisateurs on tracegps_vue_utilisateurs.id = tracegps_autorisations.idAutorise" ;
        $txt_req .= " WHERE idAutorisant = :idUtilisateur AND niveau = 1";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("idUtilisateur", utf8_encode($idUtilisateur), PDO::PARAM_INT);
        $req->execute();
        //$req->setFetchMode(PDO::FETCH_OBJ);
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        $lesUtilisateurs = array();
        // tant qu'une ligne est trouvée :
        
        //echo("fetch : ".$uneLigne->id);
        
        while ($uneLigne) {
            // création d'un objet Utilisateur
            $unId = utf8_encode($uneLigne->id);
            $unPseudo = utf8_encode($uneLigne->pseudo);
            $unMdpSha1 = utf8_encode($uneLigne->mdpSha1);
            $uneAdrMail = utf8_encode($uneLigne->adrMail);
            $unNumTel = utf8_encode($uneLigne->numTel);
            $unNiveau = utf8_encode($uneLigne->niveau);
            $uneDateCreation = utf8_encode($uneLigne->dateCreation);
            $unNbTraces = utf8_encode($uneLigne->nbTraces);
            $uneDateDerniereTrace = utf8_encode($uneLigne->dateDerniereTrace);
            
            
            
            $unUtilisateur = new Utilisateur($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau, $uneDateCreation, $unNbTraces, $uneDateDerniereTrace);
            // ajout de l'utilisateur à la collection
            $lesUtilisateurs[] = $unUtilisateur;
            // extrait la ligne suivante
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        return $lesUtilisateurs;
    }
    
   
    public function autoriseAConsulter($idAutorisant, $idAutorise)
    {
        // préparation de la requête de recherche des autorisations
        $txt_req = "SELECT id FROM `tracegps_autorisations` join tracegps_vue_utilisateurs on tracegps_vue_utilisateurs.id = tracegps_autorisations.idAutorisant" ;
        $txt_req .= " WHERE idAutorise = :idAutorise and idAutorisant = :idAutorisant and niveau = 1";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("idAutorise", utf8_encode($idAutorise), PDO::PARAM_STR);
        $req->bindValue("idAutorisant", utf8_encode($idAutorisant), PDO::PARAM_STR);
        // exécution de la requête
        $req->execute();
        //$req->setFetchMode(PDO::FETCH_OBJ);
        
        
        $ligne = $req->fetch();
        // libère les ressources du jeu de données

        $req->closeCursor();
        // fourniture de la réponse
        if ($ligne == null || $ligne == "") {
            return false;
        }
        else
        {
            return true;
        }
        
        
    }
    
    //testeeeeeeeeee
    
    
    
    public function creerUneAutorisation($idAutorisant, $idAutorise)
    {
        if ($this->autoriseAConsulter($idAutorisant, $idAutorise)) return false;
        
        // préparation de la requête
        $txt_req = "INSERT INTO `tracegps_autorisations` (idAutorisant, idAutorise)";
        $txt_req .= " values (:idAutorisant, :idAutorise)";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("idAutorise", utf8_encode($idAutorise), PDO::PARAM_STR);
        $req->bindValue("idAutorisant", utf8_encode($idAutorisant), PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }

        return true;
        
    }
    
    
    public function supprimerUneAutorisation($idAutorisant, $idAutorise)
    {
        //if (!$this->autoriseAConsulter($idAutorisant, $idAutorise)) return false;
        
        // préparation de la requête
        $txt_req = "DELETE FROM `tracegps_autorisations`";
        $txt_req .= " WHERE idAutorisant = :idAutorisant AND idAutorise = :idAutorise";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
		$req->bindValue("idAutorise", utf8_encode($idAutorise), PDO::PARAM_INT);
        $req->bindValue("idAutorisant", utf8_encode($idAutorisant), PDO::PARAM_INT);
        // exécution de la requête
        $ok = $req->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        return true;
    }
    
    
    public function getLesPointsDeTrace($idTrace)
    {
        
        // préparation de la requête de recherche de l'adresse mail
        $txt_req = "SELECT * FROM tracegps_points" ;
        $txt_req .= " WHERE idTrace = :idtrace";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("idtrace", utf8_encode($idTrace), PDO::PARAM_INT);
        $req->execute();
        //$req->setFetchMode(PDO::FETCH_OBJ);
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        $lesPoints = array();
        // tant qu'une ligne est trouvée :
        
        while ($uneLigne) {
            
            $unIdTrace = utf8_encode($uneLigne->idTrace);
            $unID = utf8_encode($uneLigne->id);
            $uneLatitude = utf8_encode($uneLigne->latitude);
            $uneLongitude = utf8_encode($uneLigne->longitude);
            $uneAltitude = utf8_encode($uneLigne->altitude);
            $uneDateHeure = utf8_encode($uneLigne->dateHeure);
            $unRythmeCardio = utf8_encode($uneLigne->rythmeCardio);

            $unPointDeTrace = new PointDeTrace($unIdTrace, $unID, $uneLatitude, $uneLongitude, $uneAltitude, $uneDateHeure, $unRythmeCardio,0,0,0);
          
            
            $lesPoints[] = $unPointDeTrace;
            
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
            
        }
        // libère les ressources du jeu de données
        $req->closeCursor();
        
        return $lesPoints;
        
    }
    
    
    
    public function creerUnPointDeTrace($unPointDeTrace)
    
    {
        
        // préparation de la requête
        $txt_req = "INSERT INTO `tracegps_points` (idTrace,id,latitude,longitude,altitude,dateHeure,rythmeCardio)";
        $txt_req .= " values (:idTrace,:id,:latitude,:longitude,:altitude,:dateHeure,:rythmeCardio)";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("idTrace", utf8_encode($unPointDeTrace->getIdTrace()), PDO::PARAM_INT);
		$req->bindValue("id", utf8_encode(($this->getUneTrace($unPointDeTrace->getIdTrace())->getNombrePoints())+1), PDO::PARAM_INT);
        $req->bindValue("latitude", utf8_encode($unPointDeTrace->getLatitude()), PDO::PARAM_STR);
        $req->bindValue("longitude", utf8_encode($unPointDeTrace->getLongitude()), PDO::PARAM_STR);
        $req->bindValue("altitude", utf8_encode($unPointDeTrace->getAltitude()), PDO::PARAM_STR);
        // si c'est la première de la trace on la met en temps que date de début
        if($unPointDeTrace->getId() == 1)
        {
            $txt_req2 = "UPDATE `tracegps_vue_traces`";
            $txt_req2 .= " SET dateDebut = :dateDebut WHERE id = :idTrace";
            $req2 = $this->cnx->prepare($txt_req2);
            
            $req2->bindValue("idTrace", utf8_encode($unPointDeTrace->getIdTrace()), PDO::PARAM_INT);
            $req2->bindValue("dateDebut", utf8_encode($unPointDeTrace->getDateHeure()), PDO::PARAM_STR);
        }

        $req->bindValue("dateHeure", utf8_encode($unPointDeTrace->getDateHeure()), PDO::PARAM_STR);
        $req->bindValue("rythmeCardio", utf8_encode($unPointDeTrace->getRythmeCardio()), PDO::PARAM_STR);
        // exécution de la requête
        $ok = $req->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        return true;
        
        
        
    }
    
    
    public function getUneTrace($idTrace)
    {
        // préparation de la requête de recherche des autorisations
        $txt_req = "SELECT * FROM `tracegps_vue_traces`";
        $txt_req .= " WHERE id = :idTrace";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue(":idTrace", utf8_encode($idTrace), PDO::PARAM_STR);

        // exécution de la requête
        $req->execute();
        
        
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        if ( !$uneLigne) { return false; }

        $unID = utf8_encode($uneLigne->id);
        $uneDateHeureDebut = utf8_encode($uneLigne->dateDebut);
        $uneDateHeureFin = utf8_encode($uneLigne->dateFin);
        $terminee = utf8_encode($uneLigne->terminee);
        $unIdUtilisateur = utf8_encode($uneLigne->idUtilisateur);
        
        $trace = new Trace($unID, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur);
        

        foreach ($this->getLesPointsDeTrace($unID) as $pdt)
        {
            $trace->ajouterPoint($pdt);
        }
        
        return $trace;
        
        
    }
    
    
    
    public function getToutesLesTraces()
    {
        // préparation de la requête de recherche des autorisations
        $txt_req = "SELECT * FROM `tracegps_vue_traces`";
        $req = $this->cnx->prepare($txt_req);
       
        // exécution de la requête
        $req->execute();
        
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        if ( !$uneLigne) { return false; }
        
        $lesTraces = array();
        
        while($uneLigne){
        
            $unID = utf8_encode($uneLigne->id);
            $uneDateHeureDebut = utf8_encode($uneLigne->dateDebut);
            $uneDateHeureFin = utf8_encode($uneLigne->dateFin);
            $terminee = utf8_encode($uneLigne->terminee);
            $unIdUtilisateur = utf8_encode($uneLigne->idUtilisateur);
            
            $trace = new Trace($unID, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur);
            
            $trace->setLesPointsDeTrace($this->getLesPointsDeTrace($unID));
            
            
            foreach ($this->getLesPointsDeTrace($unID) as $pdt)
            {
                $trace->ajouterPoint($pdt);
            }
            
            $lesTraces[] = $trace;
            
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        }
        
        
        return $lesTraces;
    }
    
    
    
    public function getLesTraces($idUtilisateur)
    {
        // préparation de la requête de recherche des autorisations
        $txt_req = "SELECT * FROM `tracegps_vue_traces`";
        $txt_req .= " WHERE `idUtilisateur` = :idUtilisateur";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue(":idUtilisateur", utf8_encode($idUtilisateur), PDO::PARAM_INT);
        
        // exécution de la requête
        $req->execute();
        
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        if ( !$uneLigne) { return false; }
        
        $lesTraces = array();
        
        
        while ($uneLigne) 
        {
            $trace = $this->getUneTrace(utf8_encode($uneLigne->id));
            
            $lesTraces[] = $trace;
            
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        
        return $lesTraces;

    }
    
    
    
    public function getLesTracesAutorisees($idUtilisateur)
    {
        
        // préparation de la requête de recherche des autorisations
        $txt_req = "SELECT * from tracegps_vue_traces";
        $txt_req .= " WHERE idUtilisateur IN (SELECT idAutorisant from tracegps_autorisations where idAutorise = :idUtilisateur)";
        $txt_req .= " OR idUtilisateur = :idUtilisateur";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue("idUtilisateur", utf8_encode($idUtilisateur), PDO::PARAM_INT);
        
        // exécution de la requête
        $req->execute();
        
        $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        
        if ( !$uneLigne) { return false; }
        
        $lesTraces = array();
        
        
        while ($uneLigne)
        {
            $trace = $this->getUneTrace(utf8_encode($uneLigne->id));
            
            $lesTraces[] = $trace;
            
            $uneLigne = $req->fetch(PDO::FETCH_OBJ);
        }
        
        return $lesTraces;
        
        
    }
    
    
    
    
    public function creerUneTrace($uneTrace)
    {
        $txt_req = "INSERT INTO `tracegps_traces` (`dateDebut`, `dateFin`, `terminee`, `idUtilisateur`)";
        $txt_req .= " values (:dateDebut,:dateFin,:terminee,:idUtilisateur)";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        //$req->bindValue("id", utf8_encode($uneTrace->getId()), PDO::PARAM_INT);
        $req->bindValue(":dateDebut", utf8_encode($uneTrace->getDateHeureDebut()), PDO::PARAM_STR);
        
        if($uneTrace->getDateHeureFin() == NULL || $uneTrace->getDateHeureFin() == "")
        {
            $req->bindValue(":dateFin",PDO::PARAM_NULL);
        }
        else 
        {
            $req->bindValue(":dateFin", utf8_encode($uneTrace->getDateHeureFin()), PDO::PARAM_STR);
        }
        
        
        $req->bindValue(":terminee", utf8_encode($uneTrace->getTerminee()), PDO::PARAM_BOOL);
        $req->bindValue(":idUtilisateur", utf8_encode($uneTrace->getIdUtilisateur()), PDO::PARAM_INT);

        // exécution de la requête
        $ok = $req->execute();

        if ( !$ok) { return false; }
        
        return true;
        
    }
    
    
    public function supprimerUneTrace($idTrace)
    {
        
        // préparation de la requête
        $txt_req = "DELETE FROM tracegps_points";
        $txt_req .= " WHERE idTrace= :idTrace;";
        $txt_req .= "DELETE FROM tracegps_traces";
        $txt_req .= " WHERE id= :idTrace;";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue(":idTrace", utf8_encode($idTrace), PDO::PARAM_INT);
        
        //echo json_encode($req);
        
        // exécution de la requête
        $ok = $req->execute();
        // sortir en cas d'échec
        if ( ! $ok) { return false; }
        
        return true;
        
        
    }
    
    
    public function terminerUneTrace($idTrace)
    {
        
        $trace = $this->getUneTrace($idTrace);
        
        if($trace->getTerminee()) { return false;}
        
        
        $pdts = $this->getLesPointsDeTrace($idTrace); 
         
        // préparation de la requête
        $txt_req = "update tracegps_traces set terminee = :terminee, dateFin = :dateFin";
        $txt_req .= " where id = :idTrace";
        $req = $this->cnx->prepare($txt_req);
        // liaison de la requête et de ses paramètres
        $req->bindValue(":terminee", TRUE, PDO::PARAM_BOOL);
        if(sizeof($pdts) == 0)
        {
            $req->bindValue(":dateFin", date('Y-m-d H:i:s', time()), PDO::PARAM_STR);
        }
        else
        {
            $req->bindValue(":dateFin", $pdts[sizeof($pdts)-1]->getDateHeure(), PDO::PARAM_STR);
        }
        
        $req->bindValue(":idTrace", $idTrace,PDO::PARAM_INT);
        // exécution de la requête
        $ok = $req->execute();
        
        
        return $ok;
        
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 2 (xxxxxxxxxxxxxxxxxxxx) : lignes 550 à 749
    // --------------------------------------------------------------------------------------
    

    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 3 (xxxxxxxxxxxxxxxxxxxx) : lignes 750 à 949
    // --------------------------------------------------------------------------------------
    
    
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
        
    
    
    
    
    
    
    
    
    
    
    
    
   
    // --------------------------------------------------------------------------------------
    // début de la zone attribuée au développeur 4 (xxxxxxxxxxxxxxxxxxxx) : lignes 950 à 1150
    // --------------------------------------------------------------------------------------
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    



    
} // fin de la classe DAO

// ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
// d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!