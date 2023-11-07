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


// BILDUPLOAD ------------------------------------------------------------------

$target_dir = "../../uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

date_default_timezone_set("Europe/Berlin");
$timestamp = time();
$stempel = date("Y-m-d-H_i_s",$timestamp);
$target_file = $target_dir.$stempel.".".$imageFileType;

// Daten AKTUALISIEREN----------------------------------------------------------
$idneu = $_POST["idname"];
$titelneu = $_POST["titel"];
$standortneu = $_POST["standort"];
$stichwortneu = $_POST["stichworte"];
$autorneu = $_POST["autor"];
$verlagneu = $_POST["verlag"];
$signaturneu = $_POST["signatur"];
$ort_jahrneu = $_POST["ort_jahr"];
$qrcodeneu = $_POST["qrcode"];
$nullervergleich = "0.0";

if ($_POST["range_0"] == 0){
  $bewertungneu = '0.0';
} else {
      $bewertungneu = $_POST["range_0"] /100;
}

$anzkat = $_POST["anzkategorien"];

$i = 1;
while ($i < $anzkat-1){
	  $rangename = "range_". $i;
    if ($_POST[$rangename] == 0){
      $neuwert = '0.0';
    } else {
      		$neuwert = $_POST[$rangename] /100;
    }
		$bewertungneu = $bewertungneu . "#" . $neuwert ;
    $nullervergleich = $nullervergleich. "#0.0";
		$i++;
}
if ($bewertungneu == $nullervergleich){
  $bewertungneu = "";
}

//print_r($bewertungneu);

// Bewertungen für neue Tabelle vorbereiten



$key = ', kat1';
$val = ', ';
$isnull = "0.0";

if ($_POST["range_0"] == 0){
	$val = ", 0.0";
} else {
			$val = ", " . $_POST["range_0"] /100;
}

$zeile = 1;
$katnummer = 2;

while ($zeile < $anzkat-1){
		$rangename = "range_". $zeile;
		if ($_POST[$rangename] == 0){
			$neuwert = '0.0';
		} else {
					$neuwert = $_POST[$rangename] /100;

		}

		$key = $key . ", kat$katnummer";
		$val = $val . ", " . $neuwert;


		$isnull = $isnull . ", 0.0";
		$zeile++;
		$katnummer ++;
}


//print_r($sqlstmt_bewertung_teil);


if ($val == $isnull){
	$key = '';
	$val = '';
}

//print_r($sqlstmt_materialbewertung);


if (isset($_POST["titel"]) && isset($_POST["idname"]) && isset($_POST["standort"]))
{
  if (basename($_FILES["fileToUpload"]["name"])== "")
	{

		$aendern = "UPDATE VB_Inventar SET titel ='" . $titelneu . "', standort = '" .$standortneu . "', autor = '" . $autorneu . "', verlag = '" . $verlagneu . "', signatur = '" . $signaturneu . "', ort_jahr = '" . $ort_jahrneu . "' , stichwort = '" .$stichwortneu . "' , bewertung = '" .$bewertungneu . "', controlled = 1  WHERE id = " .$idneu ;
		$winpunkte = "UPDATE z_user SET punkte = punkte + 1  WHERE id = " .$userid ;

// SQL für neue Tabelle inkl. Bewertungen einfügen
		$sqlstmt_inventar = "UPDATE z_Inventar SET titel = '" . $titelneu . "', standort = '" . $standortneu . "', autor = '" . $autorneu . "', verlag = '" . $verlagneu . "', signatur = '" . $signaturneu . "', ort_jahr = '" . $ort_jahrneu . "' , stichwort = '" . $stichwortneu . "', qrcode = '" . $qrcodeneu . "', controlled = 1 WHERE id = '" . $idneu . "'";
		$sqlstmt_materialbewertung = "INSERT INTO z_materialbewertungen (inventar_id $key) VALUES ($idneu $val)";




	} else {




		// BILDUPLOAD WEITER -------------------------------------------------------


		// Check if image file is a actual image or fake image
		if(isset($_POST["submit"])) {
		    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
		    if($check !== false) {
		        echo "File is an image - " . $check["mime"] . ".";
		        $uploadOk = 1;
		    } else {
		        echo "Die Datei ist kein Bild.";
		        $uploadOk = 0;
		    }
		}
		// Check if file already exists

    if (file_exists($target_file)) {
		    echo "Sorry, die Datei gibt es schon online. Probiere es spaeter nochmal.";
		    $uploadOk = 0;
		}
		// Check file size
		if ($_FILES["fileToUpload"]["size"] > 5000000) {
		    echo "Sorry, dein Bild ist zu groß.";
		    $uploadOk = 0;
		}
		// Allow certain file formats
		if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
		&& $imageFileType != "gif" ) {
		    echo "Sorry, nur JPG, JPEG, PNG & GIF ist als Dateiformat erlaubt.";
		    $uploadOk = 0;
		}
		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 0) {
		    echo "Sorry, die Datei konnte nicht hochgeladen werden.";
		// if everything is ok, try to upload file
		} else {

		    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
		        echo "Deine Datei mit dem Namen ". basename( $_FILES["fileToUpload"]["name"]). " wurde erfolgreich hochgeladen.";

						$aendern = "UPDATE VB_Inventar SET titel ='" . $titelneu . "', standort = '" .$standortneu . "' , autor = '" . $autorneu . "', verlag = '" . $verlagneu . "', signatur = '" . $signaturneu . "', ort_jahr = '" . $ort_jahrneu . "' , stichwort = '" .$stichwortneu . "', piclink = '" . $target_file . "' , bewertung = '" .$bewertungneu . "', controlled = 1  WHERE id = " .$idneu ;
						$winpunkte = "UPDATE z_user SET punkte = punkte + 1  WHERE id = " .$userid ;

						// SQL für neue Tabelle inkl. Bewertungen einfügen
								$sqlstmt_inventar = "UPDATE z_Inventar SET titel = '" . $titelneu . "', standort = '" . $standortneu . "', autor = '" . $autorneu . "', verlag = '" . $verlagneu . "', signatur = '" . $signaturneu . "', ort_jahr = '" . $ort_jahrneu . "' , stichwort = '" . $stichwortneu . "' , piclink = '" . $target_file . "', qrcode = '" . $qrcodeneu . "', controlled = 1 WHERE id = '" . $idneu . "'";
								$sqlstmt_materialbewertung = "INSERT INTO z_materialbewertungen (inventar_id $key) VALUES ($idneu $val)";


		    } else {
		        echo "Sorry, es gab beim Upload des Bildes einen Fehler.";

						$aendern = "UPDATE VB_Inventar SET titel ='" . $titelneu . "', standort = '" .$standortneu . "', autor = '" . $autorneu . "', verlag = '" . $verlagneu . "', signatur = '" . $signaturneu . "', ort_jahr = '" . $ort_jahrneu . "' , stichwort = '" .$stichwortneu . " ' , bewertung = '" .$bewertungneu . "', qrcode = '" . $qrcodeneu . "', controlled = 1,  WHERE id = " .$idneu ;
						$winpunkte = "UPDATE z_user SET punkte = punkte + 1  WHERE id = " .$userid ;

						// SQL für neue Tabelle inkl. Bewertungen einfügen
								//$sqlstmt_inventar = "UPDATE z_Inventar SET titel = $titelneu, standort = $standortneu, autor = '" . $autorneu . "', verlag = '" . $verlagneu . "', signatur = '" . $signaturneu . "', ort_jahr = '" . $ort_jahrneu . "', stichwort = $stichwortneu, controlled = 1 WHERE id = $idneu";
								$sqlstmt_inventar = "UPDATE z_Inventar SET titel = '" . $titelneu . "', standort = '" . $standortneu . "', autor = '" . $autorneu . "', verlag = '" . $verlagneu . "', signatur = '" . $signaturneu . "', ort_jahr = '" . $ort_jahrneu . "', stichwort = '" . $stichwortneu . "', qrcode = '" . $qrcodeneu . "', controlled = 1 WHERE id = '" . $idneu . "'";
								$sqlstmt_materialbewertung = "INSERT INTO z_materialbewertungen (inventar_id $key) VALUES ($idneu $val)";

		    }
		}

// BILD UPLOAD ENDE ------------------------------------------------------------

	}

//daten schreiben

if($update_stmt = $mysqli->prepare($aendern)){
  if(! $update_stmt->execute()){
    echo $mysqli->error;
  }
}

if($update_NEU_stmt = $mysqli->prepare($sqlstmt_inventar)){
  if(! $update_NEU_stmt->execute()){
    echo $mysqli->error;
  }
}


//zaehler updaten
  if($erh_stmt = $mysqli->prepare($winpunkte)){
    if(! $erh_stmt->execute()){
      echo $mysqli->error;
    }
  }

	print_r($sqlstmt_materialbewertung);
	print_r();
	print_r($sqlstmt_inventar);
//bewertung scheiben
  if($mat_stmt = $mysqli->prepare($sqlstmt_materialbewertung)){
    if(! $mat_stmt->execute()){
      echo $mysqli->error;
    }
  }

}

// Seite neu laden
 if ($uploadOk != 0) {
      header("Location: ../../pages/control.php");
  }
?>
