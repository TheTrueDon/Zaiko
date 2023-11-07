<div class="box boxfooter wish">
<p>Lernwerkstatt Erziehungswissenschaften Franckeplatz 1, Haus 31 (Raum 020) 06110 Halle/Saale </p>
<p>Impressum | <a href="https://www.uni-halle.de/datenschutzerklaerung/" target="_blank">Datenschutzerklärung</a> | <a href="zaikoinfo.php">Über Zaiko</a></p>
<?php if (login_check($mysqli) == true) : ?>
  <p>Hallo <?php echo htmlentities($_SESSION['username']); ?>!</p>
  <div class="log">
    <!-- Button to logout -->
      <a href="../includes/logout.php"><button class="button logbut" >Logout</button></a>
  </div>
<?php else : ?>
  <div class="log">
    <!-- Button to open the modal login form -->
      <button class="button logbut" onclick="document.getElementById('id01').style.display='block'">Login</button>
  </div>
<?php endif; ?>
</div>
