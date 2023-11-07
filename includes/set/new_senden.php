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

$titelneu = $_POST["titel"];
$standortneu = $_POST["standort"];
$stichwortneu = $_POST["stichworte"];
$autorneu = $_POST["autor"];
$verlagneu = $_POST["verlag"];
$signaturneu = $_POST["signatur"];
$ort_jahrneu = $_POST["ort_jahr"];
$beilagennneu = $_POST["beilagen"];
$bemerkungneu = $_POST["bemerkung"];
$anzahlneu = $_POST["anzahl"];
$isbnneu = $_POST["isbn"];
$qrcodeneu = $_POST["qrcode"];
if (isset($_POST["buch"]) && $_POST["buch"] == 'Yes'){
	$buchneu = 1;
}else {
	$buchneu = 0;
}

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

if ($val == $isnull){
	$key = '';
	$val = '';
}

if (isset($_POST["titel"]) && isset($_POST["standort"]))
{
  if (basename($_FILES["fileToUpload"]["name"])== "")
	{
		//INSERT STATEMENT OHNE PIC
		$sqlstmt_inventar = "INSERT INTO z_Inventar (titel, autor, verlag, standort, signatur, ort_jahr, beilagen, bemerkung, anzahl, isbn, stichwort, buch, qrcode)
		VALUES ('$titelneu', '$autorneu', '$verlagneu', '$standortneu', '$signaturneu', '$ort_jahrneu', '$beilagennneu', '$bemerkungneu', '$anzahlneu', '$isbnneu', '$stichwortneu', '$buchneu', '$qrcodeneu')";
		$winpunkte = "UPDATE z_user SET punkte = punkte + 1  WHERE id = " .$userid ;
		$sqlstmt_materialbewertung = "INSERT INTO z_materialbewertungen (inventar_id $key) VALUES (LAST_INSERT_ID() $val)";

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
				//print_r($_FILES);
				//echo  "NAME" . $target_file . "_____";
		        echo "Deine Datei mit dem Namen ". basename( $_FILES["fileToUpload"]["name"]). " wurde erfolgreich hochgeladen.";

						//INSERT STATEMENT MIT PICLINK
						$sqlstmt_inventar = "INSERT INTO z_Inventar (titel, autor, verlag, standort, signatur, ort_jahr, beilagen, bemerkung, anzahl, isbn, stichwort, buch, piclink, qrcode)
						VALUES ('$titelneu', '$autorneu', '$verlagneu', '$standortneu', '$signaturneu', '$ort_jahrneu', '$beilagennneu', '$bemerkungneu', '$anzahlneu', '$isbnneu', '$stichwortneu', '$buchneu', '$target_file', '$qrcodeneu')";

						$winpunkte = "UPDATE z_user SET punkte = punkte + 1  WHERE id = " .$userid ;
						$sqlstmt_materialbewertung = "INSERT INTO z_materialbewertungen (inventar_id $key) VALUES (LAST_INSERT_ID() $val)";



		    } else {
		        echo "Sorry, es gab beim Upload des Bildes einen Fehler.";

						//INSERT STATEMENT OHNE PICLINK
						$sqlstmt_inventar = "INSERT INTO z_Inventar (titel, autor, verlag, standort, signatur, ort_jahr, beilagen, bemerkung, anzahl, isbn, stichwort, buch, qrcode)
						VALUES ('$titelneu', '$autorneu', '$verlagneu', '$standortneu', '$signaturneu', '$ort_jahrneu', '$beilagennneu', '$bemerkungneu', '$anzahlneu', '$isbnneu', '$stichwortneu', '$buchneu', '$qrcodeneu')";
						$winpunkte = "UPDATE z_user SET punkte = punkte + 1  WHERE id = " .$userid ;
						$sqlstmt_materialbewertung = "INSERT INTO z_materialbewertungen (inventar_id $key) VALUES (LAST_INSERT_ID() $val)";

		    }
		}

// BILD UPLOAD ENDE ------------------------------------------------------------

	}

//Daten schreiben
if($update_NEU_stmt = $mysqli->prepare($sqlstmt_inventar)){
  if(! $update_NEU_stmt->execute()){
    echo $mysqli->error;
  }
}

echo $winpunkte;
//Zaehler updaten
  if($erh_stmt = $mysqli->prepare($winpunkte)){
    if(! $erh_stmt->execute()){
      echo $mysqli->error;
    }
  }

echo $sqlstmt_materialbewertung;
//Bewertung scheiben
  if($mat_stmt = $mysqli->prepare($sqlstmt_materialbewertung)){
    if(! $mat_stmt->execute()){
      echo $mysqli->error;
    }
  }

}

// Seite neu laden
 if ($uploadOk != 0) {
      header("Location: ../../pages/settings.php");
  }
?>
