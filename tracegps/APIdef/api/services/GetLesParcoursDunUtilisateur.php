<?php
// Rôle : ce service web permet à un utilisateur d'obtenir la liste de ses parcours ou la liste des parcours
// d'un utilisateur qui l'autorise.
// Paramètres à fournir :
// • pseudo : le pseudo de l'utilisateur
// • mdp : le mot de passe de l'utilisateur hashé en sha1
// • pseudoConsulte : le pseudo de l'utilisateur dont on veut consulter la liste des parcours
// • lang : le langage utilisé pour le flux de données ("xml" ou "json")
// Description du traitement :
// • Vérifier que les données transmises sont complètes
// • Vérifier l'authentification de l'utilisateur demandeur
// • Vérifier l'existence du pseudo de l'utilisateur consulté
// • Vérifier si l'utilisateur demandeur consulte ses propres traces, ou s’il est autorisé à consulter les
// traces de l'utilisateur consulté
// • Fournir la liste des traces de l'utilisateur consulté

$dao = new DAO();




// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdp = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoConsulte  = ( empty($this->request['pseudoConsulte'])) ? "" : $this->request['pseudoConsulte'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

$donnees = array();
$donnees[] = "";
$trace = null;

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// Les paramètres doivent être présents
if ($pseudo == "" || $mdp == "" || $pseudoConsulte == "") {
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
        if(!$dao->getUnUtilisateur($pseudoConsulte))
        {
            $msg = "Erreur : pseudo consulté inexistant.";
            $code_reponse = 200;
        }
        else 
        {
            $ok = false;
            $curId = $dao->getUnUtilisateur($pseudo)->getId();
            $consultId = $dao->getUnUtilisateur($pseudoConsulte)->getId();
            foreach ($dao->getLesUtilisateursAutorises($consultId) as $tempUser)
            {
                if($tempUser->getId() == $curId)
                {
                    $ok = true;
                    $idConsulte = $tempUser->getId();
                }
            }
            
            if(!$ok)
            {
                $msg = "Erreur : vous n'êtes pas autorisé par cet utilisateur.";
                $code_reponse = 200;
            }
            else 
            {
                if(!$dao->getLesTraces($idConsulte))
                {
                    $msg = "Aucune trace pour l'utilisateur ". $pseudoConsulte .".";
                    $code_reponse = 200;
                    $trace = null;
                }
                else
                {
                    $msg = (sizeof($dao->getLesTraces($idConsulte))). " trace(s) pour l'utilisateur " . $pseudoConsulte. ".";
                    $code_reponse = 200;
                    $trace = $dao->getLesTraces($idConsulte);
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
        
        $elt_lesTraces = $doc->createElement('lesTraces');
        $elt_donnees->appendChild($elt_lesTraces);
        
        foreach ($trace as $tempTrace)
        {
            
            $elt_trace = $doc->createElement('trace');
            $elt_lesTraces->appendChild($elt_trace);

            // id de la trace
            $elt_idTrace = $doc->createElement('id',$tempTrace->getId());
            $elt_trace->appendChild($elt_idTrace);
            // date trace debut
            $elt_dateHeureDebut = $doc->createElement('dateHeureDebut',$tempTrace->getDateHeureDebut());
            $elt_trace->appendChild($elt_dateHeureDebut);
            // trace termine
            $elt_terminee = $doc->createElement('terminee',$tempTrace->getTerminee());
            $elt_trace->appendChild($elt_terminee);
            // date trace fin
            $elt_dateHeureFin = $doc->createElement('dateHeureFin',$tempTrace->getDateHeureFin());
            $elt_trace->appendChild($elt_dateHeureFin);
            // id Utilisateur
            $elt_idUtilisateur = $doc->createElement('idUtilisateur',$tempTrace->getIdUtilisateur());
            $elt_trace->appendChild($elt_idUtilisateur);
 
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
        $lesTraces = array();
        
        
        foreach ($trace as $tempTrace)
        {
            $elt_trace = array();
            $elt_trace['id'] = $tempTrace->getId();
            $elt_trace['dateHeureDebut'] = $tempTrace->getDateHeureDebut();
            $elt_trace['terminee'] = $tempTrace->getTerminee();
            $elt_trace['dateHeureFin'] = $tempTrace->getDateHeureFin();
            $elt_trace['idUtilisateur'] = $tempTrace->getIdUtilisateur();
            
            $lesTraces[] = $elt_trace;
        }
        
        // construction de l'élément "lesUtilisateurs"

        $elt_donnee = ["lesTraces" => $lesTraces];
        
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





