<?php
// Projet TraceGPS - services web
// fichier :  api/services/GetUnParcoursEtSesPoints.php
// Dernière mise à jour : 06/12/22 par nB


// Rôle : ce service web permet à un utilisateur d'obtenir le détail d'un de ses parcours ou d'un parcours
// d'un membre qui l'autorise.
// Paramètres à fournir :
// • pseudo : le pseudo de l'utilisateur
// • mdp : le mot de passe de l'utilisateur hashé en sha1
// • idTrace : l'id de la trace à consulter
// • lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Description du traitement :
// • Vérifier que les données transmises sont complètes
// • Vérifier l'authentification de l'utilisateur
// • Vérifier l'existence de la trace demandée
// • Vérifier si l'utilisateur est le propriétaire de la trace, ou si il est autorisé à le consulter
// • Fournir les données complètes de la trace

$dao = new DAO();




// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace  = ( empty($this->request['idTrace'])) ? "" : $this->request['idTrace'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

$donnees = array();
$donnees[] = "";
$trace = null;

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// Les paramètres doivent être présents
if ($pseudo == "" || $mdp == "" || $idTrace == "") {
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
            $msg = "Erreur : parcours inexistant.";
            $code_reponse = 200;
        }
        else 
        {
            // check si la trace est consulttable ou est appartient a l'utilisateur
            
            $userId = $dao->getUnUtilisateur($pseudo)->getId();
            
            $ok = false;
            $curTrace = null;
            foreach ($dao->getLesTracesAutorisees($userId) as $tempTrace)
            {
                if($tempTrace->getId() == $idTrace)
                {
                    $curTrace = $tempTrace;
                    $ok = true;
                }
                
            }
            
            if (!$ok || $ok == false) {
                $msg = "Erreur : vous n'êtes pas autorisé par le propriétaire du parcours.";
                $code_reponse = 200;
                $trace = null;
            }
            // tout est fonctionnel
            else
            {
                $trace = $curTrace;
                $msg = "Données de la trace demandée.";
                $code_reponse = 200;
            }
        
            
        }
        
        
        
    }

}





// ferme la connexion à MySQL :
unset($dao);



// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($msg,$trace);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($msg,$trace);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

//fin du programme pour ne pas enchainer sur les 2 fonctions qui suivent
exit;

// =======================================================================================================================

//creation du flux XML en sortie
function creerFluxXML($msg,$trace)
{
    
//     xml version="1.0" encoding="UTF-8"
// <!-- <!--Service web GetUnParcoursEtSesPoints - BTS SIO - Lycée De La Salle - Rennes--> -->
// <data>
//  <reponse>Données de la trace demandée.</reponse>
//  <donnees>
//  <trace>
//  <id>2</id>
//  <dateHeureDebut>2018-01-19 13:08:48</dateHeureDebut>
//  <terminee>1</terminee>
//  <dateHeureFin>2018-01-19 13:11:48</dateHeureFin>
//  <idUtilisateur>2</idUtilisateur>
//  </trace>
//  <lesPoints>
//  <point>
//  <id>1</id>
//  <latitude>48.2109</latitude>
//  <longitude>-1.5535</longitude>
//  <altitude>60</altitude>
//  <dateHeure>2018-01-19 13:08:48</dateHeure>
//  <rythmeCardio>81</rythmeCardio>
//  </point>
//  .....................................................................................................
//  <point>
//  <id>10</id>
//  <latitude>48.2199</latitude>
//  <longitude>-1.5445</longitude>
//  <altitude>150</altitude>
//  <dateHeure>2018-01-19 13:11:48</dateHeure>
//  <rythmeCardio>90</rythmeCardio>
//  </point>
//  </lesPoints>
//  </donnees>
// </data>
    
    
    // créé une instance de DOMDocument (Document Object Modele)
    $doc = new DOMDocument();
    
    // spécifie la version et le type d'encodage
    $doc->version ='1.0';
    $doc->encoding ='utf-8';
    
    //créé un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web GetUnParcoursEtSesPoints.php - BTS SIO - Lycée De La Salle - Rennes');
    
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' juste dans l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    
    // check de si le traitement est réussi
    if(!is_null($trace) || $trace  != NULL)
    {
        // place l'element "donnees" dans l'element data
        $elt_donnees = $doc->createElement('donnees');
        $elt_data->appendChild($elt_donnees );
        
        $elt_trace = $doc->createElement('Trace');
        $elt_donnees->appendChild($elt_trace);
        // id de la trace
        $elt_idTrace = $doc->createElement('id',$trace->getId());
        $elt_trace->appendChild($elt_idTrace);
        // date trace debut
        $elt_dateHeureDebut = $doc->createElement('dateHeureDebut',$trace->getDateHeureDebut());
        $elt_trace->appendChild($elt_dateHeureDebut);
        // trace termine
        $elt_terminee = $doc->createElement('terminee',$trace->getTerminee());
        $elt_trace->appendChild($elt_terminee);
        // date trace fin
        $elt_dateHeureFin = $doc->createElement('dateHeureFin',$trace->getDateHeureFin());
        $elt_trace->appendChild($elt_dateHeureFin);
        // id Utilisateur 
        $elt_idUtilisateur = $doc->createElement('idUtilisateur',$trace->getIdUtilisateur());
        $elt_trace->appendChild($elt_idUtilisateur);
        
        
        //traitement des Points
        // s'il y en a
        if ($trace->getNombrePoints() > 0) {


            // place l'élément 'lesUtilisateurs' dans l'élément 'donnees'
            $elt_lesPoints = $doc->createElement('lesPoints');
            $elt_donnees->appendChild($elt_lesPoints);
            foreach ($trace->getLesPointsDeTrace() as $pdt)
            {
                // crée un élément vide 'utilisateur'
                $elt_point = $doc->createElement('point');
                // place l'élément 'utilisateur' dans l'élément 'lesUtilisateurs'
                $elt_lesPoints->appendChild($elt_point);
                
                // crée les éléments enfants de l'élément 'utilisateur'
                $elt_id  = $doc->createElement('id', $pdt->getId());
                
                $elt_point->appendChild($elt_id);
                
                $elt_latitude     = $doc->createElement('latitude', $pdt->getLatitude());
                $elt_point->appendChild($elt_latitude);
                
                $elt_longitude    = $doc->createElement('longitude', $pdt->getLongitude());
                $elt_point->appendChild($elt_longitude);
                
                $elt_altitude     = $doc->createElement('altitude', $pdt->getAltitude());
                $elt_point->appendChild($elt_altitude);
                
                $elt_dateHeure     = $doc->createElement('dateHeure', $pdt->getDateHeure());
                $elt_point->appendChild($elt_dateHeure);
                
                $elt_rythmeCardio = $doc->createElement('rythmeCardio', $pdt->getRythmeCardio());
                $elt_point->appendChild($elt_rythmeCardio);

            }
            
        }
    }
    
    
    // Mise en forme finale
    $doc->formatOutput = true;
    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg,$trace)
{
    /* Exemple de code JSON
     {
     "data": {
     "reponse": "Données de la trace demandée.",
     "donnees": {
     "trace": {
     "id": "2",
     "dateHeureDebut": "2018-01-19 13:08:48",
     "terminee: "1",
     "dateHeureFin: "2018-01-19 13:11:48",
     "idUtilisateur: "2"
     }
     "lesPoints": [
     {
     "id": "1",
     "latitude": "48.2109",
     "longitude": "-1.5535",
     "altitude": "60",
     "dateHeure": "2018-01-19 13:08:48",
     "rythmeCardio": "81"
     },
     ..................................
     {
     "id"10</id>,
     "latitude": "48.2199",
     "longitude": "-1.5445",
     "altitude": "150",
     "dateHeure": "2018-01-19 13:11:48",
     "rythmeCardio": "90"
     }
     ]
     }
     }
    }
     */
    

    
    
    if($trace != NULL)
    {
        $elt_trace = array();
        $elt_trace['id'] = $trace->getId();
        $elt_trace['dateHeureDebut'] = $trace->getDateHeureDebut();
        $elt_trace['terminee'] = $trace->getTerminee();
        $elt_trace['dateHeureFin'] = $trace->getDateHeureFin();
        $elt_trace['idUtilisateur'] = $trace->getIdUtilisateur();
        
        
        if ($trace->getNombrePoints() == 0){
            // construction de l'élément "data"
            $elt_data = ["reponse" => $msg, 'donnees' => $elt_trace];
        }
        else
        {
            
            // construction d'un tableau contenant les utilisateurs
            $lesLignesDesPoint = array();
            foreach ($trace->getLesPointsDeTrace() as $pdt)
            {
                // crée une ligne dans le tableau
                $uneLigne = array();
                
                $uneLigne["id"] = $pdt->getId();
                $uneLigne["latitude"] = $pdt->getLatitude();
                $uneLigne["longitude"] = $pdt->getLongitude();
                $uneLigne["altitude"] = $pdt->getAltitude();
                $uneLigne["dateHeure"] = $pdt->getDateHeure();
                $uneLigne["rythmeCardio"] = $pdt->getRythmeCardio();
                
                $lesLignesDesPoint[] = $uneLigne; // ajoute la liste $uneLigne à la liste principale $lesLignesDuTableaul
            }
            // construction de l'élément "lesUtilisateurs"
            $elt_lesPoints = ["lesPoints" => $lesLignesDesPoint];
            
            $elt_donnee = ["trace" => $elt_trace, $elt_lesPoints];
            
            // construction de l'élément "data"
            $elt_data = ["reponse" => $msg, "donnees" => $elt_donnee];
        }
    }
    else 
    {
        $elt_data = ["reponse" => $msg];
    }
    
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}



