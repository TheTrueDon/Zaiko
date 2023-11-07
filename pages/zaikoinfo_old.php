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
  <div class="box boxhead">
		<img src="../img/lw-logoNEU.svg">
		<h1>Medienportal <span class="strich">-</span> Zaiko</h1>
  </div>

  <div class="box boxinhalta inhalt wish">
	<h2>Was ist &bdquo;zaiko&ldquo;</h2>

	<p>Das Medienportal &bdquo;zaiko&ldquo; ermöglicht die <b>Suche von Materialen</b> und orientiert sich dabei an dem Prinzip des <i>entdeckenden Lernen</I>. </p>
	<p>Es geht hierbei nicht um eine Suchmaschine, die ein bestimmtes, konkretes Suchobjekt und dessen Standort liefert, das auch, aber viel mehr explorativ aufzeigt, welche Möglichkeiten sich mit dem hinterlegten Material ergeben. Dies fördert die <b>Eigeninitiative des Suchenden</b> und gibt Anregungen zum kreativen Weiterforschen. Das Tool soll für Sammlungen geeignet sein, die neben Büchern auch Modelle und darüberhinausgehende Exponate beinhalten. </p>
	<p>&copy; &bdquo;zaiko&ldquo; ist ein Projekt von Florian Johnke-Liese und Constantin Beyer.</p>
	</div>
	<div class="box boxinhaltb inhalt wish">
	<p>Es spiegelt den Suchprozess wieder, der auch analog und offline vor dem Regal geschehen kann. Dabei sind meist Dinge, die ähnlichen Kategorien zugeordnet werden können in einem Regalfach oder stehen relativ nah beieinander. Im echten Leben sind die Dinge aber leider lokal immer nur einer Kategorie zuordbar. Genau der, die am Regalbrett steht. &bdquo;zaiko&ldquo; ermöglicht es nun mehrere Beziehungen mehrdimensional zwischen allen Gegenständen des Inventars herzustellen. Dabei lernt das System durch den Suchenden die Ergebnisse und Relevanzen immer genauer einzuschätzen. Das Ergebnis wird damit immer besser und orientiert sich immer mehr an dem, was gefunden werden soll!</p>
	<p>Hinter &bdquo;zaiko&ldquo; steckt neben der Suche auch die Möglichkeit der Verwaltung einer nutzerorientierten Ausleihe und der Organisation eines so genannten „virtuellen Medienpass“, der Nutzer autorisiert bestimmte Inventar-Objekte vor Ort zu nutzen, oder zu entleihen.

  </div>


  <div class="box boxinhaltc inhalt wish">
	<h2>Wie funktioniert &bdquo;zaiko&ldquo; (Suche in zwei Phasen)</h2>
	<h3>Phase I</h3>
	<p>Jemand gibt einen Suchbegriff ein und das ist genau der Name eines Gegenstands im Werkstattinventar. Dieser wird dann auch angezeigt.</p>
	<p>Das soll ermöglichen, unabhängig von einer Problemstellung oder anderem Kontext, einfach ein Objekt anzuzeigen, von dem bekannt ist, dass es im Inventar vorhanden ist.</p>
	<p>Der Sucherfolg von Phase I ist also genau dann gesichert, falls man ein Bestimmtes Objekt im Inventar sucht.</p>
	<p><i>Da dies aber nicht die primäre Vorgehensweise in der Lernwerkstatt ist, wird zusätzlich mit Phase II fortgefahrenbeziehungsweise bei nicht finden direkt die Phase II durchgeführt.</i></p>
</div>
<div class="box boxinhaltd inhalt">
	<h3>Phase II</h3>
	<p>Diese Phase stellt die eigentliche &bdquo;zaiko&ldquo;-stärke dar. Entgegen vieler textbasierter Suchsysteme, deren Ziel ist, ein bestimmtes Objekt zu finden, in dessen Titel oder Beschreibungen diese Worte vorkommen findet hier eine semantische Suche statt.</p>
	<img src="../img/Skizze (2).png">
	<p>Das System bewertet in n Kategorien (Dimensionen) alle Objekte im Inventar. Hierbei ist n eine beliebige Anzahl. In der Lernwerkstatt zum Beispiel sind das fast 100 gut überlegte Dimensionen.</p>
	<p>Jedes Objekt wird dann als Punkt in einem n-dimensionalen Raum betrachtet.
		<img src="../img/Skizze (1).png">
		Gibt der Benutzer nun eine Suchanfrage ein, so werden seine Suchbegriffe über eine Routine ebenfalls in einen Punkt umgewandelt und eine Metrik liefert eine Bewertung für die Gegenstände im Inventar in Abhängigkeit zu dieser Suchanfrage. Die Ergebnisse werden dann nach Passgenauigkeit geordnet ausgegeben.</p>
</div>
<div class="box boxinhalte inhalt wish">
	<h3>Wie lernt &bdquo;zaiko&ldquo;?</h3>
	<p>Das System Lernt bei jeder Suche hinzu in dem es entweder eine per Hand gegebene (Schieberegler) Einordnung der Qualität der Suchergebnisse verarbeitet oder es überarbeitet die angesehenen Suchergebnisse, Merklisteneinträge und Suchbegriffe nach jeder Suche.</p>
	<p>&bdquo;zaiko&ldquo; gibt so zwar nicht bei jeder Suche deterministisch die gleichen Suchergebnisse aber liefert hingegen die Objekte, die nach Nutzerverhalten am passendsten sind. Die Nutzer entscheiden letztlich also vollkommen unabhängig vom Anbieter über die Suchergebnisse und ihre Güte.</p>
</div>
<div class="box boxinhaltf inhalt wish">
	<h3>Wo kann &bdquo;zaiko&ldquo; eingesetzt werden?</h3>
	<p>Optimal ist &bdquo;zaiko&ldquo; einsetzbar in der Lernwerkstättenarbeit, da es in der Lernwerkstatt der Erziehungswissenschaften an der Martin-Luther-Universität in Halle getestet und als erstes eingesetzt werden soll. Darüber hinaus sind Einsatzbereiche besonders in pädagogischen Einrichtungen denkbar und geeignet.</p>
	<p>Da aber die Dimensionen in Anzahl und Inhalt frei wählbar sind, und diese auch prinzipiell bis auf eine optionale Initialisierung kontextfrei sind eröffnen sich iverse Anwendungsfälle in denen nicht die Beschreibung, sondern die auf den Nutzer und sein Problem passenden Suchergebnisse ausgegeben werden.</p>
</div>
<div class="box boxinhaltg inhalt">
	<h3>Aktueller Entwicklungsstand</h3>
	<img src="../img/Skizze (3).png">

	<p>Das Medienportal ist derzeit in einem Betastatus in der bereits eine intelligente Suche inkl. Bewertung der Objekte in Bezug auf das Suchwort möglich ist.</p>
	<p>Der nächste Schritt ist das Einpflegen des Bestands und eine Initialisierung des Inventars um das initiale Lernen zu verkürzen. Anschließend beginnt die Live-Testphase</p>
	<p>Folgende Funktionen sollen in &bdquo;zaiko&ldquo; möglich sein:
		<ul>
			<li>Suche von passendem Inventar zu Themen und Anzeige interessanter Objekte</li>
			<li>Lernendes Verhalten des Systems selbst</li>
			<li>Wiedergabe Kategorie orientierter Inventarlisten – sogenanntes „Stöbern“</li>
			<li>Finden von gesuchtem Inventar-Objekten</li>
			<li>QR-Code gestützte Identifikation von Objekten</li>
			<li>Nutzerverwaltung und Strukturierung des Ausleihverfahrens</li>
			<li>Verwalten des Inventars</li>
		</ul>

	</p>

	<a href="http://lernwerkstatt.paedagogik.uni-halle.de/zaikotest/">Aktuelle Testversion</a>
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
