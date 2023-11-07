<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';

	sec_session_start();

	if (login_check($mysqli) == true) {
    $logged = 'in';
	} else {
		$logged = 'out';
	}
?>

<!doctype html>
<html lang="de">
<head>
	<meta charset="UTF-8">
	<meta name=description content="">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="../template/style.css">
	<link rel="shortcut icon" href="../img/favicon.png" type="image/png">
    <link rel="icon" href="../img/favicon.png" type="image/png">
	<script type='text/javascript' src='https://code.jquery.com/jquery-1.12.0.min.js'></script>
	<script type="text/JavaScript" src="../js/sha512.js"></script>
	<script type="text/JavaScript" src="../js/forms.js"></script>
	<title>Lernwerkstatt Halle | Zaiko Medienportal</title>
</head>

<body lang="de" onload="document.getElementById(box).style.opacity='1'">

<div class="wrapper">

	<?php include('../template/header.php');?>

	<?php include('../template/nav.php');?>

<?php include('../template/sidebar.php');?>

<div class="boxmainsmall">
  <div class="box inhalt wish">
		<h2>Workshopangebot des Medienteam im Sommersemester 2019</h2>

		<p>
		In der Medienspielstunde werden wir auf kreative Art und Weise verschiedene technische oder multimediale Themen entdecken. Jeden Donnerstag ab 18.00 Uhr startet unser Treffen. Alle Termine sind ohne Anmeldung und keine Vorkenntnisse sind notwendig. Folgenden Themen werden wir uns in diesem Semester zuwenden. Gerne kann man auch zu den Medienöffnungszeiten mit anderen Fragestellungen vorbei kommen.
		</p>
		<p>
		<ul>
		<li><b>Programmieren für Anfänger (4. und 11.04.)</b><br>
			Lerne Grundlagen der Programmierung kennen, sodass du schnell in der Lage sein wirst eigene kleine Spiele zu entwickeln.
		</li>
		<li><b>Dein Computer spinnt?! Schon einmal probiert neu zu starten? (25.04.)</b><br>
			Bringe deine PC Probleme mit in die Lernwerkstatt! Wir können gemeinsam versuchen diese zu lösen. Erfahre dabei wie du dir in den meisten Fällen selbst helfen kannst. Wenn möglich, dann bringe deinen Laptop mit…
		</li>
		<li><b>Löten Löten Löten!!! (2.und 9.05.)</b><br>
			Mit dem Lötkolben und etwas Zinn kann man sowohl künstlerisch Figuren gestalten, als auch elektrische Schaltungen bauen. Komm vorbei und lerne wie es geht! </li>
		<li><b>Fummeln mit LEDs (16.und 23.05.)</b><br>
			Wir bauen bunt blinkende Sachen. Lerne den Umgang mit Steckbrett, LEDs und Widerständen.
		</li>
		<li><b>Virtuelles Basteln in 3D - Denken wird anfassbar!(6./13./20.06.)</b><br>
			Mist, schon wieder abgebrochen. Baue am PC Ersatzteile für alles Mögliche oder werde kreativ und gestalte Skulpturen. Anschließend drucken wir diese an unserem 3D Drucker aus.
		</li>
		<li><b>Feinstaubsensor für die Franckeschen Stiftungen (27.06 sowie 04. und 11.07.)</b><br>
			Wir bauen gemeinsam den Sensor um die Luft zu „vermessen“. Komm vorbei und lerne wie es geht!
		</li>

		</ul>
		</p>

  </div>


</div>
  <?php include('../template/footer.php');?>

  		<!-- The Modal -->
		<div id="id01" class="modal">
			<span onclick="document.getElementById('id01').style.display='none'" class="close" title="Close Modal">&times;</span>

			<!-- Modal Content -->
			<form class="modal-content animate" action="../includes/process_login.php" method="post" name="login_form">
				<div class="imgcontainer">
					<img src="../img/lw-logoNEU.svg">
				</div>

				<div class="container">
					<!--label for="email"><b>Email</b></label>
					<input type="text" id="email"  name="email" placeholder="Email eingeben" required>

					<label for="password"><b>Password</b></label>
					<input type="password" name="password" id="password" placeholder="Password eingeben" required>

					<button class="button feld" type="submit" value="Login" onclick="formhash(this.form, this.form.password);">Login</button-->

					Email: <input type="text" name="email" />
					Password: <input type="password" name="password" id="password"/>
					<input type="button" class="button feld" value="Login" onclick="formhash(this.form, this.form.password);" />
				</div>

				<div class="container" style="background-color:#f1f1f1">
					<button class="button feld" type="button" onclick="document.getElementById('id01').style.display='none'" class="cancelbtn">Abbrechen</button>
					<p>Wenn Sie keinen Benutzernamen haben, dann <a href="../register.php">registrieren</a> Sie sich.</p>
				</div>
			</form>
		</div>

  <script>
	// Get the modal
	var modal = document.getElementById('id01');

	// When the user clicks anywhere outside of the modal, close it
	window.onclick = function(event) {
		if (event.target == modal) {
		modal.style.display = "none";
		}
	}
	</script>

	<!--script>
		$(document).ready(function(){
		alert('Achtung! Diese Seite ist im Beta Status. Es kann sein, dass nicht alle Funktionen verfügbar sind!');
		});
	</script-->

</div>


<script>

function isElementInViewport(element) {
  var rect = element.getBoundingClientRect();
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
}


var elements = document.querySelectorAll(".box");

function callbackFunc() {
  for (var i = 0; i < elements.length; i++) {
    if (isElementInViewport(elements[i])) {
      elements[i].classList.add("visible");
    }

    /* Else-Bedinung entfernen, um .visible nicht wieder zu löschen, wenn das Element den Viewport verlässt.
    else {
      elements[i].classList.remove("visible");
    }*/

  }
}

window.addEventListener("load", callbackFunc);
window.addEventListener("scroll", callbackFunc);

</script>

<div id="cookiedingsbums"><div>
  <span>Ja, auch diese Webseite verwendet Cookies. </span>
  <a href="https://www.uni-halle.de/datenschutzerklaerung/">Hier erfahrt ihr alles zum Datenschutz</a></div>
 <span id="cookiedingsbumsCloser" onclick="document.cookie = 'hidecookiedingsbums=1;path=/';jQuery('#cookiedingsbums').slideUp()">&#10006;</span>
</div>

<script>
 if(document.cookie.indexOf('hidecookiedingsbums=1') != -1){
 jQuery('#cookiedingsbums').hide();
 }
 else{
 jQuery('#cookiedingsbums').prependTo('body');
 jQuery('#cookiedingsbumsCloser').show();
 }
</script>


</body>
</html>
