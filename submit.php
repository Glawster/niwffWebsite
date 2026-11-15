<?php
declare(strict_types=1);
require __DIR__.'/scripts/portal.php';
session_start_safe();
header('Referrer-Policy: no-referrer');
if(isset($_GET['amend'])){
    $token=$_GET['amend'];
    unset($_SESSION['amendment_hash']);
    if(is_string($token)&&preg_match('/^[a-f0-9]{64}$/D',$token)){
        $_SESSION['amendment_hash']=hash('sha256',$token);
        redirect('/submit.php?edit=1');
    }
    http_response_code(404);page('Amendment Link Unavailable','<p>Please ask NIWFF to issue a new amendment link.</p>');exit;
}
$editing=isset($_GET['edit']);
$amendment=null;
if($editing){
    try{
        $hash=$_SESSION['amendment_hash']??'';
        $pdo=db();
        $q=$pdo->prepare("SELECT s.*,a.message FROM submissions s JOIN submission_amendments a ON a.submission_id=s.id WHERE a.token_hash=? AND a.expires_at>UTC_TIMESTAMP() AND s.status='changes_requested'");
        $q->execute([$hash]);$amendment=$q->fetch();
    }catch(Throwable $e){error_log('NIWFF amendment lookup failed');}
    if(!$amendment){unset($_SESSION['amendment_hash']);http_response_code(404);page('Amendment Link Unavailable','<p>This link has expired, already been used or is no longer valid. Please ask NIWFF for a new link if you still need to make changes.</p>');exit;}
}
$kind=$editing?$amendment['kind']:($_GET['kind']??'event');
if (!is_string($kind)||!in_array($kind,['event','club'],true)) { http_response_code(404);exit; }
$title=$editing?'Amend Submission #'.(int)$amendment['id']:($kind==='event'?'Create An Event':'Register My Club');
$errors=[];$values=$editing?json_decode($amendment['payload'],true,512,JSON_THROW_ON_ERROR):[];$name=$editing?$amendment['contact_name']:'';$email=$editing?$amendment['contact_email']:'';$phone=$editing?($amendment['contact_phone']??''):'';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    if($editing&&(string)($amendment['id'])!==($_POST['amend_id']??null)){http_response_code(409);page('Amendment Link Changed','<p>Reopen the original email link and try again.</p>');exit;}
    [$values,$errors]=validate_payload($kind,$_POST);
    if(!$editing){
    $name=is_string($_POST['contact_name']??null)?trim($_POST['contact_name']):'';
    $email=is_string($_POST['contact_email']??null)?trim($_POST['contact_email']):'';
    $phone=is_string($_POST['contact_phone']??null)?trim($_POST['contact_phone']):'';
    }
    if ($name===''||strlen($name)>160) $errors[]='A Valid Representative Name Is Required.';
    if (strlen($email)>254||!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='A Valid Representative Email Is Required.';
    if ($phone!=='' && (!preg_match('/^[0-9+().\-\s]{7,40}$/',$phone))) $errors[]='A Valid Phone Number Is Required.';
    if (($_POST['consent']??'')!=='yes') $errors[]='Please Acknowledge the Use of Your Submission Details.';
    if (!empty($_POST['company_website'])) $errors[]='Unable to accept the submission.';
    if (!$editing&&time()-(int)($_SESSION['last_submission']??0)<60) $errors[]='Please Wait One Minute Before Submitting Again.';
    if (!$errors){
        try {
            $payloadJson = json_encode($values,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
            $pdo=db();
            if($editing){
                $pdo->beginTransaction();
                $id=(int)$amendment['id'];
                $q=$pdo->prepare("SELECT * FROM submissions WHERE id=? FOR UPDATE");$q->execute([$id]);$current=$q->fetch();
                $q=$pdo->prepare("SELECT token_hash FROM submission_amendments WHERE submission_id=? AND expires_at>UTC_TIMESTAMP() FOR UPDATE");$q->execute([$id]);$live=$q->fetch();
                if(!$current||$current['status']!=='changes_requested'||!$live||!hash_equals($live['token_hash'],(string)($_SESSION['amendment_hash']??''))) throw new RuntimeException('Amendment is no longer valid.');
                save_revision($pdo,$id,'before_submitter_update',$current['payload']);
                $q=$pdo->prepare("UPDATE submissions SET payload=?,status='pending',published_at=NULL WHERE id=?");
                $q->execute([$payloadJson,$id]);
                revoke_amendment($pdo,$id);
                $pdo->commit();unset($_SESSION['amendment_hash']);
                $_SESSION['receipt']=['id'=>$id,'kind'=>$kind];
                try{notify_admin($id,$kind,true);}catch(Throwable $e){error_log('NIWFF amendment notification failed');}
                redirect('/submit.php?kind='.$kind.'&received=1');
            }
            if (submissions_has_contact_phone($pdo)) {
                $q=$pdo->prepare('INSERT INTO submissions(kind,payload,contact_name,contact_email,contact_phone) VALUES(?,?,?,?,?)');
                $q->execute([$kind,$payloadJson,$name,$email,$phone]);
            } else {
                $q=$pdo->prepare('INSERT INTO submissions(kind,payload,contact_name,contact_email) VALUES(?,?,?,?)');
                $q->execute([$kind,$payloadJson,$name,$email]);
            }
            $id=(int)db()->lastInsertId();
            $_SESSION['last_submission']=time();$_SESSION['receipt']=['id'=>$id,'kind'=>$kind];
            try {notify_admin($id,$kind);} catch(Throwable $e){error_log('NIWFF submission notification failed');}
            redirect('/submit.php?kind='.$kind.'&received=1');
        } catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log('NIWFF submission failed: '.$e->getMessage());$errors[]='The submission service is currently unavailable. Please try later.';}
    }
}
if (isset($_GET['received'])&&($_SESSION['receipt']['kind']??null)===$kind){
    $id=(int)$_SESSION['receipt']['id'];unset($_SESSION['receipt']);
    page('Submission Received','<div class="panel"><h2>Thank You</h2><p>Reference #'.$id.' is awaiting NIWFF administration review. Nothing has been published yet. The admin team may contact you for clarification.</p><a class="btn btn-dark" href="/">Return to Homepage</a></div>');exit;
}
try {db();} catch(Throwable $e){http_response_code(503);page($title,'<div class="notice">The submission service is being set up. No information can be submitted yet.</div>');exit;}
page($title,($editing?'<div class="notice"><strong>Changes requested</strong><p>'.nl2br(h($amendment['message'])).'</p><p>Update your existing details below; your original reference will be retained.</p></div>':'').'<p>Submit Information for Review by the NIWFF Website Administration Team. Required Fields Are Marked *.</p>'.($kind==='club'?'<p>Website Registration Does Not Itself Confer Formal Federation Affiliation.</p>':'').errors_html($errors).
'<form method="post" class="panel portal-form" action="/submit.php?'.($editing?'edit=1':'kind='.$kind).'"><input type="hidden" name="csrf" value="'.h(csrf()).'">'.($editing?'<input type="hidden" name="amend_id" value="'.(int)$amendment['id'].'">':'').form_fields($kind,$values).
'<h2>Contact Details</h2><p>These Details Are Used for Review and Are Not Published by the Directory or Calendar.</p><div class="field"><label for="contact_name">Name *</label><input id="contact_name" name="contact_name" '.($editing?'readonly ':'').'required maxlength="160" value="'.h($name).'"></div><div class="field"><label for="contact_email">Email *</label><input type="email" id="contact_email" name="contact_email" '.($editing?'readonly ':'').'required maxlength="254" value="'.h($email).'"></div><div class="field"><label for="contact_phone">Phone Number</label><input type="tel" id="contact_phone" name="contact_phone" '.($editing?'readonly ':'').'maxlength="40" value="'.h($phone).'"></div><div hidden><label for="company_website">Leave Empty</label><input name="company_website" id="company_website" tabindex="-1" autocomplete="off"></div><label><input type="checkbox" name="consent" value="yes" required> I Understand NIWFF Will Retain These Details to Review This Request and May Contact Me. Only Approved Public Fields Will Be Published.</label><p><button class="btn btn-dark" type="submit">'.($editing?'Resubmit for Review':'Submit For Approval').'</button></p></form>');
