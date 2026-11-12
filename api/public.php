<?php
declare(strict_types=1);
require dirname(__DIR__).'/scripts/portal.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try{
    $rows=db()->query("SELECT id,kind,payload FROM submissions WHERE status='approved' AND published_at IS NOT NULL ORDER BY id DESC");
    $result=['clubs'=>[],'events'=>[]];
    foreach($rows as $row){
        $p=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
        // Explicit public allow-list; never expose representative contacts, notes or login information.
        $p=array_intersect_key($p,fields($row['kind']));
        $p['id']=(int)$row['id'];$p['approved']=true;
        $result[$row['kind']==='club'?'clubs':'events'][]=$p;
    }
    echo json_encode($result,JSON_THROW_ON_ERROR|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
}catch(Throwable $e){http_response_code(503);echo '{"error":"Publication service unavailable"}';}
