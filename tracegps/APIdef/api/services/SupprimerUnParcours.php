<?php
// Projet TraceGPS - services web
// fichier :  api/services/SupprimerUnParcours.php
// Dernière mise à jour : 09/12/2022 par Erwan
// Rôle : ce service permet à un utilisateur de supprimer un de ses parcours (ou traces).
// Le service web doit recevoir 4 paramètres :
//pseudo : le pseudo de l'utilisateur qui demande à supprimer
//mdp : le mot de passe hashé en sha1 de l'utilisateur qui demande à supprimer
//idTrace : l'id de la trace à supprimer
//lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution
// Les paramètres doivent être passés par la méthode GET :
//     http://<hébergeur>/tracegps/api/SupprimerUnParcours

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace = ( empty($this->request['idTrace'])) ? "" : $this->request['idTrace'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// les paramètres doivent être présents 
if ($pseudo == "" || $mdp == "" || $idTrace == "")
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
    else {
        //Vérification que la trace à supprimmer existe bien 
        $existe = $dao->getUneTrace($idTrace); 
        if (! $existe)
        {
            $msg = "Erreur : parcours inexistant."; 
            $code_reponse = 400; 
        }
        else {
            // récupération de l'id de l'utilisateur connecté et celui du propriétaire de la trace
            $idUtilisateur = $dao->getUnUtilisateur($pseudo)->getId(); 
            $idPropriétaire = $dao->getUneTrace($idTrace)->getIdUtilisateur(); 
            //On peut maintenant vérifier que l'utilisateur connecté est bien le propriétaire de la trace 
            if ($idUtilisateur != $idPropriétaire)
            {
                $msg = "Erreur : vous n'êtes pas le propriétaire de ce parcours."; 
                $code_reponse = 400; 
            }
            else 
            {
               // Si tout est bon, on peut effectuer la supression
               $ok = $dao ->supprimerUneTrace($idTrace);
               if ( ! $ok )
               {
                   $msg = "Erreur : problème lors de la suppression du parcours.";
                   $code_reponse = 500;
               }
               else {
                   $msg = "Parcours supprimé.";
                   $code_reponse = 200;
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
    $elt_commentaire = $doc->createComment('Service web SupprimerUnParcours - BTS SIO - Lycée De La Salle - Rennes');
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