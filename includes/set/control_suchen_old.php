<?php
  include_once '../db_connect.php';

$gesuchtertitel = $_GET['titel'];

  mysqli_set_charset($mysqli, 'utf8');
  if ($stmt = $mysqli->prepare("SELECT id, titel, autor, verlag, standort, signatur, ort_jahr, beilagen, bemerkung, anzahl, isbn, stichwort, buch, piclink, bewertung
      FROM VB_Inventar
      WHERE titel LIKE '%$gesuchtertitel%' AND controlled = 0
      ORDER BY RAND()
      LIMIT 1")) {

    $stmt->execute();
    $stmt->store_result();

    $stmt->bind_result($item_id, $titel, $autor, $verlag, $standort, $signatur, $ort_jahr, $beilagen, $bemerkung, $anzahl, $isbn, $stichwort, $buch, $bildvorhanden, $bewertung);
    $stmt->fetch();

  }
?>

<form class="suchformular" action="../includes/set/control_senden.php" method="post" enctype="multipart/form-data">

  <input type="hidden" name="idname" value="<?php echo $item_id ?>" id="idname" required>
    <br>
  <label>Titel<br>
  <input type="text" name="titel" value="<?php echo $titel ?>" id="titel" required>
  </label>
    <br>
  <label>Standort (Regal/Fach) [Gebe den korrekten Standort ein!]<br>
  <input type="text" name="standort" value="<?php echo $standort ?>" id="standort" required>
  </label>
    <br>
  <label>Autor<br>
      <input type="text" name="autor" value="<?php echo $autor ?>" id="autor" readonly>
  </label>
      <br>
  <label>Verlag<br>
    <input type="text" name="verlag" value="<?php echo $verlag ?>" id="verlag" readonly>
  </label>
      <br>
  <label>Ort, Jahr<br>
    <input type="text" name="ort_jahr" value="<?php echo $ort_jahr ?>" id="ort_jahr">
  </label>

  <br>

  <button class="button feld" type="submit">Speichern</button>


</form>
