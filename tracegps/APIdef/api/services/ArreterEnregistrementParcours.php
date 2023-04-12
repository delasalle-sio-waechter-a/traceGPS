<?php
// Projet TraceGPS - services web
// fichier :  api/services/ArreterEnregistrementParcours.php
// Dernière mise à jour : 09/12/2022 par Erwan
// Rôle : ce service web permet à un utilisateur de terminer l'enregistrement d'un parcours.
// Paramètres à fournir :
// • pseudo : le pseudo de l'utilisateur
// • mdp : le mot de passe de l'utilisateur hashé en sha1
// • idTrace : l'id de la trace à terminer
// • lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Description du traitement :
// • Vérifier que les données transmises sont complètes
// • Vérifier l'authentification de l'utilisateur
// • Vérifier l'existence de la trace à terminer
// • Vérifier si l'utilisateur est bien le propriétaire de la trace à terminer
// • Vérifier si la trace est déjà terminée
// • Modifier la trace dans la base de données en mettant à jour les champs terminee et dateFin

$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace  = ( empty($this->request['idTrace'])) ? "" : $this->request['idTrace'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";


$msg = "";
// Les paramètres doivent être présents
if ($pseudo == "" || $mdp == "" || $idTrace == "") {
    $msg = "Erreur : données incomplètes.";
    $code_reponse = 200;
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
        //Vérification que la trace à terminer existe bien
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
                $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                $code_reponse = 400;
            }
            else 
            {
              // On regarde si la trace est déjà terminée
              $dejaTermine = $dao->getUneTrace($idTrace)->getTerminee();
              if ($dejaTermine == 1)
              {
                  $msg = "Erreur : cette trace est déjà terminée.";
                  $code_reponse = 400; 
              }
              else {
                  // on peut donc terminer la trace 
                  $ok = $dao->terminerUneTrace($idTrace); 
                  if ( ! $ok )
                  {
                      $msg ="Erreur : problème lors de la fin de l'enregistrement de la trace."; 
                      $code_reponse = 500;
                  }
                  else {
                      $msg = "Enregistrement terminé."; 
                      $code_reponse = 200; 
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
    $elt_commentaire = $doc->createComment('Service web ArreterEnregistrementParcours - BTS SIO - Lycée De La Salle - Rennes');
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