<?php
declare(strict_types=1);
require dirname(__DIR__).'/scripts/portal.php';
session_start_safe();
try { $pdo=db(); } catch(Throwable $e){http_response_code(503);page('Administration','<div class="notice">Database not configured.</div>');exit;}
$errors=[];
$view=$_GET['view']??($_POST['view']??'new');
if (!is_string($view) || !in_array($view,['new','old_club','old_event','clubs','events'],true)) $view='new';
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
        $message=is_string($_POST['message']??null)?trim($_POST['message']):'';
        if (!in_array($action,['review','maintain'],true)||!$id||!in_array($new,['pending','approved','rejected','changes_requested','withdrawn'],true)||strlen($note)>4000||strlen($message)>4000){http_response_code(400);exit('Invalid review request.');}
        try{
            $pdo->beginTransaction();
            $q=$pdo->prepare('SELECT * FROM submissions WHERE id=? FOR UPDATE');$q->execute([$id]);$row=$q->fetch();
            if (!$row) throw new RuntimeException('Submission not found.');
            if ($action==='maintain' && (!in_array($row['status'],['approved','withdrawn'],true) || !in_array($new,['approved','withdrawn'],true) || $row['kind']!==($view==='clubs'?'club':($view==='events'?'event':'')))) throw new RuntimeException('Invalid maintenance record or decision.');
            if ($action==='review' && (in_array($view,['clubs','events'],true) || in_array($row['status'],['approved','withdrawn'],true))) throw new RuntimeException('Use record maintenance for approved or withdrawn records.');
            [$payload,$validation]=validate_payload($row['kind'],$_POST);
            $original=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
            $imported=isset($original['_legacy_import']);
            if($imported){
                $payload['_legacy_import']=$original['_legacy_import'];
                $name=is_string($_POST['legacy_contact_name']??null)?trim($_POST['legacy_contact_name']):$row['contact_name'];
                $email=is_string($_POST['legacy_contact_email']??null)?trim($_POST['legacy_contact_email']):$row['contact_email'];
                if(strlen($name)>160||strlen($email)>254||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))) $validation[]='Enter valid verified representative details.';
                if($new==='changes_requested'&&($name===''||$email==='')) $validation[]='Add a verified representative name and email before requesting changes.';
                if($new==='pending'||$new==='rejected') $validation=array_values(array_filter($validation,fn($e)=>!str_ends_with($e,' is required.')));
            }
            if($new==='changes_requested'&&$message==='') $validation[]='Enter a message explaining the requested changes.';
            if ($validation){$pdo->rollBack();$errors=$validation;}
            else{
                save_revision($pdo,$id,'before_admin_update',$row['payload']);
                $amendUrl=$new==='changes_requested'?issue_amendment($pdo,$id,$message):'';
                if($new!=='changes_requested') revoke_amendment($pdo,$id);
                $q=$pdo->prepare("UPDATE submissions SET payload=?,status=?,reviewer_note=?,reviewed_by=?,published_at=CASE WHEN ?='approved' THEN COALESCE(published_at,UTC_TIMESTAMP()) ELSE NULL END WHERE id=?");
                $q->execute([json_encode($payload,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$new,$action==='maintain'?($row['reviewer_note']??null):$note,$who['id'],$new,$id]);
                if($imported){
                    $q=$pdo->prepare('UPDATE submissions SET contact_name=?,contact_email=? WHERE id=?');
                    $q->execute([$name,$email,$id]);
                    $row['contact_name']=$name;$row['contact_email']=$email;
                }
                $historyNote=($note!==''?'Internal note: '.$note."\n":'').($message!==''?'Message to submitter: '.$message."\n":'').($action==='maintain'?'Record maintenance (no submission email).':'Notification pending.');
                $q=$pdo->prepare('INSERT INTO review_log(submission_id,admin_id,old_status,new_status,note) VALUES(?,?,?,?,?)');
                $q->execute([$id,$who['id'],$row['status'],$new,$historyNote]);$logId=(int)$pdo->lastInsertId();$pdo->commit();
                if($action==='review'){
                    try{
                        $emailAudit=notify_submitter_review($row,$new,$message,$amendUrl);
                        $q=$pdo->prepare('UPDATE review_log SET note=? WHERE id=?');
                        $q->execute([str_replace('Notification pending.',$emailAudit,$historyNote),$logId]);
                    }catch(Throwable $e){error_log('NIWFF review notification failed: '.$e->getMessage());}
                }
                redirect('/admin/?id='.$id.'&saved=1&view='.rawurlencode($new==='approved'||$new==='withdrawn'?($row['kind']==='club'?'clubs':'events'):$view));
            }
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('NIWFF review error: '.$e->getMessage());$errors[]='Review could not be saved.';}
    }
}
$who=admin();
if (!$who){
    page('Administrator Sign In',errors_html($errors).'<form method="post" class="panel portal-form"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="action" value="login"><div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required></div><div class="field"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" required></div><button class="btn btn-dark">Sign in</button></form>');exit;
}
$maintenance=in_array($view,['clubs','events'],true);
$nav=[['new','Submission Review'],['clubs','Club Maintenance'],['events','Event Maintenance']];
$top='<nav class="button-row" aria-label="Administration functions">';
foreach($nav as [$key,$label]){
    $active=$key==='new'?!$maintenance:$view===$key;
    $top.='<a class="btn '.($active?'btn-dark':'btn-outline').'" href="/admin/?view='.$key.'"'.($active?' aria-current="page"':'').'>'.$label.'</a>';
}
$top.='<form method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="action" value="logout"><button class="btn btn-outline">Sign out</button></form></nav><p>Signed in as '.h($who['username']).'</p>';
if(!$maintenance){
    $top.='<nav class="button-row" aria-label="Submission filters">';
    foreach([['new','Active Submissions'],['old_club','Previous Club Requests'],['old_event','Previous Event Requests']] as [$key,$label]) $top.='<a class="btn '.($view===$key?'btn-dark':'btn-outline').'" href="/admin/?view='.$key.'"'.($view===$key?' aria-current="page"':'').'>'.$label.'</a>';
    $top.='</nav>';
}
$id=filter_var($_GET['id']??($_POST['id']??null),FILTER_VALIDATE_INT);
if($id){
    $q=$pdo->prepare('SELECT * FROM submissions WHERE id=?');$q->execute([$id]);$row=$q->fetch();
    if(!$row||($maintenance&&($row['kind']!==($view==='clubs'?'club':'event')||!in_array($row['status'],['approved','withdrawn'],true)))){http_response_code(404);page('Not Found',$top);exit;}
    if(!$maintenance&&in_array($row['status'],['approved','withdrawn'],true)) redirect('/admin/?view='.($row['kind']==='club'?'clubs':'events').'&id='.$id);
    $payload=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
    if($errors&&isset($_POST['action'])&&in_array($_POST['action'],['review','maintain'],true)){
        $legacy=$payload['_legacy_import']??null;
        [$payload,]=validate_payload($row['kind'],$_POST);
        if($legacy!==null)$payload['_legacy_import']=$legacy;
    }
    $options='';foreach(($maintenance?['approved','withdrawn']:['pending','approved','rejected','changes_requested','withdrawn']) as $status)$options.='<option value="'.$status.'"'.($row['status']===$status?' selected':'').'>'.h(str_replace('_',' ',$status)).'</option>';
    $history=$pdo->prepare('SELECT l.*,a.username FROM review_log l JOIN admins a ON a.id=l.admin_id WHERE submission_id=? ORDER BY l.id DESC');$history->execute([$id]);$log='';
    foreach($history as $item){
        $historyNote=str_replace(["\r\n","\n","\r"],' | ',(string)$item['note']);
        $log.='<li>'.h($item['created_at'].' UTC — '.$item['username'].': '.$item['old_status'].' → '.$item['new_status'].' — '.$historyNote).'</li>';
    }
    $revisions=$pdo->prepare('SELECT actor,payload,created_at FROM submission_revisions WHERE submission_id=? ORDER BY id DESC');$revisions->execute([$id]);$versions='';
    foreach($revisions as $revision){
        $previous=json_decode($revision['payload'],true,512,JSON_THROW_ON_ERROR);$changes='';
        foreach(fields($row['kind']) as $key=>[$label]){
            if(($previous[$key]??'')!==($payload[$key]??'')) $changes.='<li><strong>'.h($label).':</strong> '.h((string)($previous[$key]??'')).' → '.h((string)($payload[$key]??'')).'</li>';
        }
        $versions.='<details><summary>'.h($revision['created_at'].' UTC — '.str_replace('_',' ',$revision['actor'])).'</summary>'.($changes?'<ul>'.$changes.'</ul>':'<p>No differences from current public fields.</p>').'</details>';
    }
    $messageDefault='';
    if(!$maintenance&&$row['status']==='changes_requested'){
        $q=$pdo->prepare('SELECT message FROM submission_amendments WHERE submission_id=?');$q->execute([$id]);$messageDefault=(string)($q->fetchColumn()?:'');
    }
    $importInfo='';
    if(isset($payload['_legacy_import'])){
        $importInfo='<div class="notice">Historical import from '.h((string)$payload['_legacy_import']['captured']).'. Verify details before approval.</div>';
    }
    $importContact=isset($payload['_legacy_import'])?'<h2>Verified representative (private)</h2><div class="field"><label for="legacy_contact_name">Name</label><input id="legacy_contact_name" name="legacy_contact_name" maxlength="160" value="'.h($row['contact_name']).'"></div><div class="field"><label for="legacy_contact_email">Email</label><input type="email" id="legacy_contact_email" name="legacy_contact_email" maxlength="254" value="'.h($row['contact_email']).'"></div>':'';
    $recordTitle=$maintenance?($row['kind']==='club'?'Club Record: ':'Event Record: ').($payload['name']??$payload['title']??('#'.$id)):'Review #'.$id;
    $publicLink=$maintenance&&$row['status']==='approved'?'<p><a href="'.($row['kind']==='club'?'/find-a-club/?club=approved-'.$id:'/competitions/').'">View published '.h($row['kind']).' →</a></p>':'';
    page($recordTitle,$top.$publicLink.$importInfo.(isset($_GET['saved'])?'<p role="status">Changes Saved.</p>':'').errors_html($errors).'<div class="panel"><p><strong>Contact:</strong> '.h($row['contact_name']).' — <a href="mailto:'.h($row['contact_email']).'">'.h($row['contact_email']).'</a>'.(!empty($row['contact_phone'])?' — '.h((string)$row['contact_phone']):'').'</p><p>Status: '.h($row['status']).'</p></div><form method="post" class="panel portal-form"><input type="hidden" name="csrf" value="'.h(csrf()).'"><input type="hidden" name="action" value="'.($maintenance?'maintain':'review').'"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="view" value="'.h($view).'">'.$importContact.form_fields($row['kind'],$payload).'<div class="field"><label for="status">'.($maintenance?'Publication Status':'Review Decision').'</label><select id="status" name="status">'.$options.'</select></div>'.(!$maintenance?'<div class="field"><label for="message">Message to Submitter (required for Changes Requested)</label><textarea id="message" name="message" maxlength="4000" rows="4">'.h(is_string($_POST['message']??null)?$_POST['message']:$messageDefault).'</textarea><p>Only this message is emailed. Saving Changes Requested again issues a fresh link and invalidates the previous one.</p></div>':'').'<div class="field"><label for="note">'.($maintenance?'Internal Maintenance Note':'Internal Review Note').'</label><textarea id="note" name="note" maxlength="4000" rows="4">'.h($maintenance?'':($row['reviewer_note']??'')).'</textarea></div><button class="btn btn-dark">'.($maintenance?'Save record':'Save decision').'</button></form><h2>Review and Maintenance History</h2><ul>'.$log.'</ul><h2>Previous Versions</h2>'.($versions?:'<p>No previous versions yet.</p>'));exit;
}
if ($maintenance) {
    $kind=$view==='clubs'?'club':'event';
    $list=$pdo->prepare("SELECT id,kind,status,payload,created_at FROM submissions WHERE kind=? AND status IN ('approved','withdrawn') ORDER BY id DESC LIMIT 500");
    $list->execute([$kind]);
    $listTitle=$view==='clubs'?'Club Maintenance':'Event Maintenance';
    $listIntro='Manage approved and withdrawn records. Changes to approved records are published immediately; maintenance changes are audit logged.';
    $empty='No approved or withdrawn records found.';
} elseif ($view==='old_club') {
    $list=$pdo->query("SELECT id,kind,status,payload,created_at FROM submissions WHERE kind='club' AND status NOT IN ('pending','changes_requested') ORDER BY id DESC LIMIT 500");
    $listTitle='Club Submission History';
    $listIntro='Showing older club submissions (excluding pending and changes requested).';
    $empty='No older club submissions found.';
} elseif ($view==='old_event') {
    $list=$pdo->query("SELECT id,kind,status,payload,created_at FROM submissions WHERE kind='event' AND status NOT IN ('pending','changes_requested') ORDER BY id DESC LIMIT 500");
    $listTitle='Event Submission History';
    $listIntro='Showing older event submissions (excluding pending and changes requested).';
    $empty='No older event submissions found.';
} else {
    $list=$pdo->query("SELECT id,kind,status,payload,created_at FROM submissions WHERE status IN ('pending','changes_requested') ORDER BY id DESC LIMIT 500");
    $listTitle='Submission Review';
    $listIntro='Showing active submissions only (new and changes requested). Approved and older reviewed submissions are hidden by default.';
    $empty='No new submissions at the moment.';
}
$rows='';foreach($list as $row){$p=json_decode($row['payload'],true);$rows.='<tr><td><a href="/admin/?id='.(int)$row['id'].'&view='.rawurlencode($view).'">#'.(int)$row['id'].' '.h($p['title']??$p['name']??'Submission').'</a></td><td>'.h($row['kind']).'</td><td>'.h($row['status']).'</td><td>'.h($row['created_at']).'</td></tr>';}
page($listTitle,$top.'<p>'.$listIntro.'</p><div class="panel table-scroll"><table class="review-table"><thead><tr><th>'.($maintenance?'Record':'Submission').'</th><th>Type</th><th>Status</th><th>Received (UTC)</th></tr></thead><tbody>'.$rows.'</tbody></table>'.($rows?'':'<p>'.$empty.'</p>').'</div>');
