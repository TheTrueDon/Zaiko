<?php
	include_once '../includes/db_connect.php';
	include_once '../includes/functions.php';

setlocale(LC_TIME, "de_DE");

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
	<h2>Materialsuche</h2>
	<div class="suchfeld">
	<form class="suchformular" action="index.php" method="get">
		<input type="text" placeholder="Suche in Zaiko" name="search_string" required>
		<button class="button" type="submit">Suchen</button>
	</form>
	</div>
	<?php
		if(isset($_GET['search_string'])) {

			$results = phase_I_search_temp($mysqli, $_GET['search_string']);
			$meinsuchwort = $_GET['search_string'];

			echo "<div class=\"suchergebnisse\">";

			echo "<h2>Suchergebnisse:</h2>";
			echo "<h3 style=\"margin-bottom: 20px; margin-top:-10px; font-style:italic;\">Suchwort: $meinsuchwort </h3>";

			for ($i=1;$i<sizeof($results); $i++) {
				show_suchergebnis($results[$i][0], $results[$i][1], $results[$i][2], $results[$i][3], $results[$i][4]);
			}
			echo "</div>";

		}
	?>
  </div>

	<div class="abstand"></div>

  <div class="box boxmaterial inhalt">
	<h2>Fach des Monats</h2>
		<br>
		<?php if (login_check($mysqli) == true) : ?>
			<form method="post" enctype="multipart/form-data">

			Das neue Fach des Monats

		<select name="monat">
			<?php

			$monat = date('m');
			$nr = "\n";
			$monat_name['01'] = 'Januar';
			$monat_name['02'] = 'Februar';
			$monat_name['03'] = 'März';
			$monat_name['04'] = 'April';
			$monat_name['05'] = 'Mai';
			$monat_name['06'] = 'Juni';
			$monat_name['07'] = 'Juli';
			$monat_name['08'] = 'August';
			$monat_name['09'] = 'September';
			$monat_name['10'] = 'Oktober';
			$monat_name['11'] = 'November';
			$monat_name['12'] = 'Dezember';

			for($i=1;$i<=12;$i++) {
    		if($i <= 9) {
        	$value[$i] = '0'.$i;
    		} else {
        	$value[$i] = $i;
    		}

    			if($value[$i] == $monat+1) {
        		$selected[$i] = ' selected';
    			} else {
        		$selected[$i] = '';
    			}

    	echo '<option value="'.$value[$i].'"'.$selected[$i].'>'.$monat_name[$value[$i]].'</option>'.$nr;
			}

		?>
			</select>

			<select name = "jahr">
				<option value="<?php  echo date("Y");?>" >   <?php  echo date("Y");?>    </option>
				<option value="<?php  echo date("Y")+1; ?>"> <?php  echo date("Y")+1; ?> </option>
			</select>

			ist:<br>

				<?php
				echo "	<select name = \"schranknummer\">";
					for ($i = 1; $i <= 30; $i++) {
						echo "<option value=\" $i \"> $i </option> ";
					}
				echo "</select>";

				echo "/";

				echo "	<select name = \"fachnummer\">";
					for ($i = 1; $i <= 6; $i++) {
						echo "<option value=\" $i \"> $i </option> ";
					}
				echo "</select>";
				 ?>

				 <br><br>

				 <div>
			     <input class="inputfile" name="fileToUpload" id="fileToUpload" type="file" accept="image/*" capture="environment" data-multiple-caption="{count} files selected" multiple />
			     <label for="fileToUpload">Fachbild hinzufügen</label>
			   </div>
 <br>

			<label>Beschreibung<br>
			<input type="text" name="beschreibung" value="" id="beschreibung" required>
			</label>

			 <input class="button" type='submit' name='submit' value='Speichern'>
			</form>

			<?php
				if (!empty($_POST)){
						set_NeuesFachDesMonats($mysqli, $_POST["monat"], $_POST["jahr"], $_POST["schranknummer"], $_POST["fachnummer"], $_POST["beschreibung"], $_POST["fileToUpload"]);
				}

			 ?>

		<?php else : ?>


				<?php get_fachDesMonats($mysqli); ?>



		<?php endif; ?>



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
