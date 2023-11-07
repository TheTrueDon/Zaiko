<?php

include_once '../db_connect.php';
include_once '../functions.php';

mysqli_set_charset($mysqli, 'utf8');

sec_session_start();

	if (login_check($mysqli) == true) {
    $logged = 'in';
	} else {
		$logged = 'out';
	}

$userid = htmlentities($_SESSION['user_id']);

// Daten AKTUALISIEREN----------------------------------------------------------
$idneu = $_POST["idname"];
$titelneu = $_POST["titel"];
$standortneu = $_POST["standort"];


if (isset($_POST["titel"]) && isset($_POST["idname"]) && isset($_POST["standort"]) && $titelneu != '' && $standortneu != '')
{
		$aendern = "UPDATE VB_Inventar SET titel ='" . $titelneu . "', standort = '" .$standortneu . "', controlled = 1 WHERE id = " .$idneu ;
//daten schreiben

		if($update_stmt = $mysqli->prepare($aendern)){
  		if(! $update_stmt->execute()){
    		echo $mysqli->error;
  		}
		}

}
//seite neu laden
header("Location: ../../pages/control.php");

?>
