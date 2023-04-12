<?php
// Projet TraceGPS - services web
// fichier :  api/services/RetirerUneAutorisation.php
// Dernière mise à jour : 06/12/2022 par Erwan

// Rôle : ce service web permet à un utilisateur de supprimer une autorisation qu'il avait accordée à un autre utilisateur. 
// Le service web doit recevoir 5 paramètres :
//pseudo : le pseudo de l'utilisateur qui retire l'autorisation
//mdp : le mot de passe hashé en sha1 de l'utilisateur qui retire l'autorisation
//pseudoARetirer : le pseudo de l'utilisateur à qui on veut retirer l'autorisation
//texteMessage : le texte d'un message accompagnant la suppression
//lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution
// Les paramètres doivent être passés par la méthode GET :
//     http://<hébergeur>/tracegps/api/RetirerUneAutorisation 

// ces variables globales sont définies dans le fichier modele/parametres.php (nécéssaire pour le mail) 
global $ADR_MAIL_EMETTEUR, $ADR_SERVICE_WEB;

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoARetirer = ( empty($this->request['pseudoARetirer'])) ? "" : $this->request['pseudoARetirer'];
$texteMessage = ( empty($this->request['texteMessage'])) ? "" : $this->request['texteMessage'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";


// Les paramètres doivent être présents sauf le $texteMessage qui peut être vide 
if ($pseudo == "" || $mdp == "" || $pseudoARetirer == "")
{
    $msg = "Erreur : données incomplètes";
    $code_reponse = 400;
}
else
{
    // Si authentification incorrecte ex : mot de passe incorrect, on en informe l'utilisateur
    $niveauConnexion = $dao->getNiveauConnexion($pseudo, $mdp);
    if ($niveauConnexion == 0) {
        $msg = "Erreur : authentification incorrecte.";
        $code_reponse = 401;
    }
    else
    {
    // Vérification de l'existence du pseudo de l'utilisateur à qui on désire supprimer l'autorisation
    $ok = $dao->existePseudoUtilisateur($pseudoARetirer);
    if (! $ok)
    {
        $msg = "Erreur : pseudo utilisateur inexistant.";
        $code_reponse = 400;
    }
    else
    {
        // Vérification que l'autorisation à supprimer existe bien 
        //Pour ce faire on récupère l'id de l'utilisateur actuellement connecté et l'id de l'utilisateur auquel on souhaite supprimer l'autorisation 
        $utilisateurConnecte = $dao->getUnUtilisateur($pseudo); // D'abord on va chercher l'utilisateur correspondand au pseudo 
        $idUtilisateurConnecte = $utilisateurConnecte->getId(); 
        $utilisateurSuprAutorisation = $dao->getUnUtilisateur($pseudoARetirer); 
        $idUtilisateurSuprAutorisation = $utilisateurSuprAutorisation->getId(); 
        
        //Maintenant on peut procéder à la vérification ! 
        $ok = $dao->autoriseAConsulter($idUtilisateurConnecte, $idUtilisateurSuprAutorisation); 
        if ($ok == false)
        {
                $msg = "Erreur : l'autorisation n'était pas accordée.";
                $code_reponse = 400;
        }
        //Une fois que tout est bon, on peut supprimer l'autorisation 
        else 
        {
            // s'il n'y a pas de message de saisi, on supprime l'autorisation sans envoyer de mail 
            if ($texteMessage == "")
            {
                $ok2 = $dao->supprimerUneAutorisation($idUtilisateurConnecte, $idUtilisateurSuprAutorisation);
                if ( ! $ok2 )
                {
                    $msg ="Erreur : problème lors de la suppression de l'autorisation."; 
                    $code_reponse = 500; 
                }
                else
                {
                    $msg ="Autorisation supprimée.";
                    $code_reponse = 200; 
                }
    
            }
            else 
            {
                // Si il y a un message de saisi on envoie un email
                $ok3 = $dao->supprimerUneAutorisation($idUtilisateurConnecte, $idUtilisateurSuprAutorisation);
                if (  ! $ok3 )
                {
                   $msg ="Erreur : problème lors de la suppression de l'autorisation.";
                   $code_reponse = 500;
                }
                else 
                {
                       //pour commencer on récupère l'adresse mail de l'utilisateur auquel on supprime l'autorisation 
                       $adrMailUtilisateurSuprAutorisation = $utilisateurSuprAutorisation->getAdrMail();
                       //Ensuite on peut construire et envoyer le mail. 
                       $sujetMail = "Suppression d'autorisation de la part d'un utilisateur du système TraceGPS";
                       $contenuMail = "Cher ou chère " . $pseudoARetirer . "\n\n";
                       $contenuMail .= "L'utilisateur ".$pseudo." du système TraceGPS vous retire l'autorisation de suivre ses parcours \n\n";
                       $contenuMail .= "Son Message : ".$texteMessage."\n\n";
                       $contenuMail .= "Cordialement. \n"; 
                       $contenuMail .= "L'administrateur du système TraceGPS";
                       $ok = Outils::envoyerMail($adrMailUtilisateurSuprAutorisation, $sujetMail, $contenuMail, $ADR_MAIL_EMETTEUR);
                       if ( ! $ok ) {
                           $msg = "Erreur : l'envoi du courriel au demandeur a rencontré un problème.";
                           $code_reponse = 500;
                       }
                       else {
                           $msg = "Autorisation supprimée ; ".$pseudoARetirer." va recevoir un courriel de notification.";
                           $code_reponse = 200;
                       }
                   }
               }
            }
        }
    }
}
    unset($dao);   // ferme la connexion à MySQL
    
    //création du flux en sortie
    if($lang=="xml") {
        $content_type = "application/xml; charset=utf-8"; //indique le format XML pour la réponse
        $donnees = creerFluxXML($msg);
    }
    else {
        $content_type = "application/json; charset=utf-8"; //indique le format JSon pour la réponse
        $donnees = creerFluxJSON($msg);
        
    }
    
    //envoi de la réponse HTTP
    $this->envoyerReponse($code_reponse, $content_type, $donnees);
    
    //fin du programme pour ne pas enchainer sur les 2 fonctions qui suivent
    exit;
    
    // =======================================================================================================================
    // création du flux XML en sortie
    function creerFluxXML($msg)
    {	// crée une instance de DOMdocument (DOM : Document Object Model)
        $doc = new DOMDocument();
        
        // specifie la version et le type d'encodage
        $doc->version = '1.0';
        $doc->encoding = 'UTF-8';
        
        // crée un commentaire et l'encode en UTF-8
        $elt_commentaire = $doc->createComment('Service web RetirerUneAutorisation - BTS SIO - Lycée De La Salle - Rennes');
        // place ce commentaire à la racine du document XML
        $doc->appendChild($elt_commentaire);
        
        // crée l'élément 'data' à la racine du document XML
        $elt_data = $doc->createElement('data');
        $doc->appendChild($elt_data);
        
        // place l'élément 'reponse' dans l'élément 'data'
        $elt_reponse = $doc->createElement('reponse', $msg);
        $elt_data->appendChild($elt_reponse);
        
        // Mise en forme finale
        $doc->formatOutput = true;
        
        // renvoie le contenu XML
        return $doc->saveXML();
    }
    // ================================================================================================
    // création du flux JSON en sortie
    function creerFluxJSON($msg)
    {
        /* Exemple de code JSON
         {
         "data":{
         "reponse": "authentification incorrecte."
         }
         }
         */
        
        // 2 notations possibles pour créer des tableaux associatifs (la deuxième est en commentaire)
        
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg];
        //     $elt_data = array("reponse" => $msg);
        
        // construction de la racine
        $elt_racine = ["data" => $elt_data];
        //     $elt_racine = array("data" => $elt_data);
        
        // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
        return json_encode($elt_racine, JSON_PRETTY_PRINT);
    }
    
    // ================================================================================================
?>