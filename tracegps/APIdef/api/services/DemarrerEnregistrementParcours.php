<?php
// Rôle : ce service web permet à un utilisateur de démarrer l'enregistrement d'un parcours.
// Paramètres à fournir :
// • pseudo : le pseudo de l'utilisateur
// • mdp : le mot de passe de l'utilisateur hashé en sha1
// • lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Description du traitement :
// • Vérifier que les données transmises sont complètes
// • Vérifier l'authentification de l'utilisateur
// • Créer une nouvelle trace dans la base de données pour cet utilisateur (avec les champs
//     terminee=0 et dateFin=null)

$dao = new DAO();




// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

$laTrace = null;

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// Les paramètres doivent être présents
if ($pseudo == "" || $mdp == "") {
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
        $unIdUtilisateur = $dao->getUnUtilisateur($pseudo)->getId();
        $unId = 0;
        $uneDateHeureDebut = date('Y-m-d H:i:s', time());
        $uneDateHeureFin = "";
        $terminee = 0;  
        
        $uneTrace = new Trace($unId, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur);
        
        if(!$dao->creerUneTrace($uneTrace))
        {
            $msg = "Erreur : pas de connexion Internet.";
            $code_reponse = 200;
        }
        else 
        {
            $msg = "Trace créée.";
            $code_reponse = 200;

            foreach ($dao->getLesTraces($unIdUtilisateur) as $tempTrace)
            {
                $laTrace = $tempTrace;
            }
            
            
        }
    }
}





// ferme la connexion à MySQL :
unset($dao);



// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($msg,$laTrace);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($msg,$laTrace);
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
        // id Utilisateur
        $elt_idUtilisateur = $doc->createElement('idUtilisateur',$trace->getIdUtilisateur());
        $elt_trace->appendChild($elt_idUtilisateur);
        
        
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
        $elt_trace['idUtilisateur'] = $trace->getIdUtilisateur();
        
       
        $elt_donnee = ["trace" => $elt_trace];
            
        // construction de l'élément "data"
        $elt_data = ["reponse" => $msg, "donnees" => $elt_donnee];
        
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





