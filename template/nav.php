<?php if (login_check($mysqli) == true) : ?>
<div class="boxnav nav">
    <a class="navlink" href="index.php">Suche</a>
<?php if (htmlentities($_SESSION['username']) != 'Melli') : ?>
    <a class="navlink" href="settings.php#start">Einstellungen</a>
<?php endif; ?>
    <a class="navlink" href="control.php">Objekt neu einsortieren</a>
</div>
<?php endif; ?>
