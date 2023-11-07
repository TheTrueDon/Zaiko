<form class="suchformular" action="../includes/set/new_senden.php" method="post" enctype="multipart/form-data">
  <br>
  Handelt es sich bei dem Objekt um ein Buch?
  <input type="checkbox" name="buch" value="Yes" /> JA <br><br>
  <label>Titel<br>
  <input type="text" name="titel" value="" id="titel" required>
  </label>
  <br>
  <label>Autor<br>
  <input type="text" name="autor" value="" id="autor">
  </label>
  <br>
  <label>Verlag<br>
  <input type="text" name="verlag" value="" id="verlag">
  </label>
  <br>
  <label>Standort (Regal/Fach)<br>
  <input type="text" name="standort" value="" id="standort" required>
  </label>
  <br>
  <label>Signatur (der Bibliothek)<br>
  <input type="text" name="signatur" value="" id="signatur">
  </label>
  <br>
  <label>Ort, Jahr (mit Komma getrennt)<br>
  <input type="text" name="ort_jahr" value="" id="ort_jahr">
  </label>
  <br>
  <label>Stichworte (mit Komma getrennt)<br>
  <input type="text" name="stichworte" value="" id="stichworte">
  </label>
  <br>
  <label>Beilagen<br>
  <input type="text" name="beilagen" value="" id="beilagen">
  </label>
  <br>
  <label>Bemerkungen<br>
  <input type="text" name="bemerkung" value="" id="bemerkung">
  </label>
  <br>
  <label>Anzahl<br>
  <input type="text" name="anzahl" value="1" id="anzahl">
  </label>
  <br>
  <label>ISBN<br>
  <input type="text" name="isbn" value="" id="isbn">
  </label>
  <br>


  <div>
    <input class="inputfile" name="fileToUpload" id="fileToUpload" type="file" accept="image/*" capture="environment" data-multiple-caption="{count} files selected" multiple />
    <label for="fileToUpload">Objekt fotografieren </label>
  </div>
  <br>

<hr><br>

  <span>Wie stark zutreffend sind die folgenden Kategorien? Stelle den jeweiligen Slider zwischen links (garnicht) und rechts (für absolut zutreffend) ein!</span><br><br>

<?php
// Slider reinladen

$zeile = 1;

if (($handle = fopen("../kategorienliste.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      $num = count($data);
      $bwnummer = $zeile - 1;
      $zeile++;

      for ($c=0; $c < $num; $c++) {

          echo $data[$c] . "<br />\n";


          echo "<label><input style type='range' min='0' max='100' value='0' class='slider' name='range_". $bwnummer ."'></label> <br />\n";
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
