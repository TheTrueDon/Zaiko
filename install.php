<?php
require __DIR__.'/bootstrap.php';
if(is_file(__DIR__.'/config.php')){http_response_code(403);exit('Bereits installiert. Bitte install.php auf dem Server entfernen.');}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  checkcsrf();$host=trim($_POST['host']??'localhost');$port=(int)($_POST['port']??3306);$name=trim($_POST['database']??'');$user=trim($_POST['dbuser']??'');$password=(string)($_POST['dbpass']??'');$adminuser=trim($_POST['adminuser']??'admin');$adminname=trim($_POST['adminname']??'Administrator');$adminpass=(string)($_POST['adminpass']??'');$prefix=trim((string)($_POST['prefix']??'zaiko_'));
  if(!preg_match('/^[a-zA-Z0-9_.-]{1,100}$/',$name)||!preg_match('/^[a-zA-Z0-9_.-]{1,255}$/',$host)||$port<1||$port>65535)throw new RuntimeException('Ungültige Datenbankverbindung.');
  if(!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,39}$/D',$prefix))throw new RuntimeException('Tabellenpräfix: 1–40 Zeichen, beginnend mit einem Buchstaben; erlaubt sind Buchstaben, Ziffern und Unterstriche.');
  if(strlen($adminpass)<12||mb_strlen($adminuser)<3)throw new RuntimeException('Administratorname mindestens 3 Zeichen, Passwort mindestens 12 Zeichen.');
  $pdo=new PDO('mysql:host='.$host.';port='.$port.';dbname='.$name.';charset=utf8mb4',$user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
  // Bereits vorhandene Tabellen unter diesem Präfix nie überschreiben oder teilweise benutzen.
  $tableNames=['users','settings','locations','dimensions','items','item_vectors','keywords','keyword_vectors','associations','searches','interactions','loans','loan_items','audit_log'];
  $check=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
  foreach($tableNames as $table){$check->execute([$name,$prefix.$table]);if((int)$check->fetchColumn()>0)throw new RuntimeException('Tabelle '.$prefix.$table.' existiert bereits. Bitte anderen Präfix verwenden oder bestehende Installation sichern.');}
  foreach(array_filter(array_map('trim',explode(';',file_get_contents(__DIR__.'/schema.sql')))) as $statement)$pdo->exec(prefixed_sql($statement,$prefix));
  $stmt=$pdo->prepare("INSERT INTO `{$prefix}users` (username,name,password_hash,role) VALUES (?,?,?,'admin')");$stmt->execute([$adminuser,$adminname,password_hash($adminpass,PASSWORD_DEFAULT)]);
  $cfg="<?php\n";
  foreach(['DB_HOST'=>$host,'DB_PORT'=>(string)$port,'DB_NAME'=>$name,'DB_USER'=>$user,'DB_PASS'=>$password,'DB_PREFIX'=>$prefix] as $key=>$value)$cfg.='define('.var_export($key,true).','.var_export($value,true).");\n";
  $cfg.="// Optional: API-Schlüssel besser als Umgebungsvariable OPENAI_API_KEY setzen.\n";
  if(file_put_contents(__DIR__.'/config.php',$cfg,LOCK_EX)===false)throw new RuntimeException('config.php konnte nicht geschrieben werden. Dateirechte prüfen.');
  @chmod(__DIR__.'/config.php',0600);header('Location: index.php?p=login&installed=1');exit;
 }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Installation – Lernwerkstatt</title><link rel="stylesheet" href="style.css"><main class="container narrow"><h1>Lernwerkstatt installieren</h1><p>Benötigt PHP 8.1+, PDO MySQL, mbstring und eine leere MySQL-/MariaDB-Datenbank.</p><?php if($error):?><div class="alert"><?=htmlspecialchars($error)?></div><?php endif;?><form method="post" class="card form"><?=fieldcsrf()?><h2>Datenbank</h2><label>Host<input name="host" value="localhost" required></label><label>Port<input type="number" name="port" value="3306" required></label><label>Datenbankname<input name="database" required></label><label>Datenbankbenutzer<input name="dbuser" required></label><label>Datenbankpasswort<input type="password" name="dbpass"></label><label>Tabellenpräfix<input name="prefix" value="<?=htmlspecialchars((string)($_POST['prefix']??'zaiko_'),ENT_QUOTES,'UTF-8')?>" pattern="[A-Za-z][A-Za-z0-9_]*" maxlength="40" required></label><p class="muted">Beispiel: zaiko_ → zaiko_users, zaiko_items usw. Vorhandene Tabellen mit diesem Präfix werden nicht überschrieben.</p><h2>Administrator</h2><label>Benutzername<input name="adminuser" value="admin" required></label><label>Anzeigename<input name="adminname" value="Administrator" required></label><label>Passwort (mindestens 12 Zeichen)<input name="adminpass" type="password" minlength="12" required></label><button>Installieren</button></form></main></html>
