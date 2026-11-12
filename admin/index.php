<?php
declare(strict_types=1);
require dirname(__DIR__).'/scripts/portal.php';
session_start_safe();
try { $pdo=db(); } catch(Throwable $e){http_response_code(503);page('Administration','<div class="notice">Database not configured.</div>');exit;}
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    $action=$_POST['action']??'';
    if ($action==='login'){
        if (time() < (int)($_SESSION['login_wait']??0)) $errors[]='Please wait before trying again.';
        else {
            $user=is_string($_POST['username']??null)?substr(trim($_POST['username']),0,100):'';
            $pass=is_string($_POST['password']??null)?substr($_POST['password'],0,1000):'';
            $q=$pdo->prepare('SELECT id,password_hash FROM admins WHERE username=? AND active=1');
            $q->execute([$user]);$account=$q->fetch();
            if ($account && password_verify($pass,$account['password_hash'])){
                session_regenerate_id(true);$_SESSION=['admin_id'=>(int)$account['id'],'last_seen'=>time()];redirect('/admin/');
            }
            $_SESSION['login_wait']=time()+5;$errors[]='Invalid username or password.';
        }
    } elseif ($action==='logout'){
        $_SESSION=[];session_destroy();redirect('/admin/');
    } else {
        $who=admin();
        if (!$who){http_response_code(403);exit('Administrator login required.');}
        $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
        $new=$_POST['status']??'';
        $note=is_string($_POST['note']??null)?trim($_POST['note']):'';
        if ($action!=='review'||!$id||!in_array($new,['pending','approved','rejected','changes_requested','withdrawn'],true)||strlen($note)>4000){http_response_code(400);exit('Invalid review request.');}
        try{
            $pdo->beginTransaction();
            $q=$pdo->prepare('SELECT * FROM submissions WHERE id=? FOR UPDATE');$q->execute([$id]);$row=$q->fetch();
            if (!$row) throw new RuntimeException('Submission not found.');
            [$payload,$validation]=validate_payload($row['kind'],$_POST);
            if ($validation){$pdo->rollBack();$errors=$validation;}
            else{
                $q=$pdo->prepare("UPDATE submissions SET payload=?,status=?,reviewer_note=?,reviewed_by=?,published_at=CASE WHEN ?='approved' THEN COALESCE(published_at,UTC_TIMESTAMP()) ELSE NULL END WHERE id=?");
                $q->execute([json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$new,$note,$who['id'],$new,$id]);
                $q=$pdo->prepare('INSERT INTO review_log(submission_id,admin_id,old_status,new_status,note) VALUES(?,?,?,?,?)');
                $q->execute([$id,$who['id'],$row['status'],$new,$note]);$pdo->commit();redirect('/admin/?id='.$id.'&saved=1');
            }
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('NIWFF review error: '.$e->getMessage());$errors[]='Review could not be saved.';}
    }
}
$who=admin();
if (!$who){
    page('Administrator sign in',errors_html($errors).'<form method="post" class="panel portal-form"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="action" value="login"><div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required></div><div class="field"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" required></div><button class="btn btn-dark">Sign in</button></form>');exit;
}
$top='<div class="button-row"><a class="btn btn-outline" href="/admin/">Review queue</a><form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="action" value="logout"><button class="btn btn-outline">Sign out</button></form></div><p>Signed in as '.h($who['username']).'</p>';
$id=filter_var($_GET['id']??($_POST['id']??null),FILTER_VALIDATE_INT);
if($id){
    $q=$pdo->prepare('SELECT * FROM submissions WHERE id=?');$q->execute([$id]);$row=$q->fetch();
    if(!$row){http_response_code(404);page('Not found',$top);exit;}
    $payload=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
    if($errors&&isset($_POST['action'])&&$_POST['action']==='review')[$payload,]=validate_payload($row['kind'],$_POST);
    $options='';foreach(['pending','approved','rejected','changes_requested','withdrawn'] as $status)$options.='<option value="'.$status.'"'.($row['status']===$status?' selected':'').'>'.h(str_replace('_',' ',$status)).'</option>';
    $history=$pdo->prepare('SELECT l.*,a.username FROM review_log l JOIN admins a ON a.id=l.admin_id WHERE submission_id=? ORDER BY l.id DESC');$history->execute([$id]);$log='';
    foreach($history as $item)$log.='<li>'.h($item['created_at'].' UTC — '.$item['username'].': '.$item['old_status'].' → '.$item['new_status'].' — '.$item['note']).'</li>';
    page('Review #'.$id,$top.(isset($_GET['saved'])?'<p role="status">Review saved.</p>':'').errors_html($errors).'<div class="panel"><p><strong>Private representative:</strong> '.h($row['contact_name']).' — <a href="mailto:'.h($row['contact_email']).'">'.h($row['contact_email']).'</a></p><p>Status: '.h($row['status']).'</p></div><form method="post" class="panel portal-form"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="action" value="review"><input type="hidden" name="id" value="'.$id.'">'.form_fields($row['kind'],$payload).'<div class="field"><label for="status">Review decision</label><select id="status" name="status">'.$options.'</select></div><div class="field"><label for="note">Internal review note</label><textarea id="note" name="note" maxlength="4000" rows="4">'.h($row['reviewer_note']??'').'</textarea></div><button class="btn btn-dark">Save decision</button></form><h2>Review history</h2><ul>'.$log.'</ul>');exit;
}
$list=$pdo->query("SELECT id,kind,status,payload,created_at FROM submissions ORDER BY (status='pending') DESC,id DESC LIMIT 500");
$rows='';foreach($list as $row){$p=json_decode($row['payload'],true);$rows.='<tr><td><a href="/admin/?id='.(int)$row['id'].'">#'.(int)$row['id'].' '.h($p['title']??$p['name']??'Submission').'</a></td><td>'.h($row['kind']).'</td><td>'.h($row['status']).'</td><td>'.h($row['created_at']).'</td></tr>';}
page('NIWFF Administration',$top.'<p>Pending items are listed first. Only approved items are visible to the public. Select a submission to edit, approve, reject, request changes or withdraw it.</p><div class="panel table-scroll"><table class="review-table"><thead><tr><th>Submission</th><th>Type</th><th>Status</th><th>Received (UTC)</th></tr></thead><tbody>'.$rows.'</tbody></table>'.($rows?'':'<p>No submissions yet.</p>').'</div>');
