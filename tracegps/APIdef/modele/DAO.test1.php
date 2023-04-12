<?php
// Projet TraceGPS
// fichier : modele/DAO.test1.php
// Rôle : test de la classe DAO.class.php
// Dernière mise à jour : xxxxxxxxxxxxxxxxx par xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

// Le code des tests restant à développer va être réparti entre les membres de l'équipe de développement.
// Afin de limiter les conflits avec GitHub, il est décidé d'attribuer un fichier de test à chaque développeur.
// Développeur 1 : fichier DAO.test1.php
// Développeur 2 : fichier DAO.test2.php
// Développeur 3 : fichier DAO.test3.php
// Développeur 4 : fichier DAO.test4.php

// Quelques conseils pour le travail collaboratif :
// avant d'attaquer un cycle de développement (début de séance, nouvelle méthode, ...), faites un Pull pour récupérer
// la dernière version du fichier.
// Après avoir testé et validé une méthode, faites un commit et un push pour transmettre cette version aux autres développeurs.
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Test de la classe DAO</title>
	<style type="text/css">body {font-family: Arial, Helvetica, sans-serif; font-size: small;}</style>
</head>
<body>

<?php
    // connexion du serveur web à la base MySQL
    include_once ('DAO.class.php');
    $dao = new DAO();
    
    
    // test de la méthode existeAdrMailUtilisateur ----------------------------------------------------------
    // modifié par Natan Brault le 22/11/2022
    echo "<h3>Test de existeAdrMailUtilisateur : </h3>";
    if ($dao->existeAdrMailUtilisateur("admin@gmail.com")) $existe = "oui"; else $existe = "non";
    echo "<p>Existence de l'utilisateur 'admin@gmail.com' : <b>" . $existe . "</b><br>";
    if ($dao->existeAdrMailUtilisateur("delasalle.sio.eleves@gmail.com")) $existe = "oui"; else $existe = "non";
    echo "Existence de l'utilisateur 'delasalle.sio.eleves@gmail.com' : <b>" . $existe . "</b></br>";
    
    // test de la méthode getLesUtilisateursAutorisant ----------------------------------------------------------
    // modifié par Natan Brault le 22/11/2022
    
    echo "<h3>Test de getLesUtilisateursAutorisant(idUtilisateur) : </h3>";
    $lesUtilisateurs = $dao->getLesUtilisateursAutorisant(4);
    $nbReponses = sizeof($lesUtilisateurs);
    echo "<p>Nombre d'utilisateurs autorisant l'utilisateur 4 à voir leurs parcours : " . $nbReponses . "</p>";
    // affichage des utilisateurs
    foreach ($lesUtilisateurs as $unUtilisateur)
    { echo ($unUtilisateur->toString());
    echo ('<br>');
    }
    
    // test de la méthode getLesUtilisateursAutorise ----------------------------------------------------------
    // modifié par Natan Brault le 23/11/2022
    
    echo "<h3>Test de getLesUtilisateursAutorises(idUtilisateur) : </h3>";
    $lesUtilisateurs = $dao->getLesUtilisateursAutorises(2);
    $nbReponses = sizeof($lesUtilisateurs);
    echo "<p>Nombre d'utilisateurs autorisés par l'utilisateur 2 : " . $nbReponses . "</p>";
    // affichage des utilisateurs
    foreach ($lesUtilisateurs as $unUtilisateur)
    { echo ($unUtilisateur->toString());
    echo ('<br>');
    }
    
    // test de la méthode autoriseAConsulter ----------------------------------------------------------
    // modifié par Natan Brault le 23/11/2022
    echo "<h3>Test de autoriseAConsulter : </h3>";
    if ($dao->autoriseAConsulter(2, 3)) $autorise = "oui"; else $autorise = "non";
    echo "<p>L'utilisateur 2 autorise l'utilisateur 3 : <b>" . $autorise . "</b><br>";
    if ($dao->autoriseAConsulter(3, 2)) $autorise = "oui"; else $autorise = "non";
    echo "<p>L'utilisateur 3 autorise l'utilisateur 2 : <b>" . $autorise . "</b><br>";
    
    
    // test de la méthode creerUneAutorisation ---------------------------------------------------------
    // modifié par dP le Natan Brault le 23/11/2022
    echo "<h3>Test de creerUneAutorisation : </h3>";
    if ($dao->creerUneAutorisation(2, 1)) $ok = "oui"; else $ok = "non";
    echo "<p>La création de l'autorisation de l'utilisateur 2 vers l'utilisateur 1 a réussi : <b>" . $ok . "</b><br>";
    // la même autorisation ne peut pas être enregistrée 2 fois
    if ($dao->creerUneAutorisation(2, 1)) $ok = "oui"; else $ok = "non";
    echo "<p>La création de l'autorisation de l'utilisateur 2 vers l'utilisateur 1 a réussi : <b>" . $ok . "</b><br>";
    
    
    // test de la méthode supprimerUneAutorisation ----------------------------------------------------
    // modifié par Natan Brault le 23/11/2022
    echo "<h3>Test de supprimerUneAutorisation : </h3>";
    // on crée une autorisation
    if ($dao->creerUneAutorisation(2, 1)) $ok = "oui"; else $ok = "non";
    echo "<p>La création de l'autorisation de l'utilisateur 2 vers l'utilisateur 1 a réussi : <b>" . $ok . "</b><br>";
    // puis on la supprime
    if ($dao->supprimerUneAutorisation(2, 1)) $ok = "oui"; else $ok = "non";
    echo "<p>La suppression de l'autorisation de l'utilisateur 2 vers l'utilisateur 1 a réussi : <b>" . $ok . "</b><br>";
    
    
    
    // test de la méthode getLesPointsDeTrace ---------------------------------------------------------
    // modifié par Natan Brault le 23/11/2022
    echo "<h3>Test de getLesPointsDeTrace : </h3>";
    $lesPoints = $dao->getLesPointsDeTrace(1);
    $nbPoints = sizeof($lesPoints);
    echo "<p>Nombre de points de la trace 1 : " . $nbPoints . "</p>";
    // affichage des points
    foreach ($lesPoints as $unPoint)
    { echo ($unPoint->toString());
    echo ('<br>');
    }
    
    
    // test de la méthode creerUnPointDeTrace ---------------------------------------------------------
    // modifié par Natan Brault le 23/11/2022
    echo "<h3>Test de creerUnPointDeTrace : </h3>";
    // on affiche d'abord le nombre de points (5) de la trace 1
    $lesPoints = $dao->getLesPointsDeTrace(1);
    $nbPoints = sizeof($lesPoints);
    echo "<p>Nombre de points de la trace 1 : " . $nbPoints . "</p>";
    // on crée un sixième point et on l'ajoute à la trace 1
    $unIdTrace = 1;
    $unID = 6;
    $uneLatitude = 48.20;
    $uneLongitude = -1.55;
    $uneAltitude = 50;
    $uneDateHeure = date('Y-m-d H:i:s', time());
    $unRythmeCardio = 80;
    $unTempsCumule = 0;
    $uneDistanceCumulee = 0;
    $uneVitesse = 15;
    $unPoint = new PointDeTrace($unIdTrace, $unID, $uneLatitude, $uneLongitude, $uneAltitude, $uneDateHeure,
        $unRythmeCardio, $unTempsCumule, $uneDistanceCumulee, $uneVitesse);
    $ok = $dao->creerUnPointDeTrace($unPoint);
    // on affiche à nouveau le nombre de points (6) de la trace 1
    $lesPoints = $dao->getLesPointsDeTrace(1);
    $nbPoints = sizeof($lesPoints);
    echo "<p>Nombre de points de la trace 1 : " . $nbPoints . "</p>";
    echo ('<br>');
    
    

    // test de la méthode getUneTrace -----------------------------------------------------------------
    // modifié par Natan Brault le 23/11/2022
    echo "<h3>Test de getUneTrace : </h3>";
    $uneTrace = $dao->getUneTrace(2);
    if ($uneTrace) {
        echo "<p>La trace 2 existe : <br>" . $uneTrace->toString() . "</p>";
    }
    else {
        echo "<p>La trace 2 n'existe pas !</p>";
    }
    $uneTrace = $dao->getUneTrace(100);
    if ($uneTrace) {
        echo "<p>La trace 100 existe : <br>" . $uneTrace->toString() . "</p>";
    }
    else {
        echo "<p>La trace 100 n'existe pas !</p>";
    }
    


    // test de la méthode getToutesLesTraces ----------------------------------------------------------
    // modifié par Natan Brault le 25/11/2022
    echo "<h3>Test de getToutesLesTraces : </h3>";
    $lesTraces = $dao->getToutesLesTraces();
    $nbReponses = sizeof($lesTraces);
    echo "<p>Nombre de traces : " . $nbReponses . "</p>";
    // affichage des traces
    foreach ($lesTraces as $uneTrace)
    { echo ($uneTrace->toString());
    echo ('<br>');
    }
    
    // test de la méthode getLesTraces($idUtilisateur) ------------------------------------------------
    // modifié par Natan Brault le 25/11/2022
    echo "<h3>Test de getLesTraces(idUtilisateur) : </h3>";
    $lesTraces = $dao->getLesTraces(2);
    $nbReponses = sizeof($lesTraces);
    echo "<p>Nombre de traces de l'utilisateur 2 : " . $nbReponses . "</p>";
    // affichage des traces
    foreach ($lesTraces as $uneTrace)
    { echo ($uneTrace->toString());
    echo ('<br>');
    }
    
    
    // test de la méthode getLesTracesAutorisees($idUtilisateur) --------------------------------------
    // modifié par Natan Brault le 25/11/2022
    echo "<h3>Test de getLesTracesAutorisees(idUtilisateur) : </h3>";
    $lesTraces = $dao->getLesTracesAutorisees(2);
    $nbReponses = sizeof($lesTraces);
    echo "<p>Nombre de traces autorisées à l'utilisateur 2 : " . $nbReponses . "</p>";
    // affichage des traces
    foreach ($lesTraces as $uneTrace)
    { echo ($uneTrace->toString());
    echo ('<br>');
    }
    $lesTraces = $dao->getLesTracesAutorisees(3);
    $nbReponses = sizeof($lesTraces);
    echo "<p>Nombre de traces autorisées à l'utilisateur 3 : " . $nbReponses . "</p>";
    // affichage des traces
    foreach ($lesTraces as $uneTrace)
    { echo ($uneTrace->toString());
    echo ('<br>');
    }
    
    
    // test de la méthode creerUneTrace ----------------------------------------------------------
    // modifié par Natan Brault le 25/11/2022
    echo "<h3>Test de creerUneTrace : </h3>";
    $trace1 = new Trace(0, "2017-12-18 14:00:00", "2017-12-18 14:10:00", true, 3);
    $ok = $dao->creerUneTrace($trace1);
    if ($ok) {
        echo "<p>Trace bien enregistrée !</p>";
        echo $trace1->toString();
    }
    else {
        echo "<p>Echec lors de l'enregistrement de la trace !</p>";
    }
    $trace2 = new Trace(0, date('Y-m-d H:i:s', time()), null, false, 3);
    $ok = $dao->creerUneTrace($trace2);
    if ($ok) {
        echo "<p>Trace bien enregistrée !</p>";
        echo $trace2->toString();
    }
    else {
        echo "<p>Echec lors de l'enregistrement de la trace !</p>";
    }
    
    // test de la méthode supprimerUneTrace -----------------------------------------------------------
    // modifié par Natan Brault le 25/11/2022
    echo "<h3>Test de supprimerUneTrace : </h3>";
    $ok = $dao->supprimerUneTrace(22);
    if ($ok) {
        echo "<p>Trace bien supprimée !</p>";
    }
    else {
        echo "<p>Echec lors de la suppression de la trace !</p>";
    }
    
    
    // test des méthodes creerUnPointDeTrace et terminerUneTrace --------------------------------------
    // modifié par Natan Brault le 25/11/2022
    echo "<h3>Test de terminerUneTrace : </h3>";
    // on choisit une trace non terminée
    $unIdTrace = 3;
    // on l'affiche
    $laTrace = $dao->getUneTrace($unIdTrace);
    echo "<h4>l'objet laTrace avant l'appel de la méthode terminerUneTrace : </h4>";
    echo ($laTrace->toString());
    echo ('<br>');
    // on la termine
    $dao->terminerUneTrace($unIdTrace);
    // et on l'affiche à nouveau
    $laTrace = $dao->getUneTrace($unIdTrace);
    echo "<h4>l'objet laTrace après l'appel de la méthode terminerUneTrace : </h4>";
    echo ($laTrace->toString());
    echo ('<br>');
    
    
    



// ferme la connexion à MySQL :
unset($dao);
?>

</body>
</html>