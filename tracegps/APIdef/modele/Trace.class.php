<?php
include_once ('PointDeTrace.class.php');

class Trace
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Attributs privés de la classe -------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    private $id; // identifiant de la trace
    private $dateHeureDebut; // date et heure de début
    private $dateHeureFin; // date et heure de fin
    private $terminee; // true si la trace est terminée, false sinon
    private $idUtilisateur; // identifiant de l'utilisateur ayant créé la trace
    private $lesPointsDeTrace; // la collection (array) des objets PointDeTrace formant la trace

    // ------------------------------------------------------------------------------------------------------
    // ----------------------------------------- Constructeur -----------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    public function __construct($unId, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur)
    {
        // A VOUS DE TROUVER LE CODE MANQUANT
        $this->id = $unId;
        $this->dateHeureDebut = $uneDateHeureDebut;
        $this->dateHeureFin = $uneDateHeureFin;
        $this->terminee = $terminee;
        $this->idUtilisateur = $unIdUtilisateur;
        $this->lesPointsDeTrace = array();
    }
    
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------------- Getters et Setters ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    public function getId() {return $this->id;}
    public function setId($unId) {$this->id = $unId;}
    
    public function getDateHeureDebut() {return $this->dateHeureDebut;}
    public function setDateHeureDebut($uneDateHeureDebut) {$this->dateHeureDebut = $uneDateHeureDebut;}
    public function getDateHeureFin() {return $this->dateHeureFin;}
    public function setDateHeureFin($uneDateHeureFin) {$this->dateHeureFin= $uneDateHeureFin;}
    
    public function getTerminee() {return $this->terminee;}
    public function setTerminee($terminee) {$this->terminee = $terminee;}
    
    public function getIdUtilisateur() {return $this->idUtilisateur;}
    public function setIdUtilisateur($unIdUtilisateur) {$this->idUtilisateur = $unIdUtilisateur;}
    
    public function getLesPointsDeTrace() {return $this->lesPointsDeTrace;}
    public function setLesPointsDeTrace($lesPointsDeTrace) {$this->lesPointsDeTrace = $lesPointsDeTrace;}
    
    // Fournit une chaine contenant toutes les données de l'objet
    public function toString() {
        $msg = "Id : " . $this->getId() . "<br>";
        $msg .= "Utilisateur : " . $this->getIdUtilisateur() . "<br>";
        if ($this->getDateHeureDebut() != null) {
            $msg .= "Heure de début : " . $this->getDateHeureDebut() . "<br>";
        }
        if ($this->getTerminee()) {
            $msg .= "Terminée : Oui <br>";
        }
        else {
            $msg .= "Terminée : Non <br>";
        }
        $msg .= "Nombre de points : " . $this->getNombrePoints() . "<br>";
        if ($this->getNombrePoints() > 0) {
            if ($this->getDateHeureFin() != null) {
                $msg .= "Heure de fin : " . $this->getDateHeureFin() . "<br>";
            }
            $msg .= "Durée en secondes : " . $this->getDureeEnSecondes() . "<br>";
            $msg .= "Durée totale : " . $this->getDureeTotale() . "<br>";
            $msg .= "Distance totale en Km : " . $this->getDistanceTotale() . "<br>";
            $msg .= "Dénivelé en m : " . $this->getDenivele() . "<br>";
            $msg .= "Dénivelé positif en m : " . $this->getDenivelePositif() . "<br>";
            $msg .= "Dénivelé négatif en m : " . $this->getDeniveleNegatif() . "<br>";
            $msg .= "Vitesse moyenne en Km/h : " . $this->getVitesseMoyenne() . "<br>";
            $msg .= "Centre du parcours : " . "<br>";
            $msg .= " - Latitude : " . $this->getCentre()->getLatitude() . "<br>";
            $msg .= " - Longitude : " . $this->getCentre()->getLongitude() . "<br>";
            $msg .= " - Altitude : " . $this->getCentre()->getAltitude() . "<br>";
        }
        return $msg;
    }
    
    //
    // METHODES D'INSTANCES
    //
    
    public function getNombrePoints()
    {
        return sizeof($this->lesPointsDeTrace);
    }
    
    public function getCentre()
    {
        $first = $this->lesPointsDeTrace[0];
        $latMin = $first->getLatitude(); $latMax = $first->getLatitude();
        $lonMin = $first->getLongitude(); $lonMax = $first->getLongitude();
        foreach($this->lesPointsDeTrace as $pdt)
        {
            $lat = $pdt->getLatitude();
            $lon = $pdt->getLongitude();
            if($lat < $latMin)
            {
                $latMin = $lat;
            }
            else if($lat > $latMax)
            {
                $latMax = $lat;
            }
            
            if($lon < $lonMin)
            {
                $lonMin = $lon;
            }
            else if($lon > $lonMax)
            {
                $lonMax = $lon;
            }
        }
        
        return new Point(($latMin + $latMax) / 2, ($lonMin + $lonMax) / 2, 0);
    }
    
    public function getDenivele()
    {
        if($this->getNombrePoints() > 0)
        {
            $first = $this->lesPointsDeTrace[0];
            $altMin = $first->getAltitude();
            $altMax = $first->getAltitude();
            
            foreach($this->lesPointsDeTrace as $pdt)
            {
                $alt = $pdt->getAltitude();
                if($alt < $altMin)
                {
                    $altMin = $alt;
                }
                else if($alt > $altMax)
                {
                    $altMax = $alt;
                }
            }
            
            return($altMax - $altMin);
        }
        return 0;
    }
    
    public function getDureeEnSecondes()
    {
        if($this->getNombrePoints() > 1)
        {
            $first = ($this->lesPointsDeTrace[0])->getTempsCumule();
            $indexMax = $this->getNombrePoints() -1;
            $last = ($this->lesPointsDeTrace[$indexMax])->getTempsCumule();
            $diff = $last - $first;
            return $diff;
        }
        elseif ($this->getNombrePoints() == 1)
        {
            return $this->lesPointsDeTrace[0]->getTempsCumule();
        }
        return 0;
    }
    
    public function getDureeTotale()
    {
        $temps = $this->getDureeEnSecondes();
        $secondes = $temps % 60;
        $temps = $temps / 60;
        $minutes = $temps % 60;
        $temps = $temps / 60;
        $heures = $temps % 60;
        // construction u message retourne
        $msg = "";
        if($heures < 10)
        {
            $msg .= "0";
        }
        $msg .= $heures . ":";
        if($minutes < 10)
        {
            $msg .= "0";
        }
        $msg .= $minutes . ":";
        if($secondes < 10)
        {
            $msg .= "0";
        }
        $msg .= $secondes;
        return $msg;
    }
    
    public function getDistanceTotale()
    {
        if($this->getNombrePoints() > 0)
        {
            $indexMax = $this->getNombrePoints() -1;
            return ($this->lesPointsDeTrace[$indexMax])->getDistanceCumulee();
        }
        return 0;
    }
    
    public function getDenivelePositif()
    {
        $total = 0;
        if($this->getNombrePoints() > 0)
        {
            for($i=1; $i < $this->getNombrePoints(); $i++)
            {
                $altPrecedent = ($this->lesPointsDeTrace[$i-1])->getAltitude();
                $alt = ($this->lesPointsDeTrace[$i])->getAltitude();
                if($alt-$altPrecedent > 0)
                {
                    $total += ($alt-$altPrecedent);
                }
            }
        }
        return $total;
    }
    
    public function getDenivelenegatif()
    {
        $total = 0;
        if($this->getNombrePoints() > 0)
        {
            for($i=1; $i < $this->getNombrePoints(); $i++)
            {
                $altPrecedent = ($this->lesPointsDeTrace[$i-1])->getAltitude();
                $alt = ($this->lesPointsDeTrace[$i])->getAltitude();
                if($altPrecedent-$alt > 0)
                {
                    $total += ($altPrecedent-$alt);
                }
            }
        }
        return $total;
    }
    
    public function getVitesseMoyenne()
    {
        if($this->getDureeEnSecondes() != 0) // éviter div par 0
        {
            $sec = $this->getDureeEnSecondes();
            $distanceTotale = $this->getDistanceTotale();
            return ($distanceTotale / ($sec / 3600) );
        }
    }
    
    public function ajouterPoint($pdt)
    {
        if($this->getNombrePoints() < 1)
        {
            $pdt->setDistanceCumulee(0);
            $pdt->setTempsCumule(0);
            $pdt->setVitesse(0);
            $this->lesPointsDeTrace[] = $pdt;
            // test push
            // tema le push
        }
        else
        {
            $this->lesPointsDeTrace[] = $pdt;
            $indexMax = $this->getNombrePoints() - 1;
            $current = $this->lesPointsDeTrace[$indexMax];
            $precedent = $this->lesPointsDeTrace[$indexMax-1];
            
            
            $current_time = DateTime::createFromFormat("Y-m-d H:i:s", $current->getDateHeure());
            $precedent_time = DateTime::createFromFormat("Y-m-d H:i:s", $precedent->getDateHeure());
            $current->setDistanceCumulee($precedent->getDistanceCumulee() + $current->getDistance($precedent, $current));
            // $diff est de type DateInterval
            $diff = $current_time->diff($precedent_time);
            // $diff_int représente $diff mais en secondes
            $diff_int = $diff->s + ($diff->i * 60) + ($diff->h * 60 * 60);
            $current->setTempsCumule($precedent->getTempsCumule() + $diff_int);
			if(doubleval($diff_int / 3600) == 0)
            {
                $current->setVitesse(0);
            }
            else
            {
                $current->setVitesse($current->getDistance($precedent, $current) / (doubleval($diff_int / 3600)));
            }
        }
    }
    
    public function viderListePoints()
    {
        $this->lesPointsDeTrace = array();
    }
    
    
    
} // fin de la classe Trace