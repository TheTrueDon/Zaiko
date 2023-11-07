<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';

	sec_session_start();

	if (login_check($mysqli) == true) {
    $logged = 'in';
	} else {
		$logged = 'out';
	}
	  mysqli_set_charset($mysqli, 'utf8');
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
  <div class="box boxsuche inhalt">

	<?php
		if(isset($_GET['id'])) {

			$meineid = $_GET['id'];
			list ($item_id, $titel, $autor, $verlag, $standort, $signatur, $ort_jahr, $beilagen, $bemerkung, $anzahl, $isbn, $stichwort, $buch, $bildvorhanden) = get_objfromid($mysqli, $meineid);

			echo "<h2>$titel </h2>";
			echo "<div class=\"maxansichtbox\">";
			if ($bildvorhanden != ''){
			echo "<div class=\"illustrationbox col\">";
			echo "<img class=\"illustrationgross\" src =\" " . substr($bildvorhanden, 3) . "\"> ";
			echo "</div>";
			}
			echo "<div class=\"maxobjboxbeschreibung col\">";
			if ($autor != '') echo "Autor: $autor <br>";
			if ($verlag != '') echo "Verlag: $verlag<br>";
			if ($standort != '') echo "Standort: $standort<br>"; else echo "Standort: Kein Standort vorhanden!<br>";
			if ($signatur != 0) echo "Aufgeklebte Signatur der Bibliothek: $signatur<br>";
			if ($beilagen != '') echo "Vorhandene Beilagen: <br>$beilagen<br><br>";
			if ($anzahl != '') echo "Anzahl im Regal: $anzahl<br>";
			if ($isbn != '') echo "ISBN: $isbn<br>";
			if ($buch != 1) echo "<br><br><span style=\"font-style: italic\">Es handelt sich bei dem Objekt um <b>kein</b> Buch!</span>";

			echo "</div>";
			echo "</div>";


		}
	?>
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

    /* Else-Bedinung entfernen, um .visible nicht wieder zu löschen, wenn das Element den Viewport verlässt. */
    else {
      elements[i].classList.remove("visible");
    }

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
