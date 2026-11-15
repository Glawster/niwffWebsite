<?php
declare(strict_types=1);
// CLI only. Default is a read-only preview; --apply inserts pending drafts.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/portal.php';
if(count($argv)>2||(isset($argv[1])&&!in_array($argv[1],['--apply','--dry-run'],true))){fwrite(STDERR,"Usage: php scripts/importLegacyClubs.php [--dry-run|--apply]\n");exit(2);}
$apply=($argv[1]??'')==='--apply';
function legacy_name_key(string $name): string {
    $key=strtolower($name);
    $key=preg_replace('/[^a-z0-9]+/',' ',$key)??$key;
    $key=preg_replace('/\b(?:walking football|football club|football|club|fc|wf|wfc)\b/',' ',$key)??$key;
    return trim(preg_replace('/\s+/',' ',$key)??$key);
}
$seed=json_decode(file_get_contents(dirname(__DIR__).'/data/legacySite/derived/clubImport.json'),true,512,JSON_THROW_ON_ERROR);
$pdo=db();
$lock=false;
try{
    if($apply){
        $lock=(int)$pdo->query("SELECT GET_LOCK('niwff_legacy_club_import',10)")->fetchColumn()===1;
        if(!$lock) throw new RuntimeException('Another import is running.');
        $pdo->beginTransaction();
    }
    $existing=[];
    foreach($pdo->query("SELECT id,payload,status FROM submissions WHERE kind='club'") as $row){
        $p=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
        $existing[]=['id'=>$row['id'],'name'=>$p['name']??'','legacy_id'=>$p['_legacy_import']['id']??'','status'=>$row['status']];
    }
    $insert=$pdo->prepare("INSERT INTO submissions(kind,status,payload,contact_name,contact_email,reviewer_note) VALUES('club','pending',?,?,?,?)");
    $count=0;$skipped=0;
    foreach($seed as $item){
        $p=$item['payload'];$key=legacy_name_key($p['name']);$matches=[];
        foreach($existing as $e){
            $other=legacy_name_key($e['name']);
            if($e['legacy_id']===$item['legacy_id']||($key!==''&&$other!==''&&($key===$other||str_starts_with($key.' ',$other.' ')||str_starts_with($other.' ',$key.' ')))) $matches[]='#'.$e['id'].' '.$e['name'].' ('.$e['status'].')';
        }
        if($matches){echo 'SKIP '.$p['name'].' — possible existing record: '.implode(', ',$matches).PHP_EOL;$skipped++;continue;}
        // Blank required fields are intentionally allowed only for imported drafts.
        $payload=[];foreach(fields('club') as $field=>$spec)$payload[$field]=(string)($p[$field]??'');
        $payload['_legacy_import']=['id'=>$item['legacy_id'],'source'=>$item['source'],'captured'=>$item['captured']];
        $missing=[];foreach(fields('club') as $field=>[$label,$type,$required])if($required&&$payload[$field]==='')$missing[]=$label;
        $note='Imported historical listing; NOT verified. Source: '.$item['source'].' ('.$item['captured'].'). '.$item['review_note'].($missing?' Missing required fields: '.implode(', ',$missing).'.':'');
        // No old personal contact details or invented email addresses; no notification is sent.
        if($apply){
            $insert->execute([json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'','',$note]);
            $id=(int)$pdo->lastInsertId();
            $existing[]=['id'=>$id,'name'=>$p['name'],'legacy_id'=>$item['legacy_id'],'status'=>'pending'];
            echo 'INSERT #'.$id.' ';
        }else echo 'WOULD INSERT ';
        echo $p['name'].($missing?' — missing: '.implode(', ',$missing):'').PHP_EOL;$count++;
    }
    if($apply)$pdo->commit();
    echo ($apply?'Inserted ':'Would insert ').$count.' pending drafts; skipped '.$skipped.'. No records published or emails sent.'.PHP_EOL;
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
finally{if($lock)$pdo->query("SELECT RELEASE_LOCK('niwff_legacy_club_import')");}
