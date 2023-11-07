<?php
  include_once '../db_connect.php';

$gesuchte_id = $_GET['id'];




  mysqli_set_charset($mysqli, 'utf8');
  if ($stmt = $mysqli->prepare("SELECT id, titel, autor, verlag, standort, signatur, ort_jahr, beilagen, bemerkung, anzahl, isbn, stichwort, buch, piclink, bewertung, controlled
      FROM VB_Inventar
      WHERE id = $gesuchte_id
      ")) {

    $stmt->execute();
    $stmt->store_result();

    $stmt->bind_result($item_id, $titel, $autor, $verlag, $standort, $signatur, $ort_jahr, $beilagen, $bemerkung, $anzahl, $isbn, $stichwort, $buch, $bildvorhanden, $bewertung, $schonkontrolliert);
    $stmt->fetch();
    }

?>

<form class="suchformular" action="../includes/set/pruef_senden.php" method="post" enctype="multipart/form-data">

  <input type="hidden" name="idname" value="<?php echo $item_id ?>" id="idname">
    <br>
  <label>Titel<br>
  <input type="text" name="titel" value="<?php echo $titel ?>" id="titel" required>
  </label>
    <br>
  <label>Standort (Regal/Fach)<br>
  <input type="text" name="standort" value="<?php echo $standort ?>" id="standort" required>
  </label>
    <br>
  <label>Autor<br>
    <input type="text" name="autor" value="<?php echo $autor ?>" id="autor">
  </label>
    <br>
  <label>Verlag<br>
    <input type="text" name="verlag" value="<?php echo $verlag ?>" id="verlag">
  </label>
    <br>
  <label>Signatur (der Bibliothek)<br>
    <input type="text" name="signatur" value="<?php echo $signatur ?>" id="signatur">
  </label>
    <br>
  <label>Ort, Jahr (mit Komma getrennt)<br>
    <input type="text" name="ort_jahr" value="<?php echo $ort_jahr ?>" id="ort_jahr">
  </label>
    <br>
  <label>Stichworte (mit Komma getrennt)<br>
    <input type="text" name="stichworte" value="<?php echo $stichwort ?>" id="stichworte">
  </label>
    <br>
  <div>
    <input class="inputfile" name="fileToUpload" id="fileToUpload" type="file" accept="image/*" capture="environment" data-multiple-caption="{count} files selected" multiple />
    <label for="fileToUpload">Objekt fotografieren <?php if ($bildvorhanden != '') {echo "<img class='hakenicon' src='../img/icons8-haken.svg'>";} ?></label>
  </div>
  <br>

  <!-- <input class="button feld" type="button" value="Nicht gefunden" onclick="window.location.href='vermisst.php<?php echo "?nummer=".$item_id ."&standort=" .$standort ; ?>'" /> -->

<hr><br>



  <span>Wie stark zutreffend sind die folgenden Kategorien? Stelle den jeweiligen Slider zwischen links (garnicht) und rechts (für absolut zutreffend) ein!</span><br><br>

<?php
// Slider reinladen

$zeile = 1;
$bewertungsliste = explode("#", $bewertung);

if (($handle = fopen("../kategorienliste.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $num = count($data);
      $bwnummer = $zeile - 1;
      $zeile++;

      for ($c=0; $c < $num; $c++) {

          echo $data[$c] . "<br />\n";


          echo "<label><input style type='range' min='0' max='100' value='". $bewertungsliste[$bwnummer]* 100 ."' class='slider' name='range_". $bwnummer ."'></label> <br />\n";
      }
  }
  fclose($handle);
}

echo "<input type='hidden' name='anzkategorien' value='" . $zeile . "'>";

 ?>
 <label>QR-Code Nummer<br>
 <input type="text" name="qrcode" value="" id="qrcode" pattern="LW-[0-9]{5}" title="LW-####" required>
 </label>
 <br>

 <button class="button feld" type="submit">Speichern</button>

</form>
