<?php
include_once 'includes/register.inc.php';
include_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Registrierung</title>
        <script type="text/JavaScript" src="js/sha512.js"></script> 
        <script type="text/JavaScript" src="js/forms.js"></script>
        <link rel="stylesheet" href="template/style.css" />
    </head>
    <body>
        <!-- Anmeldeformular für die Ausgabe, wenn die POST-Variablen nicht gesetzt sind
        oder wenn das Anmelde-Skript einen Fehler verursacht hat. -->
        <h1>Registrierung</h1>
        <?php
        if (!empty($error_msg)) {
            echo $error_msg;
        }
        ?>	
		<small>
			Benutzernamen dürfen nur Ziffern, Groß- und Kleinbuchstaben und Unterstriche enthalten.<br>
			E-Mail-Adressen müssen ein gültiges Format haben.<br>
			Passwörter müssen mindest sechs Zeichen lang sein.<br>
			Passwörter müssen enthalten
					<ul>
						<li>mindestens einen Großbuchstaben (A..Z)</li>
						<li>mindestens einen Kleinbuchstabenr (a..z)</li>
						<li>mindestens eine Ziffer (0..9)</li>
					</ul>
			Das Passwort und die Bestätigung müssen exakt übereinstimmen.<br><br>
		</small>
        <form action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>" method="post" name="registration_form">
            Benutzername: 
			<input type='text' name='username' id='username' size="40" maxlength="250"><br>
            Email: 
			<input type="text" name="email" id="email" /><br>
            Passwort: 
			<input type="password" name="password"  id="password" size="40" maxlength="250"><br>
            Passwort bestätigen: 
			<input type="password" name="confirmpwd" id="confirmpwd" size="40" maxlength="250"><br>
            <input class="button feld" type="button" value="Registrieren" 
                   onclick="return regformhash(this.form,
                                   this.form.username,
                                   this.form.email,
                                   this.form.password,
                                   this.form.confirmpwd);" /> 
        </form>
        <p>Zurück zur <a href="index.php">Startseite</a>.</p>
    </body>
</html>
