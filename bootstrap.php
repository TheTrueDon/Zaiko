<?php
declare(strict_types=1);
ini_set('display_errors','0');
if (session_status() !== PHP_SESSION_ACTIVE) {
 session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','samesite'=>'Lax']); session_start();
}
if (!is_file(__DIR__.'/config.php')) { if (basename($_SERVER['SCRIPT_NAME']??'')!=='install.php') {header('Location: install.php');exit;} }
else {require __DIR__.'/config.php';}
function db(): PDO {static $db=null; if (!$db) {$db=new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}return $db;}
// Tabellenpräfix zentral anwenden: SQL-Tabellennamen werden nur an Tabellenpositionen ersetzt.
// Die Liste ist bewusst fest vorgegeben, damit niemals Benutzereingaben als SQL-Identifier dienen.
function table_prefix():string {return defined('DB_PREFIX') ? DB_PREFIX : '';}
function prefixed_sql(string $query,string $prefix=null):string {
 $prefix=$prefix??table_prefix();
 if($prefix==='')return $query;
 if(!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,39}$/D',$prefix))throw new RuntimeException('Ungültiger Tabellenpräfix.');
 $tables='users|settings|locations|dimensions|items|item_vectors|keywords|keyword_vectors|associations|searches|interactions|loans|loan_items|audit_log';
 // SQL-Klauseln (auch FK-REFERENCES und ALTER/DROP/CREATE) statt globaler Textersetzung.
 return preg_replace_callback('/\b(FROM|JOIN|INTO|UPDATE|REFERENCES|TABLE(?:\s+IF\s+NOT\s+EXISTS)?|TABLE(?:\s+IF\s+EXISTS)?)\s+`?('.$tables.')`?\b/i',
  static function(array $m)use($prefix):string {return $m[1].' `'.$prefix.$m[2].'`';},$query);
}
function sql(string $s,array $p=[]):PDOStatement {$stmt=db()->prepare(prefixed_sql($s));$stmt->execute($p);return $stmt;}
function rows(string $s,array $p=[]):array {return sql($s,$p)->fetchAll();}
function one(string $s,array $p=[]):?array {$v=sql($s,$p)->fetch();return $v?:null;}
function e($v):string {return htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function url(string $page='', array $args=[]):string{return 'index.php?'.http_build_query(array_merge(['p'=>$page?:'search'],$args));}
function redirect(string $page='',array $args=[]):never {header('Location: '.url($page,$args));exit;}
function csrf():string{return $_SESSION['csrf']??=bin2hex(random_bytes(24));}
function fieldcsrf():string{return '<input type="hidden" name="csrf" value="'.e(csrf()).'">';}
function checkcsrf():void{if (!hash_equals(csrf(),(string)($_POST['csrf']??''))) throw new RuntimeException('Ungültiges Sicherheits-Token. Bitte Seite neu laden.');}
function active_user():?array{return $_SESSION['user']??null;}
function require_login():void{if(!active_user())redirect('login');}
function can_edit():bool{return in_array(active_user()['role']??'', ['admin','editor'],true);}
function admin():void{require_login();if((active_user()['role']??'')!=='admin')throw new RuntimeException('Keine Administratorberechtigung.');}
function editor():void{require_login();if(!can_edit())throw new RuntimeException('Keine Schreibberechtigung.');}
function setting(string $k,string $default=''):string{return one('SELECT value FROM settings WHERE `key`=?',[$k])['value']??$default;}
function setsetting(string $k,string $v):void{sql('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',[$k,$v]);}
function flash(string $message):void{$_SESSION['flash']=$message;}
function audit(string $action,string $entity,int $id=0,string $detail=''):void{sql('INSERT INTO audit_log (user_id,action,entity,entity_id,details) VALUES (?,?,?,?,?)',[active_user()['id']??null,$action,$entity,$id?:null,$detail]);}
function norm(string $s):string{$s=mb_strtolower(trim(preg_replace('/\s+/u',' ',$s)),'UTF-8');return mb_substr($s,0,190,'UTF-8');}
function clamp($n):float{return max(0,min(1,(float)$n));}
function availability(int $id):int{$r=one('SELECT i.quantity-COALESCE((SELECT SUM(li.qty-li.returned_qty) FROM loan_items li WHERE li.item_id=i.id),0) n FROM items i WHERE i.id=?',[$id]);return max(0,(int)($r['n']??0));}
function dims():array{return rows('SELECT * FROM dimensions WHERE enabled=1 ORDER BY name');}
function storedvector(string $table,string $column,int $id):array{$key=$table==='item_vectors'?'item_id':'keyword_id';$rs=rows("SELECT * FROM $table WHERE $key=?",[$id]);$map=[];foreach($rs as $r)$map[(int)$r['dimension_id']]=$r;return $map;}
function touch_assoc(string $a,int $aid,string $b,int $bid,float $delta):void{
 if(!$aid||!$bid||($a===$b&&$aid===$bid))return;
 if(strcmp($a.':'.$aid,$b.':'.$bid)>0){[$a,$b]=[$b,$a];[$aid,$bid]=[$bid,$aid];}
 sql('INSERT INTO associations (source_type,source_id,target_type,target_id,strength,interactions) VALUES (?,?,?,?,?,1) ON DUPLICATE KEY UPDATE strength=LEAST(100,strength+VALUES(strength)),interactions=interactions+1',[$a,$aid,$b,$bid,$delta]);
}
function feedback(int $kid,int $iid,string $event,?int $searchId):void{
 sql('INSERT INTO interactions (search_id,item_id,event) VALUES (?,?,?)',[$searchId,$iid,$event]);
 $strength=['click'=>0.5,'basket'=>1.5,'borrow'=>3.0][$event]??0;
 if(!$kid)return;
 touch_assoc('keyword',$kid,'item',$iid,$strength);
 $lr=['click'=>0.012,'basket'=>0.025,'borrow'=>0.05][$event]??0.01;
 $kv=storedvector('keyword_vectors','value',$kid);$iv=storedvector('item_vectors','base_value',$iid);
 foreach($iv as $dim=>$obj){if(empty($obj['applicable']))continue;
  $itemConf=(float)$obj['confidence'];if($itemConf<=0)continue;
  $base=(float)$obj['base_value'];$learn=(float)$obj['learned_value'];$target=0.85*$base+0.15*$learn;
  $old=(float)($kv[$dim]['value']??0.5);$initial=(float)($kv[$dim]['initial_value']??$old);
  $new=clamp($old+$lr*$itemConf*($target-$old)-0.001*($old-$initial));
  $oldConf=(float)($kv[$dim]['confidence']??0);
  sql('INSERT INTO keyword_vectors (keyword_id,dimension_id,initial_value,value,confidence) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value),confidence=VALUES(confidence)',[$kid,$dim,$initial,$new,min(1,$oldConf+0.02*$itemConf)]);
  $newObj=clamp($learn+($lr*0.06)*($old-$learn)-0.008*($learn-$base));
  sql('UPDATE item_vectors SET learned_value=? WHERE item_id=? AND dimension_id=?',[$newObj,$iid,$dim]);
 }
}
function initkeyword(string $term):int{
 $term=norm($term);if($term==='')throw new RuntimeException('Bitte Suchbegriff eingeben.');
 sql('INSERT INTO keywords (term,usage_count) VALUES (?,1) ON DUPLICATE KEY UPDATE usage_count=usage_count+1',[$term]);$id=(int)one('SELECT id FROM keywords WHERE term=?',[$term])['id'];
 if(one('SELECT keyword_id FROM keyword_vectors WHERE keyword_id=? LIMIT 1',[$id]))return $id;
 $items=rows('SELECT id,title,description,tags FROM items ORDER BY id DESC LIMIT 5000');$candidates=[];
 foreach($items as $i){$score=textscore($term,$i);if($score>0)$candidates[(int)$i['id']]=$score;}
 arsort($candidates);$candidates=array_slice($candidates,0,20,true);
 foreach(dims() as $dim){$sum=0;$total=0;
  foreach($candidates as $iid=>$score){$v=one('SELECT base_value,confidence,applicable FROM item_vectors WHERE item_id=? AND dimension_id=?',[$iid,$dim['id']]);if(!$v||!(int)$v['applicable'])continue;$w=$score*(float)$v['confidence'];$sum+=(float)$v['base_value']*$w;$total+=$w;}
  $value=$total>0?$sum/$total:0.5;
  sql('INSERT IGNORE INTO keyword_vectors (keyword_id,dimension_id,initial_value,value,confidence) VALUES (?,?,?,?,?)',[$id,$dim['id'],$value,$value,$total>0?min(0.8,$total/5):0]);
 }
 return $id;
}
function textscore(string $query,array $i):float{
 $query=norm($query);if($query==='')return 0;
 $title=norm((string)$i['title']);$description=norm((string)($i['description']??''));$tags=norm((string)($i['tags']??''));$score=0;
 if($title===$query)$score+=8;
 if(mb_strpos($title,$query)!==false)$score+=4;
 if(mb_strpos($tags,$query)!==false)$score+=3;
 if(mb_strpos($description,$query)!==false)$score+=1.5;
 foreach(preg_split('/\s+/u',$query) as $part){if(mb_strlen($part)<3)continue;if(mb_strpos($title,$part)!==false)$score+=1.2;if(mb_strpos($tags,$part)!==false)$score+=0.8;if(mb_strpos($description,$part)!==false)$score+=0.3;}
 return min(1,$score/8);
}
function rankitems(string $term,int $kid,array $items):array{
 $kv=storedvector('keyword_vectors','value',$kid);$d=dims();$assoc=[];
 foreach(rows("SELECT IF(source_type='item',source_id,target_id) item_id,strength FROM associations WHERE (source_type='item' AND target_type='keyword' AND target_id=?) OR (target_type='item' AND source_type='keyword' AND source_id=?)",[$kid,$kid]) as $a)$assoc[(int)$a['item_id']]=(float)$a['strength'];
 foreach($items as &$i){$iv=storedvector('item_vectors','base_value',(int)$i['id']);$num=0;$den=0;
  foreach($d as $dim){$id=(int)$dim['id'];$x=$iv[$id]??null;$y=$kv[$id]??null;if(!$x||!$y||!(int)$x['applicable'])continue;
   $weight=(float)$dim['weight']*(float)$x['confidence']*(float)$y['confidence'];if($weight<=0)continue;
   $v=0.85*(float)$x['base_value']+0.15*(float)$x['learned_value'];$num+=$weight*($v-(float)$y['value'])**2;$den+=$weight;
  }
  $vec=$den>0?1-sqrt($num/$den):0;
  $txt=textscore($term,$i);$assocScore=min(1,log(1+($assoc[(int)$i['id']]??0))/log(21));
  $i['score']=round(100*(0.5*$vec+0.35*$txt+0.15*$assocScore),1);
  $i['explain']=['vector'=>round(100*$vec),'text'=>round(100*$txt),'association'=>round(100*$assocScore)];
 }
 unset($i);usort($items,fn($a,$b)=>($b['score']<=>$a['score'])?:strcmp($a['title'],$b['title']));return $items;
}
