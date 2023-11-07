<?php
include_once 'psl-config.php';   // Da functions.php nicht included ist
$mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);
$mysqli->options(ATTR_EMULATE_PREPARES,true); 
