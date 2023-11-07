<?php
  include_once '../db_connect.php';

$gesuchtertitel = $_GET['titel'];

$suchwoerterarray = searchstring_preparing($mysqli, $gesuchtertitel);
$wherebegriff = implode('%\' OR LOWER(titel) LIKE \'%',$suchwoerterarray);
$wherebegriff = "LOWER(titel) LIKE '%" . $wherebegriff . "%'";

echo "$wherebegriff";

  mysqli_set_charset($mysqli, 'utf8');
  if ($stmt = $mysqli->prepare("SELECT id, titel, autor, verlag, standort, signatur, ort_jahr, beilagen, bemerkung, anzahl, isbn, stichwort, buch, piclink, bewertung
      FROM VB_Inventar
      WHERE $wherebegriff AND controlled = 0
      ORDER BY titel ASC
      ")) {

    $stmt->execute();
    $stmt->store_result();

    echo "<h2>Suchergebnisse:</h2>";
    echo "<h3 style=\"margin-bottom: 20px; margin-top:-10px; font-style:italic;\">Suchwort: $gesuchtertitel </h3>";

    $stmt->bind_result($item_id, $titel, $autor, $verlag, $standort, $signatur, $ort_jahr, $beilagen, $bemerkung, $anzahl, $isbn, $stichwort, $buch, $bildvorhanden, $bewertung);

		while($stmt->fetch()) {
			echo "<a href=\"control.php?id=$item_id\">   $titel </a><br><hr>";
		}
  }
  echo $mysqli -> error;

?>
