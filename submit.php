<?php
declare(strict_types=1);
require __DIR__.'/scripts/portal.php';
session_start_safe();
$kind=$_GET['kind']??'event';
if (!is_string($kind)||!in_array($kind,['event','club'],true)) { http_response_code(404);exit; }
$title=$kind==='event'?'Create An Event':'Register My Club';
$errors=[];$values=[];$name='';$email='';$phone='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    [$values,$errors]=validate_payload($kind,$_POST);
    $name=is_string($_POST['contact_name']??null)?trim($_POST['contact_name']):'';
    $email=is_string($_POST['contact_email']??null)?trim($_POST['contact_email']):'';
    $phone=is_string($_POST['contact_phone']??null)?trim($_POST['contact_phone']):'';
    if ($name===''||strlen($name)>160) $errors[]='A Valid Representative Name Is Required.';
    if (strlen($email)>254||!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='A Valid Representative Email Is Required.';
    if ($phone!=='' && (!preg_match('/^[0-9+().\-\s]{7,40}$/',$phone))) $errors[]='A Valid Phone Number Is Required.';
    if (($_POST['consent']??'')!=='yes') $errors[]='Please Acknowledge the Use of Your Submission Details.';
    if (!empty($_POST['company_website'])) $errors[]='Unable to accept the submission.';
    if (time()-(int)($_SESSION['last_submission']??0)<60) $errors[]='Please Wait One Minute Before Submitting Again.';
    if (!$errors){
        try {
            $payloadJson = json_encode($values,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
            $pdo=db();
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
        } catch(Throwable $e){error_log('NIWFF submission failed: '.$e->getMessage());$errors[]='The submission service is currently unavailable. Please try later.';}
    }
}
if (isset($_GET['received'])&&($_SESSION['receipt']['kind']??null)===$kind){
    $id=(int)$_SESSION['receipt']['id'];unset($_SESSION['receipt']);
    page('Submission Received','<div class="panel"><h2>Thank You</h2><p>Reference #'.$id.' is awaiting NIWFF administration review. Nothing has been published yet. The admin team may contact you for clarification.</p><a class="btn btn-dark" href="/">Return to Homepage</a></div>');exit;
}
try {db();} catch(Throwable $e){http_response_code(503);page($title,'<div class="notice">The submission service is being set up. No information can be submitted yet.</div>');exit;}
page($title,'<p>Submit Information for Review by the NIWFF Website Administration Team. Required Fields Are Marked *.</p>'.($kind==='club'?'<p>Website Registration Does Not Itself Confer Formal Federation Affiliation.</p>':'').errors_html($errors).
'<form method="post" class="panel portal-form" action="/submit.php?kind='.$kind.'"><input type="hidden" name="csrf" value="'.h(csrf()).'">'.form_fields($kind,$values).
'<h2>Private Representative Contact</h2><p>These Details Are Used for Review and Are Not Published by the Directory or Calendar.</p><div class="field"><label for="contact_name">Name *</label><input id="contact_name" name="contact_name" required maxlength="160" value="'.h($name).'"></div><div class="field"><label for="contact_email">Email *</label><input type="email" id="contact_email" name="contact_email" required maxlength="254" value="'.h($email).'"></div><div class="field"><label for="contact_phone">Phone Number</label><input type="tel" id="contact_phone" name="contact_phone" maxlength="40" value="'.h($phone).'"></div><div hidden><label for="company_website">Leave Empty</label><input name="company_website" id="company_website" tabindex="-1" autocomplete="off"></div><label><input type="checkbox" name="consent" value="yes" required> I Understand NIWFF Will Retain These Details to Review This Request and May Contact Me. Only Approved Public Fields Will Be Published.</label><p><button class="btn btn-dark" type="submit">Submit For Approval</button></p></form>');
