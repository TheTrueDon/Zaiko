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

<div class="box boxmain inhalt">
<?php if (login_check($mysqli) == true) : ?>
	<h1 id="start">Inventarkorrektur</h1>


	<p>Einen Titel als Suchbegriff eingeben:</p>
	<form class="suchformular" action="control.php" method="get">
		<input type="text" placeholder="Titel suchen" name="titel" required>
		<button class="button" type="submit">Suchen</button>
	</form>

<?php
	if(isset($_GET['titel'])) {
		include('../includes/set/control_suchen.php');
	}

	if(isset($_GET['id'])) {
		include('../includes/set/objekt_aendern.php');
	}
?>


<?php else : ?>
	<p>Du bist leider nicht eingeloggt. Bitte melde dich für diese Seite an!</p>

<?php endif; ?>


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
		var acc = document.getElementsByClassName("accordion");
		var i;

		for (i = 0; i < acc.length; i++) {
		  acc[i].addEventListener("click", function() {
		    this.classList.toggle("active");
		    var panel = this.nextElementSibling;
		    if (panel.style.maxHeight){
		      panel.style.maxHeight = null;
		    } else {
		      panel.style.maxHeight = panel.scrollHeight + "px";
		    }
		  });
		}
		</script>

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
