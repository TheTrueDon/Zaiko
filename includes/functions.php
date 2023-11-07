<?php
include_once 'psl-config.php';

function sec_session_start() {
    $session_name = 'sec_session_id';   // vergib einen Sessionnamen
    $secure = SECURE;
    // Damit wird verhindert, dass JavaScript auf die session id zugreifen kann.
    $httponly = true;
    // Zwingt die Sessions nur Cookies zu benutzen.
    if (ini_set('session.use_only_cookies', 1) === FALSE) {
        header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
        exit();
    }
    // Holt Cookie-Parameter.
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params($cookieParams["lifetime"],
        $cookieParams["path"],
        $cookieParams["domain"],
        $secure,
        $httponly);
    // Setzt den Session-Name zu oben angegebenem.
    session_name($session_name);
    session_start();            // Startet die PHP-Sitzung
    session_regenerate_id();    // Erneuert die Session, löscht die alte.
}

function login($email, $password, $mysqli) {
    // Das Benutzen vorbereiteter Statements verhindert SQL-Injektion.
    if ($stmt = $mysqli->prepare("SELECT id, username, password, salt
        FROM z_user
       WHERE email = ?
        LIMIT 1")) {
        $stmt->bind_param('s', $email);  // Bind "$email" to parameter.
        $stmt->execute();
        $stmt->store_result();


        $stmt->bind_result($user_id, $username, $db_password, $salt);
        $stmt->fetch();

        // hash das Passwort mit dem eindeutigen salt.
        $password = hash('sha512', $password . $salt);
        if ($stmt->num_rows == 1) {
            // Wenn es den Benutzer gibt, dann wird überprüft ob das Konto
            // blockiert ist durch zu viele Login-Versuche

            if (checkbrute($user_id, $mysqli) == true) {
                // Konto ist blockiert
                // Schicke E-Mail an Benutzer, dass Konto blockiert ist
                return false;
            } else {
                // Überprüfe, ob das Passwort in der Datenbank mit dem vom
                // Benutzer angegebenen übereinstimmt.

                if ($db_password == $password) {
                    // Passwort ist korrekt!
                    // Hole den user-agent string des Benutzers.
                    $user_browser = $_SERVER['HTTP_USER_AGENT'];
                    // XSS-Schutz, denn eventuell wir der Wert gedruckt
                    $user_id = preg_replace("/[^0-9]+/", "", $user_id);
                    $_SESSION['user_id'] = $user_id;
                    // XSS-Schutz, denn eventuell wir der Wert gedruckt
                    $username = preg_replace("/[^a-zA-Z0-9_\-]+/",
                                                                "",
                                                                $username);
                    $_SESSION['username'] = $username;
                    $_SESSION['login_string'] = hash('sha512',
                              $password . $user_browser);
                    // Login erfolgreich.
                    return true;
                } else {
                    // Passwort ist nicht korrekt
                    // Der Versuch wird in der Datenbank gespeichert
                    $now = time();
                    $mysqli->query("INSERT INTO login_attempts(user_id, time)
                                    VALUES ('$user_id', '$now')");
                    return false;
                }
            }
        } else {
            //Es gibt keinen Benutzer.
            return false;
        }
    }
}

function checkbrute($user_id, $mysqli) {
    // Hole den aktuellen Zeitstempel
    $now = time();

    // Alle Login-Versuche der letzten zwei Stunden werden gezählt.
    $valid_attempts = $now - (2 * 60 * 60);

    if ($stmt = $mysqli->prepare("SELECT time
                             FROM login_attempts <code><pre>
                             WHERE user_id = ?
                            AND time > '$valid_attempts'")) {
        $stmt->bind_param('i', $user_id);

        // Führe die vorbereitet Abfrage aus.
        $stmt->execute();
        $stmt->store_result();

        // Wenn es mehr als 5 fehlgeschlagene Versuche gab
        if ($stmt->num_rows > 5) {
            return true;
        } else {
            return false;
        }
    }
}

function login_check($mysqli) {
    // Überprüfe, ob alle Session-Variablen gesetzt sind
    if (isset($_SESSION['user_id'],
                        $_SESSION['username'],
                        $_SESSION['login_string'])) {

        $user_id = $_SESSION['user_id'];
        $login_string = $_SESSION['login_string'];
        $username = $_SESSION['username'];

        // Hole den user-agent string des Benutzers.
        $user_browser = $_SERVER['HTTP_USER_AGENT'];

        if ($stmt = $mysqli->prepare("SELECT password
                                      FROM z_user
                                      WHERE id = ? LIMIT 1")) {
            // Bind "$user_id" zum Parameter.
            $stmt->bind_param('i', $user_id);
            $stmt->execute();   // Execute the prepared query.
            $stmt->store_result();

            if ($stmt->num_rows == 1) {
                // Wenn es den Benutzer gibt, hole die Variablen von result.
                $stmt->bind_result($password);
                $stmt->fetch();
                $login_check = hash('sha512', $password . $user_browser);

                if ($login_check == $login_string) {
                    // Eingeloggt!!!!
                    return true;
                } else {
                    // Nicht eingeloggt
                    return false;
                }
            } else {
                // Nicht eingeloggt
                return false;
            }
        } else {
            // Nicht eingeloggt
            return false;
        }
    } else {
        // Nicht eingeloggt
        return false;
    }
}

function esc_url($url) {

    if ('' == $url) {
        return $url;
    }

    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);

    $strip = array('%0d', '%0a', '%0D', '%0A');
    $url = (string) $url;

    $count = 1;
    while ($count) {
        $url = str_replace($strip, '', $url, $count);
    }

    $url = str_replace(';//', '://', $url);

    $url = htmlentities($url);

    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);

    if ($url[0] !== '/') {
        // Wir wollen nur relative Links von $_SERVER['PHP_SELF']
        return '';
    } else {
        return $url;
    }
}

function katString($katcount){
	$res = "kat1";
	for ($i = 2; $i <= $katcount; $i++) {
		$res = $res . ", kat$i";
	}
	return $res;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////

function phase_I_search_temp($mysqli, $searchstring) {
	if ($stmt = $mysqli->prepare("SELECT i.id, i.titel, i.standort, i.piclink FROM z_Inventar as i, z_materialbewertungen as m
	WHERE m.inventar_id = i.id and UPPER(i.titel) = UPPER(TRIM(BOTH ' ' from ?)) LIMIT 1")) {
		$stmt->bind_param('s', $searchstring);
        $stmt->execute();
        $stmt->store_result();


        $stmt->bind_result($inventar_id, $title, $standort, $piclink);
        if ($stmt->fetch()) {
			$_SESSION['phase_I_hit'] = [$inventar_id, $title, $standort, $piclink];
			if ($stmt = $mysqli->prepare("SELECT ". katString(84) ." FROM z_materialbewertungen WHERE inventar_id = $inventar_id LIMIT 1")) {
				$stmt->execute();
				$row = bind_result_array($stmt);
				$stmt->fetch();
				$_SESSION['phase_I_hit_point'] = $row;
			} else {
				$_SESSION['phase_I_hit_point'] = null;
			}

			return phase_II_search_point($mysqli, $_SESSION['phase_I_hit_point']);
		}
	}
	return tempsearch_textbased($mysqli, $searchstring);
}

function tempsearch_textbased($mysqli, $searchstring) {
	$result[0] = null;
	$suchwoerterarray = searchstring_preparing($mysqli, $searchstring);
	$wherebegriff = implode('%\' OR LOWER(titel) LIKE \'%',$suchwoerterarray);
	$wherebegriff = "LOWER(titel) LIKE '%" . $wherebegriff . "%'";

	if ($stmt = $mysqli->prepare("SELECT id, titel, standort, piclink FROM z_Inventar
			WHERE $wherebegriff AND controlled = 0
			ORDER BY titel ASC")) {
				$stmt->execute();
				$stmt->store_result();


				$stmt->bind_result($inventar_id, $title, $standort, $piclink);
				$i=0;
				while($stmt->fetch()) {
					$result[$i] = [$inventar_id, $title, $standort, $piclink, $rating];
					$i++;
				}
	}

	return $result;
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////

function phase_I_search($mysqli, $searchstring) {
	if ($stmt = $mysqli->prepare("SELECT id, titel, standort, piclink FROM z_Inventar WHERE UPPER(titel) = UPPER(TRIM(BOTH ' ' from ?)) LIMIT 1")) {
		$stmt->bind_param('s', $searchstring);
        $stmt->execute();
        $stmt->store_result();


        $stmt->bind_result($inventar_id, $title, $standort, $piclink);
        if ($stmt->fetch()) {
			$_SESSION['phase_I_hit'] = [$inventar_id, $title, $standort, $piclink];
			if ($stmt = $mysqli->prepare("SELECT ". katString(84) ." FROM z_materialbewertungen WHERE inventar_id = $inventar_id LIMIT 1")) {
				$stmt->execute();
				$row = bind_result_array($stmt);
				$stmt->fetch();
				$_SESSION['phase_I_hit_point'] = $row;
			} else {
				$_SESSION['phase_I_hit_point'] = null;
			}

			return phase_II_search_point($mysqli, $_SESSION['phase_I_hit_point']);
		}
	}
	return phase_II_search_string($mysqli, $searchstring);
}

function bind_result_array($stmt) {
    $meta = $stmt->result_metadata();
    $result = array();
    while ($field = $meta->fetch_field())
    {
        $result[$field->name] = NULL;
        $params[] = &$result[$field->name];
    }

    call_user_func_array(array($stmt, 'bind_result'), $params);
	$stmt->store_result();
    return $result;
}

function getCopy($row) {
    return array_map(create_function('$a', 'return $a;'), $row);
}

function phase_II_search_point($mysqli, $searchpoint) {

	$sql = "SELECT inv.id, inv.titel, inv.standort, inv.piclink, POW(POW(ABS(kat1-" . $searchpoint['kat1'] . "),84)";
	for ($i = 2; $i <= 84; $i++) {
		$sql = $sql . " + POW(ABS(kat$i-" . $searchpoint["kat$i"] . "),84)";
	}

	$sql = $sql . ",1/84) as r FROM z_materialbewertungen mat, z_Inventar inv where mat.inventar_id = inv.id ORDER BY r LIMIT 50";
	//echo $sql;

	$result[0] = $_SESSION['phase_I_hit'];

	if ($stmt = $mysqli->prepare($sql)) {
        //$stmt->bind_param('s', $searchstring);
        $stmt->execute();
        $stmt->store_result();
		$stmt->bind_result($inventar_id, $title, $standort, $piclink, $rating);
		$i=1;
		while($stmt->fetch()) {
			$result[$i] = [$inventar_id, $title, $standort, $piclink, $rating];
			$i++;
		}
	}
	echo $mysqli -> error;
	return $result;
}

function phase_II_search_string($mysqli, $searchstring) {
	$searchstrings = searchstring_preparing($mysqli, $searchstring);
	$sql = "SELECT keyword, ". katString(84) ." FROM z_keywordbewertungen WHERE keyword like '";
	$sql = $sql . implode("' or keyword like '", $searchstrings);
	$sql = $sql . "' LIMIT 1";

	//echo $sql;
	$searchpoint = array();
	for ($i=1; $i<=84; $i++) {
		$searchpoint["kat$i"] = 0;
	}

	//print_r($searchpoint);

	if ($stmt = $mysqli->prepare($sql)) {
		$stmt->execute();
		$row = bind_result_array($stmt);
		$div = 0;

		while ($stmt->fetch()) {
			$div++;
			for ($i=1; $i<=84; $i++) {
				$searchpoint["kat$i"] += $row[$i-1];
			}
			print_r($row);
		}
		if ($div>0) {
			for ($i=1; $i<=84; $i++) {
				$searchpoint["kat$i"] /= $div;
			}
		}
		return phase_II_search_point($mysqli, $searchpoint);
	} else {

	}

	return null;

}

function training($mysqli, $sourcePoint, $targetPoint, $learningrate) {
	for ($i=0; $i<sizeof($sourcePoint); $i++) {
		$sourcePoint[$i] = $sourcePoint[$i] - ($sourcePoint[$i]-$targetPoint[$i])*$learningrate;
	}
	return $sourcePoint;
}

function searchstring_preparing($mysqli, $searchstring) {
	$parts = explode(" ", $searchstring);
	$words = [];
	$x=0;
	for($i=0; $i<sizeof($parts); $i++) {
		$words[$x] = $parts[$i];
		$x++;
		$j=$i;
		$currWord= $parts[$i];
		while($j<sizeof($parts)-1){
			$j++;
			$currWord = $currWord . " " . $parts[$j];
			$words[$x] = $currWord;
			$x++;
		}
	}
	return $words;
}

function show_suchergebnis($obj_id, $titel, $ablageort, $bildlink, $bewertung){
  echo "<div class=\"ergebnissuchbox\">";
      echo "<div class=\"illustrationbox col\">";
    echo "<img class=\"illustration\" src =\" " . substr($bildlink, 3) . "\"> ";
    // Hier werden momentan die ersten Zeichen entfernt, da der Link in DB von hier aus falsch ist.
    echo "</div>";
    echo "<div class=\"suchboxbeschreibung col\">";
      echo "<a href=\"einzelobjektseite.php?id=$obj_id\"><h3>". $titel . "</h3></a>";
      echo "<h4> Standort (Regal/Fach): ". $ablageort . "</h4>";
      echo "<h5> Bewertung: ". $bewertung . "</h5>";
    echo "</div>";
  echo "</div>";
}

function get_objfromid($mysqli, $obj_id){

  if ($stmt = $mysqli->prepare("SELECT id, titel, autor, verlag, standort, signatur, ort_jahr, beilagen, bemerkung, anzahl, isbn, stichwort, buch, piclink
      FROM z_Inventar
      WHERE id = $obj_id")) {

    $stmt->execute();
    $stmt->store_result();

    $stmt->bind_result($item_id, $titel, $autor, $verlag, $standort, $signatur, $ort_jahr, $beilagen, $bemerkung, $anzahl, $isbn, $stichwort, $buch, $bildvorhanden);
    $stmt->fetch();

    return array ($item_id, $titel, $autor, $verlag, $standort, $signatur, $ort_jahr, $beilagen, $bemerkung, $anzahl, $isbn, $stichwort, $buch, $bildvorhanden);
    }

}

function set_NeuesFachDesMonats($mysqli, $monat, $jahr, $schranknummer, $fachnummer, $beschreibung){

  // BILDUPLOAD ------------------------------------------------------------------

  $target_dir = "../fachuploads/";
  $smalltarget_file = "../fachuploads/vorschau/";
  $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
  $uploadOk = 1;
  $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

  date_default_timezone_set("Europe/Berlin");
  $timestamp = time();
  $stempel = date("Y-m-d-H_i_s",$timestamp);
  $target_file = $target_dir.$stempel.".".$imageFileType;



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
  // existiert die Datei bereits - pruefen!

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
  if($imageFileType != "jpg" && $imageFileType != "JPG" && $imageFileType != "png" && $imageFileType != "PNG" && $imageFileType != "jpeg" && $imageFileType != "JPEG"
  && $imageFileType != "gif" && $imageFileType != "GIF" ) {
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

          $piclink = "$target_file";
          $sqlstmt_fach = "INSERT INTO z_fachdesmonats (monat, jahr, schranknummer, fachnummer, beschreibung, piclink) VALUES ('$monat', '$jahr', '$schranknummer', '$fachnummer', '$beschreibung', '$piclink')";


          

      } else {
          echo "Sorry, es gab beim Upload des Bildes einen Fehler.";

      }
  }
// BILD UPLOAD ENDE ------------------------------------------------------------

  //fach scheiben

  if($uploadOk == 1){
    if($fach_stmt = $mysqli->prepare($sqlstmt_fach)){
      echo "Das Fach des Monats ($schranknummer/$fachnummer) wurde erfolgreich gespeichert!";
      if(! $fach_stmt->execute()){
        echo $mysqli->error;
      }
    }
  } else {
    echo "Das Fach des Monats ($schranknummer/$fachnummer) KONNTE NICHT GESPEICHERT WERDEN!";
  }

}

function get_fachDesMonats($mysqli){

  $aktuellermonat = date("n");
  $aktuellesjahr = date("Y");

  $monate = array(1=>"Januar",
                  2=>"Februar",
                  3=>"M&auml;rz",
                  4=>"April",
                  5=>"Mai",
                  6=>"Juni",
                  7=>"Juli",
                  8=>"August",
                  9=>"September",
                  10=>"Oktober",
                  11=>"November",
                  12=>"Dezember");

  $monat_name = $monate[$aktuellermonat];

  if ($stmt = $mysqli->prepare("SELECT id, monat, jahr, schranknummer, fachnummer, beschreibung, piclink
      FROM z_fachdesmonats
      WHERE monat = $aktuellermonat AND jahr = $aktuellesjahr
      ORDER BY id DESC LIMIT 1")) {

    $stmt->execute();
    $stmt->store_result();

    $stmt->bind_result($id, $monat, $jahr, $schranknummer, $fachnummer, $beschreibung, $piclink);
    $stmt->fetch();



    echo "Das Fach des Monats $monat_name ist:<br>";

    echo"<div class=\"illustrationboxfach\"> ";
    echo "<div>";
    echo "Standort: $schranknummer/$fachnummer <br>";
    echo "<img src=\"$piclink\" class=\"illustrationfach\"></div>";
    echo "</div>";

    echo "<br>$beschreibung <br>";

  }
}
