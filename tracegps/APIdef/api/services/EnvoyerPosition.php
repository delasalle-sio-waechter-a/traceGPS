<?php
// Projet TraceGPS - services web
// fichier :  api/services/EnvoyerPosition.php
// Dernière mise à jour : 02/12/22 par nB

// Rôle : ce service web permet à un utilisateur authentifié d'envoyer sa position.
// Paramètres à fournir :
// • pseudo : le pseudo de l'utilisateur
// • mdp : le mot de passe de l'utilisateur hashé en sha1
// • idTrace : l'id de la trace dont le point fera partie
// • dateHeure : la date et l'heure au point de passage (format 'Y-m-d H:i:s')
// • latitude : latitude du point de passage
// • longitude : longitude du point de passage
// • altitude : altitude du point de passage
// • rythmeCardio : rythme cardiaque au point de passage (ou 0 si le rythme n'est pas mesurable)
// • lang : le langage utilisé pour le flux de données ("xml" ou "json")
// connexion du serveur web à la base MySQL
$dao = new DAO();




// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace  = ( empty($this->request['idTrace'])) ? "" : $this->request['idTrace'];
$dateHeure  = ( empty($this->request['dateHeure'])) ? "" : $this->request['dateHeure'];
$latitude  = ( empty($this->request['latitude'])) ? "" : $this->request['latitude'];
$longitude  = ( empty($this->request['longitude'])) ? "" : $this->request['longitude'];
$altitude  = ( empty($this->request['altitude'])) ? "" : $this->request['altitude'];
$rythmeCardio  = ( empty($this->request['rythmeCardio'])) ? "" : $this->request['rythmeCardio'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];


$donnee = "";
// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// Les paramètres doivent être présents
if ($pseudo == "" || $mdp == "" || $idTrace == "" || $dateHeure == "" || $rythmeCardio == "" || $latitude == "" || $longitude == "" || $altitude == "") {
    $msg = "Erreur : données incomplètes.";
    $code_reponse = 200;
}
else
{
    if ($dao->getUnUtilisateur($pseudo) == null || strtolower($dao->getUnUtilisateur($pseudo)->getMdpSha1()) != strtolower($mdp))
    {
        $msg = "Erreur : authentification incorrecte.";
        $code_reponse = 200;
    }
    else
    {
        
        if(!$dao->getUneTrace($idTrace))
        {
            $msg = "Erreur : le numéro de trace n'existe pas.";
            $code_reponse = 200;
        }
        else
        {
            $userId = $dao->getUnUtilisateur($pseudo)->getId();
            if (!$dao->getLesTraces($userId)) {
                $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                $code_reponse = 200;
                
            }
            else
            {
                $ok = false;
                $curTrace = null;
                foreach ($dao->getLesTraces($userId) as $trace)
                {
                    if($trace->getId() == $idTrace)
                    {
                        $curTrace = $trace;
                        $ok = true;
                    }
                    
                }
                if(!$ok)
                {
                    $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                    $code_reponse = 200;
                }
                else
                {
                    if($curTrace->getTerminee())
                    {
                        $msg = "Erreur : la trace est déjà terminée.";
                        $code_reponse = 200;
                        
                    }
                    else
                    {
                        
                        
                        $pointId =  $curTrace->getNombrePoints();
                        $pdt = new PointDeTrace($idTrace, $pointId, $latitude, $longitude, $altitude, $dateHeure, $rythmeCardio, 0, 0, 0);
                        if(!$dao->creerUnPointDeTrace($pdt))
                        {
                            $msg = "Erreur : problème lors de l'enregistrement du point.";
                            $code_reponse = 200;
                            
                        }
                        else
                        {
                            $msg = "Point créé.";
                            $donnee = $pointId;
                            $code_reponse = 200;
                        }
                        
                    }
                }
                
                
            }
        }
    }
    
}


// ferme la connexion à MySQL :
unset($dao);



// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($msg,$donnee);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($msg,$donnee);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);






// fin du programme (pour ne pas enchainer sur les 3 fonctions qui suivent)
exit;



// création du flux XML en sortie
function creerFluxXML($msg,$donnee)
{
    /* Exemple de code XML
     <?xml version="1.0" encoding="UTF-8"?>
     <data>
     <reponse>............. (message retourné par le service web) ...............</reponse>
     <donnees/>
     </data>
     */
    
    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web ChangerDeMdp - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' juste après l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    if($donnee == "")
    {
        $elt_donnee = $doc->createElement('donnees', $donnee);
        $elt_data->appendChild($elt_donnee);
        
    }
    else
    {
        $elt_donnee = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnee);
        $elt_id = $doc->createElement('id',$donnee);
        $elt_donnee->appendChild($elt_id);
    }
    
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg,$donnee)
{
    /* Exemple de code JSON
     {
     "data":
     {
     "reponse": "............. (message retourné par le service web) ...............",
     "donnees": [ ]
     }
     }
     }
     */
    $elt_data = array();
    
    // construction de l'élément "data"
    $elt_data["reponse"] = $msg;
    
    // construction de l'élément "donnees"
    if($donnee == "")
    {
        $elt_data["donnee"] = $donnee;
    }
    else
    {
        $ids = array();
        $elt_data["donnee"] = ($ids["id"] = $donnee);
    }
    
    
    
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

// ================================================================================================
?>
