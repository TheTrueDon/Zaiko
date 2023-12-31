<?php
include_once 'db_connect.php';
include_once 'psl-config.php';
 
$error_msg = "";
 
if (isset($_POST['username'], $_POST['email'], $_POST['p'])) {
    // Bereinige und überprüfe die Daten
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // keine gültige E-Mail
        $error_msg .= '<p class="error">The email address you entered is not valid</p>';
    }
 
    $password = filter_input(INPUT_POST, 'p', FILTER_SANITIZE_STRING);
    if (strlen($password) != 128) {
        // Das gehashte Passwort sollte 128 Zeichen lang sein.
        // Wenn nicht, dann ist etwas sehr seltsames passiert
        $error_msg .= '<p class="error">Invalid password configuration.</p>';
    }
 
    // Benutzername und Passwort wurde auf der Benutzer-Seite schon überprüft.
    // Das sollte genügen, denn niemand hat einen Vorteil, wenn diese Regeln   
    // verletzt werden.
    //
 
    $prep_stmt = "SELECT id FROM z_user WHERE email = ? LIMIT 1";
    $stmt = $mysqli->prepare($prep_stmt);
 
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
 
        if ($stmt->num_rows == 1) {
            // Ein Benutzer mit dieser E-Mail-Adresse existiert schon
            $error_msg .= '<p class="error">A user with this email address already exists.</p>';
        }
    } else {
        $error_msg .= '<p class="error">Database error</p>';
    }
 
    // Noch zu tun: 
    // Wir müssen uns noch um den Fall kümmern, wo der Benutzer keine
    // Berechtigung für die Anmeldung hat indem wir überprüfen welche Art 
    // von Benutzer versucht diese Operation durchzuführen.
 
    if (empty($error_msg)) {
        // Erstelle ein zufälliges Salt
        $random_salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE));
 
        // Erstelle saltet Passwort 
        $password = hash('sha512', $password . $random_salt);
 
        // Trage den neuen Benutzer in die Datenbank ein 
        if ($insert_stmt = $mysqli->prepare("INSERT INTO z_user (username, email, password, salt) VALUES (?, ?, ?, ?)")) {
			
            $insert_stmt->bind_param('ssss', $username, $email, $password, $random_salt);
            // Führe die vorbereitete Anfrage aus.
            if (! $insert_stmt->execute()) {
				echo $mysqli->error;
                //header('Location: ../error.php?err=Registration failure: INSERT');
            }
        }
        //header('Location: ./register_success.php');
    }
}
