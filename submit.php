<?php
declare(strict_types=1);
require __DIR__.'/scripts/portal.php';
session_start_safe();
$kind=$_GET['kind']??'event';
if (!is_string($kind)||!in_array($kind,['event','club'],true)) { http_response_code(404);exit; }
$title=$kind==='event'?'Create an Event':'Register My Club';
$errors=[];$values=[];$name='';$email='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    [$values,$errors]=validate_payload($kind,$_POST);
    $name=is_string($_POST['contact_name']??null)?trim($_POST['contact_name']):'';
    $email=is_string($_POST['contact_email']??null)?trim($_POST['contact_email']):'';
    if ($name===''||strlen($name)>160) $errors[]='A valid representative name is required.';
    if (strlen($email)>254||!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='A valid representative email is required.';
    if (($_POST['consent']??'')!=='yes') $errors[]='Please acknowledge the use of your submission details.';
    if (!empty($_POST['company_website'])) $errors[]='Unable to accept the submission.';
    if (time()-(int)($_SESSION['last_submission']??0)<60) $errors[]='Please wait one minute before submitting again.';
    if (!$errors){
        try {
            $q=db()->prepare('INSERT INTO submissions(kind,payload,contact_name,contact_email) VALUES(?,?,?,?)');
            $q->execute([$kind,json_encode($values,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$name,$email]);
            $id=(int)db()->lastInsertId();
            $_SESSION['last_submission']=time();$_SESSION['receipt']=['id'=>$id,'kind'=>$kind];
            try {notify_admin($id,$kind);} catch(Throwable $e){error_log('NIWFF submission notification failed');}
            redirect('/submit.php?kind='.$kind.'&received=1');
        } catch(Throwable $e){error_log('NIWFF submission failed: '.$e->getMessage());$errors[]='The submission service is currently unavailable. Please try later.';}
    }
}
if (isset($_GET['received'])&&($_SESSION['receipt']['kind']??null)===$kind){
    $id=(int)$_SESSION['receipt']['id'];unset($_SESSION['receipt']);
    page('Submission received','<div class="panel"><h2>Thank you</h2><p>Reference #'.$id.' is awaiting NIWFF administration review. Nothing has been published yet. The admin team may contact you for clarification.</p><a class="btn btn-dark" href="/">Return to homepage</a></div>');exit;
}
try {db();} catch(Throwable $e){http_response_code(503);page($title,'<div class="notice">The submission service is being set up. No information can be submitted yet.</div>');exit;}
page($title,'<p>Submit information for review by the NIWFF website administration team. Required fields are marked *.</p>'.($kind==='club'?'<p>Website registration does not itself confer formal federation affiliation.</p>':'').errors_html($errors).
'<form method="post" class="panel portal-form" action="/submit.php?kind='.$kind.'"><input type="hidden" name="csrf" value="'.h(csrf()).'">'.form_fields($kind,$values).
'<h2>Private representative contact</h2><p>These details are used for review and are not published by the directory or calendar.</p><div class="field"><label for="contact_name">Name *</label><input id="contact_name" name="contact_name" required maxlength="160" value="'.h($name).'"></div><div class="field"><label for="contact_email">Email *</label><input type="email" id="contact_email" name="contact_email" required maxlength="254" value="'.h($email).'"></div><div hidden><label for="company_website">Leave empty</label><input name="company_website" id="company_website" tabindex="-1" autocomplete="off"></div><label><input type="checkbox" name="consent" value="yes" required> I understand NIWFF will retain these details to review this request and may contact me. Only approved public fields will be published.</label><p><button class="btn btn-dark" type="submit">Submit for approval</button></p></form>');
