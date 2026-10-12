<?php
require __DIR__.'/bootstrap.php';require __DIR__.'/ai.php';
$page=(string)($_GET['p']??'search');$action=(string)($_POST['action']??'');$error='';
if(active_user()){
 $fresh=one('SELECT id,username,name,role,active FROM users WHERE id=?',[active_user()['id']]);
 if(!$fresh||!$fresh['active']){unset($_SESSION['user']);}else{$_SESSION['user']=$fresh;}
}
try{
 if($_SERVER['REQUEST_METHOD']==='POST'){
  checkcsrf();
  if($action==='login'){
   $u=one('SELECT * FROM users WHERE username=?',[trim($_POST['username']??'')]);
   if(!$u||!$u['active']||!password_verify((string)($_POST['password']??''),$u['password_hash']))throw new RuntimeException('Ungültiger Benutzername oder Passwort.');
   session_regenerate_id(true);$_SESSION['user']=array_intersect_key($u,array_flip(['id','username','name','role','active']));redirect('search');
  }
  if($action==='logout'){$_SESSION=[];session_regenerate_id(true);redirect('login');}
  require_login();
  if($action==='change_password'){
   $u=one('SELECT password_hash FROM users WHERE id=?',[active_user()['id']]);
   if(!password_verify((string)($_POST['old_password']??''),$u['password_hash']))throw new RuntimeException('Altes Passwort falsch.');
   $p=(string)($_POST['new_password']??'');if(strlen($p)<12)throw new RuntimeException('Neues Passwort: mindestens 12 Zeichen.');
   sql('UPDATE users SET password_hash=? WHERE id=?',[password_hash($p,PASSWORD_DEFAULT),active_user()['id']]);flash('Passwort geändert.');redirect('account');
  }
  if($action==='item_save'){
   editor();$id=max(0,(int)($_POST['id']??0));$title=trim((string)($_POST['title']??''));if(!$title)throw new RuntimeException('Titel fehlt.');
   $data=[$title,trim((string)($_POST['description']??'')),trim((string)($_POST['tags']??'')),trim((string)($_POST['category']??'')),($_POST['location_id']??'')!==''?(int)$_POST['location_id']:null,max(0,(int)($_POST['quantity']??1))];
   db()->beginTransaction();
   if($id){$open=(int)(one('SELECT COALESCE(SUM(qty-returned_qty),0) n FROM loan_items WHERE item_id=?',[$id])['n']??0);if($data[5]<$open)throw new RuntimeException('Bestand darf nicht unter die Anzahl offener Ausleihen sinken.');sql('UPDATE items SET title=?,description=?,tags=?,category=?,location_id=?,quantity=? WHERE id=?',[...$data,$id]);if(!one('SELECT id FROM items WHERE id=?',[$id]))throw new RuntimeException('Objekt nicht gefunden.');}
   else{sql('INSERT INTO items (title,description,tags,category,location_id,quantity,created_by) VALUES (?,?,?,?,?,?,?)',[...$data,active_user()['id']]);$id=(int)db()->lastInsertId();}
   foreach(dims() as $d){$dim=(int)$d['id'];$v=clamp($_POST['val'][$dim]??0.5);$conf=clamp($_POST['conf'][$dim]??0);$ap=isset($_POST['app'][$dim])?1:0;
    sql('INSERT INTO item_vectors (item_id,dimension_id,base_value,learned_value,confidence,applicable) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE base_value=VALUES(base_value),learned_value=VALUES(learned_value),confidence=VALUES(confidence),applicable=VALUES(applicable)',[$id,$dim,$v,$v,$conf,$ap]);
   }
   db()->commit();audit('save','item',$id);flash('Objekt gespeichert.');redirect('item',['id'=>$id]);
  }
  if($action==='item_delete'){
   admin();$id=(int)$_POST['id'];$row=one('SELECT image_path FROM items WHERE id=?',[$id]);sql("DELETE FROM associations WHERE (source_type='item' AND source_id=?) OR (target_type='item' AND target_id=?)",[$id,$id]);sql('DELETE FROM items WHERE id=?',[$id]);if($row&&!empty($row['image_path']))@unlink(__DIR__.'/'.$row['image_path']);audit('delete','item',$id);flash('Objekt gelöscht.');redirect('items');
  }
  if($action==='upload_image'){
   editor();$id=(int)$_POST['id'];if(!one('SELECT id FROM items WHERE id=?',[$id]))throw new RuntimeException('Objekt existiert nicht.');
   $f=$_FILES['image']??null;if(!$f||$f['error']!==UPLOAD_ERR_OK||$f['size']>5*1024*1024)throw new RuntimeException('Bild fehlt oder ist größer als 5 MB.');
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;if(!$ext)throw new RuntimeException('Nur JPEG, PNG und WebP erlaubt.');
   if(!@getimagesize($f['tmp_name']))throw new RuntimeException('Ungültiges Bild.');$path='uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
   if(!move_uploaded_file($f['tmp_name'],__DIR__.'/'.$path))throw new RuntimeException('Upload fehlgeschlagen.');
   $old=one('SELECT image_path FROM items WHERE id=?',[$id]);sql('UPDATE items SET image_path=? WHERE id=?',[$path,$id]);if(!empty($old['image_path']))@unlink(__DIR__.'/'.$old['image_path']);audit('image','item',$id);flash('Bild hochgeladen.');redirect('item',['id'=>$id]);
  }
  if($action==='remove_image'){
   editor();$id=(int)$_POST['id'];$old=one('SELECT image_path FROM items WHERE id=?',[$id]);sql('UPDATE items SET image_path=NULL WHERE id=?',[$id]);if(!empty($old['image_path']))@unlink(__DIR__.'/'.$old['image_path']);redirect('item',['id'=>$id]);
  }
  if($action==='dimension_save'){
   admin();$id=(int)($_POST['id']??0);$data=[trim((string)$_POST['name']),trim((string)($_POST['description']??'')),trim((string)($_POST['low_label']??'')),trim((string)($_POST['high_label']??'')),max(0.01,min(100,(float)($_POST['weight']??1))),isset($_POST['enabled'])?1:0];if(!$data[0])throw new RuntimeException('Dimensionsname fehlt.');
   if($id)sql('UPDATE dimensions SET name=?,description=?,low_label=?,high_label=?,weight=?,enabled=? WHERE id=?',[...$data,$id]);else{sql('INSERT INTO dimensions (name,description,low_label,high_label,weight,enabled) VALUES (?,?,?,?,?,?)',$data);$id=(int)db()->lastInsertId();}
   audit('save','dimension',$id);flash('Dimension gespeichert.');redirect('dimensions');
  }
  if($action==='dimension_delete'){
   admin();$id=(int)$_POST['id'];sql('DELETE FROM dimensions WHERE id=?',[$id]);audit('delete','dimension',$id);flash('Dimension gelöscht und ihre Werte entfernt.');redirect('dimensions');
  }
  if($action==='location_save'){
   admin();$id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));if(!$name)throw new RuntimeException('Lagerortname fehlt.');$parent=(int)($_POST['parent_id']??0);if($parent===$id&&$id)throw new RuntimeException('Ein Lagerort kann sich nicht selbst enthalten.');$data=[$name,$parent?:null,trim((string)($_POST['description']??''))];
   if($id)sql('UPDATE locations SET name=?,parent_id=?,description=? WHERE id=?',[...$data,$id]);else{sql('INSERT INTO locations (name,parent_id,description) VALUES (?,?,?)',$data);$id=(int)db()->lastInsertId();}
   audit('save','location',$id);flash('Lagerort gespeichert.');redirect('locations');
  }
  if($action==='location_delete'){admin();$id=(int)$_POST['id'];sql('DELETE FROM locations WHERE id=?',[$id]);audit('delete','location',$id);redirect('locations');}
  if($action==='user_save'){
   admin();$id=(int)($_POST['id']??0);$username=trim((string)($_POST['username']??''));$name=trim((string)($_POST['name']??''));$role=(string)($_POST['role']??'viewer');$active=isset($_POST['active'])?1:0;$pass=(string)($_POST['password']??'');
   if(!$username||!$name||!in_array($role,['admin','editor','viewer'],true))throw new RuntimeException('Ungültige Benutzerdaten.');
   if($id===(int)active_user()['id']&&($role!=='admin'||!$active))throw new RuntimeException('Eigenen Administratorzugang nicht deaktivieren.');
   if($pass!==''&&strlen($pass)<12)throw new RuntimeException('Passwort mindestens 12 Zeichen.');
   if(!$id&&!$pass)throw new RuntimeException('Initialpasswort erforderlich.');
   if($id){sql('UPDATE users SET username=?,name=?,role=?,active=? WHERE id=?',[$username,$name,$role,$active,$id]);if($pass)sql('UPDATE users SET password_hash=? WHERE id=?',[password_hash($pass,PASSWORD_DEFAULT),$id]);}
   else{sql('INSERT INTO users (username,name,role,active,password_hash) VALUES (?,?,?,?,?)',[$username,$name,$role,$active,password_hash($pass,PASSWORD_DEFAULT)]);$id=(int)db()->lastInsertId();}
   audit('save','user',$id);flash('Benutzer gespeichert.');redirect('users');
  }
  if($action==='user_delete'){
   admin();$id=(int)$_POST['id'];if($id===(int)active_user()['id'])throw new RuntimeException('Eigenen Account nicht löschen.');
   $u=one('SELECT role FROM users WHERE id=?',[$id]);if($u&&$u['role']==='admin'&&(int)one("SELECT COUNT(*) n FROM users WHERE role='admin' AND active=1")['n']<=1)throw new RuntimeException('Letzten Administrator nicht löschen.');
   sql('DELETE FROM users WHERE id=?',[$id]);audit('delete','user',$id);redirect('users');
  }
  if($action==='settings_save'){
   admin();setsetting('ai_model',trim((string)($_POST['ai_model']??'gpt-4o-mini')));
   if(trim((string)($_POST['ai_key']??''))!=='')setsetting('ai_key',trim((string)$_POST['ai_key']));
   if(isset($_POST['remove_key']))setsetting('ai_key','');flash('Einstellungen gespeichert.');redirect('settings');
  }
  if($action==='ai_classify'){
   editor();$id=(int)$_POST['id'];$item=one('SELECT * FROM items WHERE id=?',[$id]);if(!$item)throw new RuntimeException('Objekt existiert nicht.');$vals=ai_vectors($item,dims());
   db()->beginTransaction();foreach($vals as $dim=>$v)sql('INSERT INTO item_vectors (item_id,dimension_id,base_value,learned_value,confidence,applicable) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE base_value=VALUES(base_value),learned_value=VALUES(learned_value),confidence=VALUES(confidence),applicable=VALUES(applicable)',[$id,$dim,$v['value'],$v['value'],$v['confidence'],$v['applicable']?1:0]);db()->commit();
   audit('ai_classify','item',$id);flash('KI-Vorschlag übernommen. Bitte fachlich prüfen und gegebenenfalls korrigieren.');redirect('item',['id'=>$id]);
  }
  if($action==='keyword_save'){
   admin();$id=(int)$_POST['id'];foreach(dims() as $d){$did=(int)$d['id'];$val=clamp($_POST['val'][$did]??0.5);$conf=clamp($_POST['conf'][$did]??0);sql('INSERT INTO keyword_vectors (keyword_id,dimension_id,initial_value,value,confidence) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE initial_value=VALUES(initial_value),value=VALUES(value),confidence=VALUES(confidence)',[$id,$did,$val,$val,$conf]);}
   audit('edit','keyword',$id);flash('Keywordvektor korrigiert.');redirect('keywords',['id'=>$id]);
  }
  if($action==='keyword_delete'){admin();$id=(int)$_POST['id'];sql("DELETE FROM associations WHERE (source_type='keyword' AND source_id=?) OR (target_type='keyword' AND target_id=?)",[$id,$id]);sql('DELETE FROM keywords WHERE id=?',[$id]);audit('delete','keyword',$id);redirect('keywords');}
  if($action==='assoc_delete'){admin();$id=(int)$_POST['id'];sql('DELETE FROM associations WHERE id=?',[$id]);audit('delete','association',$id);redirect('associations');}
  if($action==='reset_learning'){
   admin();sql('UPDATE item_vectors SET learned_value=base_value');sql('UPDATE keyword_vectors SET value=initial_value');sql('DELETE FROM associations');audit('reset','learning');flash('Gelernte Positionen und Assoziationen zurückgesetzt. Historische Such- und Ausleihdaten bleiben erhalten.');redirect('settings');
  }
  if($action==='basket_add'){
   $id=(int)$_POST['id'];$qty=max(1,min(10000,(int)($_POST['qty']??1)));$n=availability($id);$prev=(int)($_SESSION['basket'][$id]['qty']??0);
   if(!$n||$qty+$prev>$n)throw new RuntimeException('Nicht ausreichend Exemplare verfügbar.');
   $sid=(int)($_POST['search_id']??0);$valid=$sid?one('SELECT id,keyword_id FROM searches WHERE id=? AND session_token=?',[$sid,session_id()]):null;
   $_SESSION['basket'][$id]=['qty'=>$qty+$prev,'search_id'=>$valid?(int)$sid:($_SESSION['basket'][$id]['search_id']??null)];
   if($valid&&$valid['keyword_id'])feedback((int)$valid['keyword_id'],$id,'basket',$sid);
   flash('Zum Ausleihkorb hinzugefügt.');redirect('basket');
  }
  if($action==='basket_remove'){unset($_SESSION['basket'][(int)$_POST['id']]);redirect('basket');}
  if($action==='checkout'){
   editor();$basket=$_SESSION['basket']??[];if(!$basket)throw new RuntimeException('Ausleihkorb ist leer.');$borrower=trim((string)($_POST['borrower']??''));if(!$borrower)throw new RuntimeException('Entleiher fehlt.');
   $date=trim((string)($_POST['due_date']??''));if($date!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))throw new RuntimeException('Ungültiges Datum.');
   db()->beginTransaction();
   $ids=array_map('intval',array_keys($basket));sort($ids,SORT_NUMERIC);foreach($ids as $iid){sql('SELECT id FROM items WHERE id=? FOR UPDATE',[$iid]);$qty=(int)$basket[$iid]['qty'];if($qty<1||$qty>availability($iid))throw new RuntimeException('Bestand hat sich verändert. Bitte Warenkorb prüfen.');}
   sql('INSERT INTO loans (borrower,contact,note,due_date,created_by) VALUES (?,?,?,?,?)',[$borrower,trim((string)($_POST['contact']??'')),trim((string)($_POST['note']??'')),$date?:null,active_user()['id']]);$lid=(int)db()->lastInsertId();
   foreach($ids as $iid){$row=one('SELECT title FROM items WHERE id=?',[$iid]);$qty=(int)$basket[$iid]['qty'];$sid=$basket[$iid]['search_id']??null;sql('INSERT INTO loan_items (loan_id,item_id,item_title,qty,search_id) VALUES (?,?,?,?,?)',[$lid,$iid,$row['title'],$qty,$sid]);if($sid){$s=one('SELECT keyword_id FROM searches WHERE id=? AND session_token=?',[$sid,session_id()]);if($s&&$s['keyword_id'])feedback((int)$s['keyword_id'],$iid,'borrow',(int)$sid);}}
   db()->commit();unset($_SESSION['basket']);audit('checkout','loan',$lid);flash('Ausleihe erfolgreich gespeichert.');redirect('loans',['id'=>$lid]);
  }
  if($action==='return'){
   editor();$line=(int)$_POST['line_id'];$num=max(1,(int)($_POST['qty']??1));db()->beginTransaction();$l=one('SELECT * FROM loan_items WHERE id=? FOR UPDATE',[$line]);if(!$l)throw new RuntimeException('Ausleihposition nicht vorhanden.');if($num>(int)$l['qty']-(int)$l['returned_qty'])throw new RuntimeException('Zu viele Exemplare zurückgegeben.');sql('UPDATE loan_items SET returned_qty=returned_qty+? WHERE id=?',[$num,$line]);db()->commit();audit('return','loan',(int)$l['loan_id']);flash('Rückgabe gespeichert.');redirect('loans',['id'=>$l['loan_id']]);
  }
  if($action==='loan_line_edit'){
   admin();$line=(int)($_POST['line_id']??0);$qty=(int)($_POST['qty']??0);$returned=(int)($_POST['returned_qty']??0);
   db()->beginTransaction();$l=one('SELECT * FROM loan_items WHERE id=? FOR UPDATE',[$line]);if(!$l)throw new RuntimeException('Position nicht gefunden.');
   if($qty<0||$returned<0||$returned>$qty)throw new RuntimeException('Ungültige Mengen.');
   if($l['item_id']){sql('SELECT id FROM items WHERE id=? FOR UPDATE',[(int)$l['item_id']]);$change=($qty-$returned)-((int)$l['qty']-(int)$l['returned_qty']);if($change>0&&$change>availability((int)$l['item_id']))throw new RuntimeException('Nicht genügend verfügbare Exemplare.');}
   if($qty===0)sql('DELETE FROM loan_items WHERE id=?',[$line]);else sql('UPDATE loan_items SET qty=?,returned_qty=? WHERE id=?',[$qty,$returned,$line]);
   db()->commit();audit('edit_line','loan',(int)$l['loan_id']);flash('Ausleihposition korrigiert.');redirect('loans',['id'=>$l['loan_id']]);
  }
  if($action==='search_history_delete'){
   admin();sql('DELETE FROM interactions');sql('DELETE FROM searches');audit('delete','search_history');flash('Such- und Klickprotokolle gelöscht. Aggregierte Keyword- und Assoziationsdaten bleiben bestehen.');redirect('settings');
  }
  if($action==='loan_edit'){
   editor();$id=(int)$_POST['id'];$borrower=trim((string)($_POST['borrower']??''));if(!$borrower)throw new RuntimeException('Entleiher fehlt.');$due=(string)($_POST['due_date']??'');sql('UPDATE loans SET borrower=?,contact=?,note=?,due_date=? WHERE id=?',[$borrower,trim((string)($_POST['contact']??'')),trim((string)($_POST['note']??'')),$due?:null,$id]);audit('edit','loan',$id);redirect('loans',['id'=>$id]);
  }
  if($action==='loan_delete'){
   admin();$id=(int)$_POST['id'];sql('DELETE FROM loans WHERE id=?',[$id]);audit('delete','loan',$id);flash('Ausleihe gelöscht; offene Mengen wieder verfügbar.');redirect('loans');
  }
 }
}catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();$error=$ex->getMessage();}
if(!active_user()&&$page!=='login')redirect('login');
function postform(string $action,array $hidden=[],string $label='Speichern',string $class=''):string{$out='<form method="post" class="inline">'.fieldcsrf().'<input type="hidden" name="action" value="'.e($action).'">';foreach($hidden as $k=>$v)$out.='<input type="hidden" name="'.e($k).'" value="'.e($v).'">';return $out.'<button class="'.e($class).'">'.e($label).'</button></form>';}
function pagehead(string $title):void{echo '<h1>'.e($title).'</h1>';}
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e(ucfirst($page))?> · Lernwerkstatt</title><link rel="stylesheet" href="style.css"></head><body>
<?php if(active_user()):?><nav class="top"><a class="brand" href="<?=url('search')?>">◈ Lernwerkstatt</a><a href="<?=url('search')?>">Suche</a><a href="<?=url('items')?>">Inventar</a><a href="<?=url('basket')?>">Ausleihkorb (<?=count($_SESSION['basket']??[])?>)</a><a href="<?=url('loans')?>">Ausleihen</a><?php if(active_user()['role']==='admin'):?><a href="<?=url('dimensions')?>">Administration</a><?php endif;?><a href="<?=url('account')?>"><?=e(active_user()['name'])?></a></nav><?php endif;?><main class="container">
<?php if($error):?><div class="alert"><?=e($error)?></div><?php endif;?><?php if(isset($_SESSION['flash'])):?><div class="alert success"><?=e($_SESSION['flash'])?></div><?php unset($_SESSION['flash']);endif;?>
<?php
switch($page){
case 'login':
 if(active_user())redirect('search');pagehead('Anmeldung');echo '<div class="narrow card"><form method="post" class="form">'.fieldcsrf().'<input type="hidden" name="action" value="login"><label>Benutzername<input name="username" autocomplete="username" required></label><label>Passwort<input name="password" type="password" autocomplete="current-password" required></label><button>Anmelden</button></form></div>';break;
case 'account':
 pagehead('Mein Konto');echo '<div class="narrow card"><p>'.e(active_user()['name']).' · '.e(active_user()['role']).'</p><form class="form" method="post">'.fieldcsrf().'<input type="hidden" name="action" value="change_password"><label>Altes Passwort<input type="password" name="old_password" required></label><label>Neues Passwort (mindestens 12 Zeichen)<input type="password" name="new_password" minlength="12" required></label><button>Passwort ändern</button></form><hr>'.postform('logout',[],'Abmelden','secondary').'</div>';break;
case 'search':
 $term=trim((string)($_GET['q']??''));pagehead('Materialien finden');echo '<form method="get" class="searchbar"><input type="hidden" name="p" value="search"><input type="search" name="q" maxlength="190" placeholder="Was suchst du? z. B. Stromkreis, Magnet, Basteln …" value="'.e($term).'" autofocus><button>Suchen</button></form>';
 if($term!==''){
  $kid=initkeyword($term);$prev=$_SESSION['last_search']??null;
  if($prev&&time()-(int)$prev['at']<900&&(int)$prev['keyword_id']!==$kid)touch_assoc('keyword',(int)$prev['keyword_id'],'keyword',$kid,0.08);
  sql('INSERT INTO searches (session_token,user_id,query,keyword_id) VALUES (?,?,?,?)',[session_id(),active_user()['id'],$term,$kid]);$sid=(int)db()->lastInsertId();$_SESSION['last_search']=['keyword_id'=>$kid,'at'=>time()];
  $items=rows('SELECT i.*,l.name location_name FROM items i LEFT JOIN locations l ON l.id=i.location_id');$items=rankitems($term,$kid,$items);echo '<p class="muted">'.count($items).' Gegenstände · Ranking aus Vektorähnlichkeit, Texttreffer und gelernten Assoziationen. <a href="'.url('search').'">Suche zurücksetzen</a></p><div class="grid">';
  foreach(array_slice($items,0,100) as $i){$id=(int)$i['id'];echo '<article class="card item">';if($i['image_path'])echo '<img class="thumb" alt="" src="'.e($i['image_path']).'">';echo '<div class="split"><span class="pill">'.e($i['category']?:'Material').'</span><strong>'.e($i['score']).' Punkte</strong></div><h3><a href="'.url('item',['id'=>$id,'s'=>$sid]).'">'.e($i['title']).'</a></h3><p>'.e(mb_strimwidth((string)$i['description'],0,160,'…','UTF-8')).'</p><small>'.e($i['location_name']??'Ohne Lagerort').' · Verfügbar: '.availability($id).'</small><small>Vektor '.e($i['explain']['vector']).' % · Text '.e($i['explain']['text']).' % · Assoziation '.e($i['explain']['association']).' %</small><div class="actions"><a class="btn secondary" href="'.url('item',['id'=>$id,'s'=>$sid]).'">Details</a>';if(availability($id))echo postform('basket_add',['id'=>$id,'search_id'=>$sid],'Ausleihen');echo '</div></article>';}
  echo '</div>';
 }else echo '<p class="muted">Durchsuche deine Lernwerkstatt klassisch und semantisch. Die Zuordnungen verbessern sich mit Klicks und Ausleihen.</p>';
 break;
case 'items':
 pagehead('Inventar');if(can_edit())echo '<p><a class="btn" href="'.url('item_edit').'">+ Gegenstand erfassen</a></p>';
 $filter=trim((string)($_GET['filter']??''));echo '<form method="get" class="searchbar"><input type="hidden" name="p" value="items"><input name="filter" type="search" value="'.e($filter).'" placeholder="Titel, Kategorie, Schlagworte …"><button>Filtern</button></form>';
 $items=$filter!==''?rows('SELECT i.*,l.name location_name FROM items i LEFT JOIN locations l ON l.id=i.location_id WHERE i.title LIKE ? OR i.description LIKE ? OR i.tags LIKE ? OR i.category LIKE ? ORDER BY i.title',array_fill(0,4,'%'.$filter.'%')):rows('SELECT i.*,l.name location_name FROM items i LEFT JOIN locations l ON l.id=i.location_id ORDER BY i.title');
 echo '<div class="tablewrap"><table><tr><th>Objekt</th><th>Kategorie</th><th>Lagerort</th><th>Gesamt</th><th>Verfügbar</th><th></th></tr>';foreach($items as $i)echo '<tr><td><a href="'.url('item',['id'=>$i['id']]).'">'.e($i['title']).'</a></td><td>'.e($i['category']).'</td><td>'.e($i['location_name']).'</td><td>'.e($i['quantity']).'</td><td>'.availability((int)$i['id']).'</td><td>'.(can_edit()?'<a href="'.url('item_edit',['id'=>$i['id']]).'">Bearbeiten</a>':'').'</td></tr>';echo '</table></div>';break;
case 'item':
 $id=(int)($_GET['id']??0);$i=one('SELECT i.*,l.name location_name FROM items i LEFT JOIN locations l ON l.id=i.location_id WHERE i.id=?',[$id]);if(!$i){echo '<p>Objekt nicht gefunden.</p>';break;}
 $sid=(int)($_GET['s']??0);$s=$sid?one('SELECT keyword_id FROM searches WHERE id=? AND session_token=?',[$sid,session_id()]):null;
 if($s&&$s['keyword_id']&&($_SESSION['clicked'][$sid.':'.$id]??false)!==true){feedback((int)$s['keyword_id'],$id,'click',$sid);$_SESSION['clicked'][$sid.':'.$id]=true;}
 pagehead($i['title']);echo '<div class="twocol"><section class="card">';if($i['image_path'])echo '<img class="thumb" style="height:280px" alt="Materialfoto" src="'.e($i['image_path']).'">';echo '<p>'.nl2br(e($i['description'])).'</p><p><strong>Schlagworte:</strong> '.e($i['tags']).'<br><strong>Kategorie:</strong> '.e($i['category']).'<br><strong>Lagerort:</strong> '.e($i['location_name']??'Nicht zugeordnet').'<br><strong>Bestand:</strong> '.e($i['quantity']).' · <strong>Verfügbar:</strong> '.availability($id).'</p>';if(availability($id))echo '<form method="post" class="inline">'.fieldcsrf().'<input type="hidden" name="action" value="basket_add"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="search_id" value="'.($s?$sid:0).'"><label>Menge <input name="qty" type="number" value="1" min="1" max="'.availability($id).'" style="width:80px"></label><button>In Ausleihkorb</button></form>';
 if(can_edit())echo '<p><a class="btn secondary" href="'.url('item_edit',['id'=>$id]).'">Bearbeiten</a></p>';echo '</section><section class="card"><h2>Semantische Merkmale</h2>';
 $vec=storedvector('item_vectors','base_value',$id);foreach(dims() as $d){$v=$vec[(int)$d['id']]??null;if(!$v||!(int)$v['applicable']||(float)$v['confidence']===0)continue;echo '<p><strong>'.e($d['name']).'</strong> · '.e($v['base_value']).' <small>(Sicherheit '.round(100*(float)$v['confidence']).' %)</small><br><meter min="0" max="1" value="'.e($v['base_value']).'" style="width:100%"></meter></p>';}
 echo '</section></div>';break;
case 'item_edit':
 editor();$id=(int)($_GET['id']??0);$i=$id?one('SELECT * FROM items WHERE id=?',[$id]):null;if($id&&!$i)throw new RuntimeException('Objekt fehlt.');$i??=['title'=>'','description'=>'','tags'=>'','category'=>'','location_id'=>'','quantity'=>1,'image_path'=>null];$vec=$id?storedvector('item_vectors','base_value',$id):[];
 pagehead($id?'Gegenstand bearbeiten':'Neuer Gegenstand');echo '<form method="post" class="form card">'.fieldcsrf().'<input type="hidden" name="action" value="item_save"><input type="hidden" name="id" value="'.$id.'"><div class="twocol"><label>Titel<input name="title" maxlength="255" value="'.e($i['title']).'" required></label><label>Kategorie (frei)<input name="category" value="'.e($i['category']).'"></label></div><label>Beschreibung<textarea name="description">'.e($i['description']).'</textarea></label><label>Schlagworte / Synonyme (kommagetrennt)<input name="tags" value="'.e($i['tags']).'"></label><div class="twocol"><label>Lagerort<select name="location_id"><option value="">— Ohne —</option>';
 foreach(rows('SELECT * FROM locations ORDER BY name') as $l)echo '<option value="'.$l['id'].'" '.((int)$i['location_id']===(int)$l['id']?'selected':'').'>'.e($l['name']).'</option>';echo '</select></label><label>Bestand<input type="number" min="0" name="quantity" value="'.e($i['quantity']).'" required></label></div><h2>Semantischer Basisvektor</h2><p class="muted">0 = geringe Ausprägung, 1 = starke Ausprägung. Sicherheit 0 = unbekannt. Nicht anwendbare Dimensionen abwählen. Beim Speichern werden gelernte Objektanteile für diese Dimensionen zurückgesetzt.</p><input id="dimension-filter" placeholder="Dimensionen filtern …" style="width:100%;padding:10px" oninput="document.querySelectorAll(&quot;.dimrow&quot;).forEach(x=>x.hidden=!x.textContent.toLowerCase().includes(this.value.toLowerCase()))"><div class="dimrow"><strong>Dimension</strong><strong>Wert</strong><strong>Sicherheit</strong><strong>Anwendbar</strong></div>';
 foreach(dims() as $d){$did=(int)$d['id'];$v=$vec[$did]??null;echo '<div class="dimrow"><div><strong>'.e($d['name']).'</strong><small>'.e($d['description']).'</small></div><input type="number" step="0.01" min="0" max="1" name="val['.$did.']" value="'.e($v['base_value']??0.5).'"><input type="number" step="0.01" min="0" max="1" name="conf['.$did.']" value="'.e($v['confidence']??0).'"><label><input type="checkbox" name="app['.$did.']" '.((!$v||(int)$v['applicable'])?'checked':'').'> Ja</label></div>';}
 echo '<p><button>Gegenstand speichern</button> <a class="btn secondary" href="'.url('items').'">Abbrechen</a></p></form>';
 if($id){echo '<div class="card"><h2>Foto</h2><form method="post" enctype="multipart/form-data" class="inline">'.fieldcsrf().'<input type="hidden" name="action" value="upload_image"><input type="hidden" name="id" value="'.$id.'"><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required><button>Bild hochladen</button></form>';if($i['image_path'])echo '<p>'.postform('remove_image',['id'=>$id],'Foto entfernen','secondary').'</p>';echo '</div><div class="card"><h2>KI-Initialisierung</h2><p>KI klassifiziert anhand der gespeicherten Dimensionsdefinitionen. Bestehende Werte werden überschrieben. Bitte Ergebnis prüfen.</p>'.postform('ai_classify',['id'=>$id],'Mit KI klassifizieren','secondary').'</div>';
 if((active_user()['role']??'')==='admin')echo '<div class="card"><h2>Gefahrenzone</h2>'.postform('item_delete',['id'=>$id],'Gegenstand löschen','danger').'</div>';}
 break;
case 'basket':
 pagehead('Ausleihkorb');$basket=$_SESSION['basket']??[];
 if(!$basket){echo '<p>Der Ausleihkorb ist leer. <a href="'.url('search').'">Zur Suche</a></p>';break;}
 echo '<div class="card tablewrap"><table><tr><th>Gegenstand</th><th>Menge</th><th>Verfügbar</th><th></th></tr>';
 foreach($basket as $id=>$info){$i=one('SELECT title FROM items WHERE id=?',[(int)$id]);echo '<tr><td>'.e($i['title']??'Gelöschter Gegenstand').'</td><td>'.e($info['qty']).'</td><td>'.availability((int)$id).'</td><td>'.postform('basket_remove',['id'=>$id],'Entfernen','secondary').'</td></tr>';}
 echo '</table></div>';
 if(can_edit())echo '<form class="card form" method="post">'.fieldcsrf().'<input type="hidden" name="action" value="checkout"><h2>Ausleihe abschließen</h2><label>Entleiher / Gruppe<input name="borrower" maxlength="180" required></label><label>Kontakt / Klasse (optional)<input name="contact" maxlength="180"></label><label>Fällig am<input name="due_date" type="date"></label><label>Bemerkung<textarea name="note"></textarea></label><button>Ausleihe verbindlich speichern</button></form>';
 else echo '<p class="muted">Nur Redakteure und Administratoren können eine Ausleihe abschließen.</p>';break;
case 'loans':
 $id=(int)($_GET['id']??0);if($id){$loan=one('SELECT * FROM loans WHERE id=?',[$id]);if(!$loan){echo '<p>Ausleihe nicht gefunden.</p>';break;}
  pagehead('Ausleihe #'.$id);echo '<div class="card"><form method="post" class="form">'.fieldcsrf().'<input type="hidden" name="action" value="loan_edit"><input type="hidden" name="id" value="'.$id.'"><label>Entleiher<input name="borrower" value="'.e($loan['borrower']).'" '.(!can_edit()?'disabled':'').'></label><label>Kontakt<input name="contact" value="'.e($loan['contact']).'" '.(!can_edit()?'disabled':'').'></label><label>Fällig am<input type="date" name="due_date" value="'.e($loan['due_date']).'" '.(!can_edit()?'disabled':'').'></label><label>Notiz<textarea name="note" '.(!can_edit()?'disabled':'').'>'.e($loan['note']).'</textarea></label>';if(can_edit())echo '<button>Änderungen speichern</button>';echo '</form></div><div class="card tablewrap"><h2>Positionen</h2><table><tr><th>Gegenstand</th><th>Ausgeliehen</th><th>Zurück</th><th>Rückgabe</th></tr>';
  foreach(rows('SELECT * FROM loan_items WHERE loan_id=?',[$id]) as $l){$open=(int)$l['qty']-(int)$l['returned_qty'];echo '<tr><td>'.e($l['item_title']).'</td><td>'.e($l['qty']).'</td><td>'.e($l['returned_qty']).'</td><td>';if(can_edit()&&$open)echo '<form method="post" class="inline">'.fieldcsrf().'<input type="hidden" name="action" value="return"><input type="hidden" name="line_id" value="'.$l['id'].'"><input name="qty" type="number" min="1" max="'.$open.'" value="'.$open.'" style="width:75px"><button>Zurückbuchen</button></form>';else echo '—';if((active_user()['role']??'')==='admin')echo '<form class="inline" method="post" style="margin-top:8px">'.fieldcsrf().'<input type="hidden" name="action" value="loan_line_edit"><input type="hidden" name="line_id" value="'.$l['id'].'"><small>Korrigieren:</small><input type="number" name="qty" min="0" value="'.e($l['qty']).'" style="width:75px"><input type="number" name="returned_qty" min="0" value="'.e($l['returned_qty']).'" style="width:75px"><button class="secondary">Ändern</button></form>';echo '</td></tr>';}
  echo '</table></div>';if((active_user()['role']??'')==='admin')echo '<div class="card">'.postform('loan_delete',['id'=>$id],'Ausleihe löschen','danger').'</div>';echo '<a href="'.url('loans').'">← Ausleihen</a>';break;
 }
 pagehead('Ausleihen');echo '<div class="tablewrap"><table><tr><th>Nr.</th><th>Entleiher</th><th>Datum</th><th>Fälligkeit</th><th>Offene Exemplare</th></tr>';
 foreach(rows('SELECT l.*,COALESCE((SELECT SUM(qty-returned_qty) FROM loan_items WHERE loan_id=l.id),0) open_items FROM loans l ORDER BY l.created_at DESC LIMIT 500') as $l)echo '<tr><td><a href="'.url('loans',['id'=>$l['id']]).'">#'.e($l['id']).'</a></td><td>'.e($l['borrower']).'</td><td>'.e($l['created_at']).'</td><td>'.e($l['due_date']).'</td><td>'.e($l['open_items']).'</td></tr>';echo '</table></div>';break;
case 'dimensions':
 admin();pagehead('Dimensionen');echo '<p><a href="'.url('locations').'">Lagerorte</a> · <a href="'.url('users').'">Benutzer</a> · <a href="'.url('keywords').'">Keywords</a> · <a href="'.url('associations').'">Assoziationen</a> · <a href="'.url('settings').'">Einstellungen</a> · <a href="'.url('audit').'">Protokoll</a></p>';
 $id=(int)($_GET['id']??0);$d=$id?one('SELECT * FROM dimensions WHERE id=?',[$id]):null;$d??=['name'=>'','description'=>'','low_label'=>'Kein Bezug','high_label'=>'Starker Bezug','weight'=>1,'enabled'=>1];
 echo '<form class="card form" method="post">'.fieldcsrf().'<input type="hidden" name="action" value="dimension_save"><input type="hidden" name="id" value="'.$id.'"><h2>'.($id?'Dimension bearbeiten':'Dimension hinzufügen').'</h2><label>Name<input name="name" maxlength="120" value="'.e($d['name']).'" required></label><label>Definition / Beschreibung<textarea name="description">'.e($d['description']).'</textarea></label><div class="twocol"><label>Bedeutung 0<input name="low_label" value="'.e($d['low_label']).'" required></label><label>Bedeutung 1<input name="high_label" value="'.e($d['high_label']).'" required></label></div><label>Distanzgewicht<input name="weight" type="number" step="0.1" min="0.01" max="100" value="'.e($d['weight']).'" required></label><label><input type="checkbox" name="enabled" '.($d['enabled']?'checked':'').'> Aktiv</label><button>Speichern</button></form><div class="card tablewrap"><table><tr><th>Dimension</th><th>Beschreibung</th><th>Gewicht</th><th></th></tr>';
 foreach(rows('SELECT * FROM dimensions ORDER BY name') as $row)echo '<tr><td><a href="'.url('dimensions',['id'=>$row['id']]).'">'.e($row['name']).'</a>'.(!$row['enabled']?' (inaktiv)':'').'</td><td>'.e($row['description']).'</td><td>'.e($row['weight']).'</td><td>'.postform('dimension_delete',['id'=>$row['id']],'Löschen','danger').'</td></tr>';echo '</table></div>';break;
case 'locations':
 admin();pagehead('Lagerorte');$id=(int)($_GET['id']??0);$loc=$id?one('SELECT * FROM locations WHERE id=?',[$id]):null;$loc??=['name'=>'','description'=>'','parent_id'=>null];$locations=rows('SELECT * FROM locations ORDER BY name');
 echo '<form class="card form" method="post">'.fieldcsrf().'<input type="hidden" name="action" value="location_save"><input type="hidden" name="id" value="'.$id.'"><label>Bezeichnung / Fach<input name="name" value="'.e($loc['name']).'" required></label><label>Übergeordneter Lagerort<select name="parent_id"><option value="0">Keiner</option>';
 foreach($locations as $l)if((int)$l['id']!==$id)echo '<option value="'.$l['id'].'" '.((int)$loc['parent_id']===(int)$l['id']?'selected':'').'>'.e($l['name']).'</option>';echo '</select></label><label>Beschreibung<textarea name="description">'.e($loc['description']).'</textarea></label><button>Speichern</button></form><div class="card tablewrap"><table><tr><th>Fach / Ort</th><th>Beschreibung</th><th>Aktion</th></tr>';
 foreach($locations as $l)echo '<tr><td><a href="'.url('locations',['id'=>$l['id']]).'">'.e($l['name']).'</a></td><td>'.e($l['description']).'</td><td>'.postform('location_delete',['id'=>$l['id']],'Löschen','danger').'</td></tr>';echo '</table></div><p><a href="'.url('dimensions').'">← Administration</a></p>';break;
case 'users':
 admin();pagehead('Benutzerverwaltung');$id=(int)($_GET['id']??0);$u=$id?one('SELECT * FROM users WHERE id=?',[$id]):null;$u??=['username'=>'','name'=>'','role'=>'viewer','active'=>1];
 echo '<form class="card form" method="post">'.fieldcsrf().'<input type="hidden" name="action" value="user_save"><input type="hidden" name="id" value="'.$id.'"><h2>'.($id?'Benutzer bearbeiten':'Benutzer anlegen').'</h2><div class="twocol"><label>Benutzername<input name="username" value="'.e($u['username']).'" required></label><label>Anzeigename<input name="name" value="'.e($u['name']).'" required></label></div><label>Rolle<select name="role">';foreach(['viewer'=>'Betrachter','editor'=>'Redakteur / Ausleihe','admin'=>'Administrator'] as $r=>$label)echo '<option value="'.$r.'" '.($u['role']===$r?'selected':'').'>'.$label.'</option>';echo '</select></label><label>Passwort ('.($id?'leer lassen = unverändert':'min. 12 Zeichen').')<input type="password" name="password" autocomplete="new-password" '.(!$id?'required minlength="12"':'').'></label><label><input type="checkbox" name="active" '.($u['active']?'checked':'').'> Aktiv</label><button>Speichern</button></form><div class="card tablewrap"><table><tr><th>Benutzer</th><th>Name</th><th>Rolle</th><th>Status</th><th></th></tr>';
 foreach(rows('SELECT * FROM users ORDER BY username') as $u)echo '<tr><td><a href="'.url('users',['id'=>$u['id']]).'">'.e($u['username']).'</a></td><td>'.e($u['name']).'</td><td>'.e($u['role']).'</td><td>'.($u['active']?'Aktiv':'Inaktiv').'</td><td>'.($u['id']!=active_user()['id']?postform('user_delete',['id'=>$u['id']],'Löschen','danger'):'').'</td></tr>';echo '</table></div>';break;
case 'keywords':
 admin();pagehead('Gelernte Suchbegriffe');$id=(int)($_GET['id']??0);if($id){$k=one('SELECT * FROM keywords WHERE id=?',[$id]);if(!$k){echo 'Nicht gefunden';break;}$kv=storedvector('keyword_vectors','value',$id);echo '<p><strong>'.e($k['term']).'</strong> · Suchen: '.e($k['usage_count']).'</p><form method="post" class="card form">'.fieldcsrf().'<input type="hidden" name="action" value="keyword_save"><input type="hidden" name="id" value="'.$id.'"><div class="dimrow"><strong>Dimension</strong><strong>Wert</strong><strong>Sicherheit</strong></div>';foreach(dims() as $d){$did=(int)$d['id'];$v=$kv[$did]??null;echo '<div class="dimrow"><strong>'.e($d['name']).'</strong><input type="number" name="val['.$did.']" step="0.01" min="0" max="1" value="'.e($v['value']??0.5).'"><input type="number" name="conf['.$did.']" step="0.01" min="0" max="1" value="'.e($v['confidence']??0).'"></div>';}echo '<button>Korrigieren und Lernwert zurücksetzen</button></form>';}else{echo '<div class="card tablewrap"><table><tr><th>Suchbegriff</th><th>Verwendung</th><th>Aktion</th></tr>';foreach(rows('SELECT * FROM keywords ORDER BY usage_count DESC,term LIMIT 500') as $k)echo '<tr><td><a href="'.url('keywords',['id'=>$k['id']]).'">'.e($k['term']).'</a></td><td>'.e($k['usage_count']).'</td><td>'.postform('keyword_delete',['id'=>$k['id']],'Löschen','danger').'</td></tr>';echo '</table></div>';}break;
case 'associations':
 admin();pagehead('Assoziationsnetzwerk');echo '<p class="muted">Die Kanten verbinden Suchbegriffe oder Gegenstände. Sie beeinflussen das Ranking getrennt von der geometrischen Ähnlichkeit.</p><div class="card tablewrap"><table><tr><th>Quelle</th><th>Ziel</th><th>Stärke</th><th>Ereignisse</th><th></th></tr>';
 foreach(rows('SELECT * FROM associations ORDER BY strength DESC LIMIT 300') as $a){$from=$a['source_type']==='keyword'?one('SELECT term name FROM keywords WHERE id=?',[$a['source_id']]):one('SELECT title name FROM items WHERE id=?',[$a['source_id']]);$to=$a['target_type']==='keyword'?one('SELECT term name FROM keywords WHERE id=?',[$a['target_id']]):one('SELECT title name FROM items WHERE id=?',[$a['target_id']]);echo '<tr><td>'.e($from['name']??'[entfernt]').'</td><td>'.e($to['name']??'[entfernt]').'</td><td>'.e($a['strength']).'</td><td>'.e($a['interactions']).'</td><td>'.postform('assoc_delete',['id'=>$a['id']],'Löschen','danger').'</td></tr>';}
 echo '</table></div>';break;
case 'settings':
 admin();pagehead('Einstellungen');echo '<form class="card form" method="post">'.fieldcsrf().'<input type="hidden" name="action" value="settings_save"><h2>Optionale KI-Klassifikation</h2><p class="muted">Nutzt die OpenAI Chat-Completions-API. Ohne Schlüssel bleibt die manuelle Klassifikation und lernende Suche funktionsfähig. Ein Schlüssel kann alternativ per Server-Umgebungsvariable OPENAI_API_KEY bereitgestellt werden. Titel und Beschreibungen werden bei einer KI-Klassifikation an den Dienst übermittelt.</p><label>Modell<input name="ai_model" value="'.e(setting('ai_model','gpt-4o-mini')).'"></label><label>API-Schlüssel (leer = unverändert)<input type="password" name="ai_key" autocomplete="off"></label><label><input type="checkbox" name="remove_key"> Gespeicherten Schlüssel entfernen</label><button>Speichern</button></form><div class="card"><h2>Lernwerte zurücksetzen</h2><p>Basiswerte, Inventar, Ausleihen und Suchprotokolle bleiben. Gelernte Vektoren und Assoziationen werden zurückgesetzt.</p>'.postform('reset_learning',[],'Lernwerte zurücksetzen','danger').'</div>';break;
case 'audit':
 admin();pagehead('Änderungsprotokoll');echo '<div class="card tablewrap"><table><tr><th>Zeit</th><th>Benutzer</th><th>Aktion</th><th>Typ</th><th>Nr.</th></tr>';foreach(rows('SELECT a.*,u.username FROM audit_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 300') as $a)echo '<tr><td>'.e($a['created_at']).'</td><td>'.e($a['username']??'Gelöscht').'</td><td>'.e($a['action']).'</td><td>'.e($a['entity']).'</td><td>'.e($a['entity_id']).'</td></tr>';echo '</table></div>';break;
default:http_response_code(404);echo '<h1>Seite nicht gefunden</h1>';
}
?></main><footer style="text-align:center;padding:30px;color:#60718a">Lernwerkstatt · Inventar und adaptive semantische Suche</footer>
<script>document.querySelectorAll('form').forEach(f=>{const a=f.querySelector('input[name=action]');if(a&&['item_delete','loan_delete','user_delete','dimension_delete','location_delete','keyword_delete','assoc_delete','reset_learning','search_history_delete'].includes(a.value)){f.addEventListener('submit',e=>{if(!confirm('Wirklich löschen bzw. zurücksetzen?'))e.preventDefault();});}});</script>
</body></html>
