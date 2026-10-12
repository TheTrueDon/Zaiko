<?php
function ai_vectors(array $item,array $dimensions):array{
 $key=getenv('OPENAI_API_KEY')?:setting('ai_key');$model=setting('ai_model','gpt-4o-mini');
 if(!$key)throw new RuntimeException('Kein KI-Schlüssel eingerichtet (Admin → Einstellungen oder OPENAI_API_KEY).');
 if(!function_exists('curl_init'))throw new RuntimeException('PHP-cURL-Erweiterung fehlt.');
 $prompt="Bewerte dieses Inventarobjekt anhand jeder Dimension mit Wert 0 bis 1, confidence 0 bis 1 und applicable als true/false. Unbekannte Eigenschaften confidence=0. Antworte NUR mit JSON-Objekt {\"vectors\":[{\"id\":123,\"value\":0.5,\"confidence\":0.8,\"applicable\":true}]} und allen ids.\nObjekt: ".json_encode(['title'=>$item['title'],'description'=>$item['description'],'tags'=>$item['tags'],'category'=>$item['category']],JSON_UNESCAPED_UNICODE)."\nDimensionen: ".json_encode(array_map(fn($d)=>['id'=>(int)$d['id'],'name'=>$d['name'],'definition'=>$d['description'],'min'=>$d['low_label'],'max'=>$d['high_label']],$dimensions),JSON_UNESCAPED_UNICODE);
 $payload=['model'=>$model,'temperature'=>0.1,'response_format'=>['type'=>'json_object'],'messages'=>[['role'=>'system','content'=>'Du klassifizierst Lernwerkstatt-Inventar nach benutzerdefinierten Dimensionen. Erfinde keine Fakten. Gib ausschließlich korrektes JSON zurück.'],['role'=>'user','content'=>$prompt]]];
 $ch=curl_init('https://api.openai.com/v1/chat/completions');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$key,'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>55]);$raw=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
 if($raw===false||$code>=300)throw new RuntimeException('KI-Anfrage fehlgeschlagen (HTTP '.$code.'): '.mb_substr($err?:$raw,0,220));
 $resp=json_decode($raw,true);$parsed=json_decode($resp['choices'][0]['message']['content']??'',true);
 if(!is_array($parsed['vectors']??null))throw new RuntimeException('KI lieferte keine gültigen Dimensionswerte.');
 $known=array_fill_keys(array_map(fn($d)=>(int)$d['id'],$dimensions),true);$result=[];
 foreach($parsed['vectors'] as $v){$id=(int)($v['id']??0);if(!isset($known[$id]))continue;$result[$id]=['value'=>clamp($v['value']??0.5),'confidence'=>clamp($v['confidence']??0),'applicable'=>!empty($v['applicable'])];}
 return $result;
}
