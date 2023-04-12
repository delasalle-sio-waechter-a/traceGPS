<?php

include_once ('Outils.class.php');

class Utilisateur
{
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Attributs privés de la classe -------------------------------------
    // ------------------------------------------------------------------------------------------------------
    private $id; // identifiant de l'utilisateur (numéro automatique dans la BDD)
    private $pseudo; // pseudo de l'utilisateur
    private $mdpSha1; // mot de passe de l'utilisateur (hashé en SHA1)
    private $adrMail; // adresse mail de l'utilisateur
    private $numTel; // numéro de téléphone de l'utilisateur
    private $niveau; // niveau d'accès : 1 = utilisateur (pratiquant ou proche) 2 = administrateur
    private $dateCreation; // date de création du compte
    private $nbTraces; // nombre de traces stockées actuellement
    private $dateDerniereTrace; // date de début de la dernière trace
    
    // ------------------------------------------------------------------------------------------------------
    // ----------------------------------------- Constructeur -----------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    public function __construct($unId, $unPseudo, $unMdpSha1, $uneAdrMail, $unNumTel, $unNiveau,
        $uneDateCreation, $unNbTraces, $uneDateDerniereTrace)
    {
        $this->id = $unId;
        $this->pseudo = $unPseudo;
        $this->mdpSha1 = $unMdpSha1;
        $this->adrMail = $uneAdrMail;
        $this->numTel = Outils::corrigerTelephone($unNumTel);
        $this->niveau = $unNiveau;
        $this->dateCreation = $uneDateCreation;
        $this->nbTraces = $unNbTraces;
        $this->dateDerniereTrace = $uneDateDerniereTrace;

        // Utilisez la classe Outils pour améliorer la forme du numéro de téléphone
    }
    
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------------- Getters et Setters ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    public function getId()
    {
        return $this->id;
    }
    public function setId($value)
    {
        $this->id = $value;
    }
    
    
    public function getPseudo()
    {
        return $this->pseudo;
    }
    public function setPseudo($value)
    {
        $this->pseudo = $value;
    }
    
    
    public function getMdpSha1()
    {
        return $this->mdpSha1;
    }
    public function setMdpSha1($value)
    {
        $this->mdpSha1 = $value;
    }
    
    
    public function getAdrMail()
    {
        return $this->adrMail;
    }
    public function setAdrMail($value)
    {
        $this->adrMail = $value;
    }
    
    
    public function getNumTel()
    {
        return $this->numTel;
    }
    public function setNumTel($value)
    {
        $this->numTel = Outils::corrigerTelephone($value);
    }
    
    
    public function getNiveau()
    {
        return $this->niveau;
    }
    public function setNiveau($value)
    {
        $this->niveau = $value;
    }
    
    
    public function getDateCreation()
    {
        return $this->dateCreation;
    }
    public function setDateCreation($value)
    {
        $this->dateCreation = $value;
    }
    
    
    public function getNbTraces()
    {
        return $this->nbTraces;
    }
    public function setNbTraces($value)
    {
        $this->nbTraces = $value;
    }
    
    
    public function getDateDerniereTrace()
    {
        return $this->dateDerniereTrace;
    }
    public function setDateDerniereTrace($value)
    {
        $this->dateDerniereTrace = $value;
    }
    
    // ------------------------------------------------------------------------------------------------------
    // -------------------------------------- Méthodes d'instances ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    public function toString() {
        $msg = 'id : ' . $this->id . '<br>';
        $msg .= 'pseudo : ' . $this->pseudo . '<br>';
        $msg .= 'mdpSha1 : ' . $this->mdpSha1 . '<br>';
        $msg .= 'adrMail : ' . $this->adrMail . '<br>';
        $msg .= 'numTel : ' . $this->numTel . '<br>';
        $msg .= 'niveau : ' . $this->niveau . '<br>';
        $msg .= 'dateCreation : ' . $this->dateCreation . '<br>';
        $msg .= 'nbTraces : ' . $this->nbTraces . '<br>';
        $msg .= 'dateDerniereTrace : ' . $this->dateDerniereTrace . '<br>';
        return $msg;
    }
}

