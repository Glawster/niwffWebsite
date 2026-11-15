<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $cfg = config();
    $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}
function config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    // Repository is ~/public_html/stagingniwf...; configuration is outside all web roots.
    $path = dirname(__DIR__, 3) . '/niwff-private/config.php';
    if (!is_file($path)) throw new RuntimeException('Database configuration unavailable');
    $cfg = require $path;
    if (!is_array($cfg)) throw new RuntimeException('Invalid configuration');
    return $cfg;
}
function submissions_has_contact_phone(PDO $pdo): bool {
    static $hasColumn = null;
    if (is_bool($hasColumn)) return $hasColumn;
    $query = $pdo->query("SHOW COLUMNS FROM submissions LIKE 'contact_phone'");
    $hasColumn = (bool)$query->fetch();
    return $hasColumn;
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function session_start_safe(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('niwff_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
    if (isset($_SESSION['admin_id']) && time() - (int)($_SESSION['last_seen'] ?? 0) > 1800) {
        $_SESSION=[]; session_regenerate_id(true);
    }
    $_SESSION['last_seen']=time();
}
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) {
        http_response_code(403); exit('Invalid form token. Return to the form and try again.');
    }
}
function fields(string $kind): array {
    if ($kind === 'club') return [
        'name'=>['Club Name','text',true,160],
        'area'=>['Council Area','text',true,160],
        'town'=>['Town / Location','text',true,160],
        'venue'=>['Venue and Address','textarea',true,5000],
        'sessions'=>['Session Days and Times','textarea',true,10000],
        'eligibility'=>['Age Groups and Men’s / Women’s / Mixed Sessions','textarea',true,1000],
        'cost'=>['Membership / Session Cost','text',false,200],
        'description'=>['Club Description','textarea',true,3000],
        'accessibility'=>['Accessibility Information','textarea',false,1000],
        'website'=>['Club Website','url',false,500],
        'social'=>['Club social media URL','url',false,500],
        'contact_method'=>['How to Contact Us (Public - Avoid Personal Phone or Email Unless Necessary)','textarea',false,1000],
        'public_email'=>['Public Club Email (Optional, Published If Approved)','email',false,254],
    ];
    return [
        'title'=>['Event Title','text',true,160],
        'club'=>['Organising Club','text',true,160],
        'type'=>['Event Type','text',true,160],
        'startDate'=>['Start Date','date',true,10],
        'endDate'=>['End Date (If Different)','date',false,10],
        'time'=>['Start / End Times (UK Time)','text',false,100],
        'venue'=>['Venue and Address','textarea',true,500],
        'eligibility'=>['Eligibility / Age Categories','textarea',false,1000],
        'cost'=>['Cost and Entry Details','text',false,200],
        'description'=>['Event Description','textarea',true,3000],
        'registration'=>['Registration / Information URL','url',false,500],
        'poster'=>['Poster URL (Optional; No File Upload)','url',false,500],
    ];
}
function valid_date(string $s): bool {
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$s);
    return $d !== false && $d->format('Y-m-d') === $s;
}
function validate_club_schedule(array $source,bool $optional=false): array {
    $raw=$source['schedule_data']??'';
    if($raw==='') return [null,[]];
    if(!is_string($raw)||strlen($raw)>30000) return [null,['Invalid structured schedule.']];
    $input=json_decode($raw,true);
    if(!is_array($input)||!isset($input['venues'],$input['sessions'])||!is_array($input['venues'])||!is_array($input['sessions'])||(!$optional&&count($input['venues'])<1)||count($input['venues'])>10||(!$optional&&count($input['sessions'])<1)||count($input['sessions'])>30) return [null,['Enter 1–10 venues and 1–30 sessions.']];
    $venues=[];$sessions=[];$errors=[];
    $days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    foreach(array_values($input['venues']) as $i=>$v){
        $name=is_array($v)&&is_string($v['name']??null)?trim($v['name']):'';
        $address=is_array($v)&&is_string($v['address']??null)?trim($v['address']):'';
        if((!$optional&&($name===''||$address===''))||strlen($name)>160||strlen($address)>300) $errors[]='Venue '.($i+1).' needs a name and address within the field limits.';
        $venues[]=['name'=>$name,'address'=>$address];
    }
    foreach(array_values($input['sessions']) as $i=>$v){
        $day=is_array($v)&&is_string($v['day']??null)?$v['day']:'';
        $venue=is_array($v)&&is_int($v['venue']??null)?$v['venue']:null;
        $start=is_array($v)&&is_string($v['start']??null)?$v['start']:'';
        $end=is_array($v)&&is_string($v['end']??null)?$v['end']:'';
        $category=is_array($v)&&is_string($v['category']??null)?$v['category']:'';
        if((!$optional||$day!=='')&&!in_array($day,$days,true)||(!$optional||$venue!==null)&&!isset($venues[$venue])||(!$optional||$start!=='')&&!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$start)||(!$optional||$end!=='')&&!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$end)||($start!==''&&$end!==''&&$end<=$start)||!in_array($category,['','Men','Women','Mixed'],true)) $errors[]='Session '.($i+1).' needs a valid day, location, start and later finish time.';
        $sessions[]=['day'=>$day,'venue'=>$venue,'start'=>$start,'end'=>$end,'category'=>$category];
    }
    return [['venues'=>$venues,'sessions'=>$sessions],$errors];
}
function validate_payload(string $kind, array $source,bool $adminClub=false): array {
    $data=[]; $errors=[];$schedule=null;
    if($kind==='club'){
        [$schedule,$errors]=validate_club_schedule($source,$adminClub);
        if($schedule!==null&&!$errors){
            $source['venue']=implode("\n",array_map(fn($v)=>implode(' — ',array_filter([$v['name'],$v['address']],fn($x)=>$x!=='')),$schedule['venues']));
            $source['sessions']=implode("\n",array_map(fn($v)=>trim($v['day'].' '.($v['start']!==''&&$v['end']!==''?$v['start'].'–'.$v['end']:($v['start']!==''?$v['start'].' (finish to be confirmed)':($v['end']!==''?'Finish '.$v['end']:''))).($v['venue']!==null&&($schedule['venues'][$v['venue']]['name']??'')!==''?' at '.$schedule['venues'][$v['venue']]['name']:'').($v['category']!==''?' ('.$v['category'].')':'')),$schedule['sessions']));
        }
    }
    foreach(fields($kind) as $key=>[$label,$type,$required,$max]){
        $raw=$source[$key] ?? '';
        $v=is_string($raw)?trim($raw):'';
        if($kind==='club'&&$key==='area') $v=normalize_council_area($v);
        if ($required && (!$adminClub||$kind!=='club'||$key==='name') && $v==='') $errors[]="$label is required.";
        if (strlen($v)>$max) $errors[]="$label is too long.";
        if ($v!=='' && $type==='email' && !filter_var($v,FILTER_VALIDATE_EMAIL)) $errors[]="$label is invalid.";
        if ($v!=='' && $type==='url' && (!filter_var($v,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($v,PHP_URL_SCHEME)),['http','https'],true))) $errors[]="$label must be an HTTP or HTTPS URL.";
        if ($v!=='' && $type==='date' && !valid_date($v)) $errors[]="$label is invalid.";
        $data[$key]=$v;
    }
    if ($kind==='event' && $data['endDate']!=='' && $data['endDate']<$data['startDate']) $errors[]='End date must not precede start date.';
    if($schedule!==null) $data['schedule_data']=$schedule;
    return [$data,$errors];
}
// Build reusable venue options exclusively from approved public records.
function event_venue_options(iterable $records): array {
    $options=[];
    foreach($records as $record){
        $p=json_decode((string)($record['payload']??''),true);
        if(!is_array($p)) continue;
        $candidates=[];
        if(($record['kind']??'')==='club'){
            $venues=$p['schedule_data']['venues']??[];
            if(is_array($venues)) foreach($venues as $v){
                if(!is_array($v)||!is_string($v['name']??null)||!is_string($v['address']??null)) continue;
                $name=trim($v['name']);$address=trim($v['address']);
                if($name!==''&&$address!=='') $candidates[]=$name.' — '.$address;
            }
        }elseif(($record['kind']??'')==='event'&&is_string($p['venue']??null)){
            $candidates[]=trim($p['venue']);
        }
        foreach($candidates as $venue){
            if($venue===''||strlen($venue)>500) continue;
            $key=strtolower(preg_replace('/\s+/',' ',trim($venue))??$venue);
            if(!isset($options[$key])) $options[$key]=$venue;
        }
    }
    $options=array_values($options);natcasesort($options);
    return array_values($options);
}
function approved_event_venues(): array {
    try{
        $q=db()->query("SELECT kind,payload FROM submissions WHERE status='approved' AND published_at IS NOT NULL AND kind IN ('club','event') ORDER BY id DESC");
        return event_venue_options($q);
    }catch(Throwable $e){
        error_log('NIWFF venue options unavailable');
        return [];
    }
}
function event_venue_selector(array $options,string $current): string {
    $selected=in_array($current,$options,true)?$current:'';
    $out='<div class="field event-venue-picker" id="event-venue-picker" hidden><label for="event-venue-choice">Choose a known venue</label><select id="event-venue-choice" aria-describedby="event-venue-help"><option value=""'.($selected===''?' selected':'').'>Other / new venue — enter below</option>';
    foreach($options as $venue) $out.='<option value="'.h($venue).'"'.($selected===$venue?' selected':'').'>'.h($venue).'</option>';
    return $out.'</select><p id="event-venue-help">Select a previously approved venue to fill in its name and address, or enter a new venue below. You can edit the details if needed.</p></div>';
}
// All eleven Northern Ireland local government districts, using the existing
// directory's short labels. Retain non-standard historical values only on
// the record being edited so administrators can reconcile them safely.
function standard_council_areas(): array {
    $areas=[
        'Antrim & Newtownabbey',
        'Ards & North Down',
        'Armagh, Banbridge & Craigavon',
        'Belfast',
        'Causeway Coast & Glens',
        'Derry City & Strabane',
        'Fermanagh & Omagh',
        'Lisburn & Castlereagh',
        'Mid & East Antrim',
        'Mid Ulster',
        'Newry, Mourne & Down',
    ];
    return $areas;
}
function normalize_council_area(string $area): string {
    $area=trim(preg_replace('/\s+/u',' ',trim($area))??$area);
    $area=preg_replace('/\band\b/iu','&',$area)??$area;
    $area=trim(preg_replace('/\s*&\s*/u',' & ',$area)??$area);
    $key=fn(string $v)=>strtolower(preg_replace('/[\s,]+/u','',$v)??$v);
    foreach(standard_council_areas() as $known) if($key($area)===$key($known)) return $known;
    return $area;
}
function council_area_options(iterable $records=[],string $current=''): array {
    $areas=standard_council_areas();
    $current=normalize_council_area($current);
    if($current!==''&&!in_array($current,$areas,true)) $areas[]=$current;
    natcasesort($areas);return array_values($areas);
}
function known_council_areas(string $current=''): array {
    return council_area_options([],$current);
}
function council_area_field(array $options,string $current,bool $required=true): string {
    $current=normalize_council_area($current);
    $options=array_values(array_unique(array_map('normalize_council_area',$options)));
    $out='<div class="field"><label for="area">Council Area'.($required?' *':'').'</label><select id="area" name="area"'.($required?' required':'').'><option value=""'.($current===''?' selected':'').'>Select council area</option>';
    foreach($options as $area) $out.='<option value="'.h($area).'"'.($current===$area?' selected':'').'>'.h($area).'</option>';
    return $out.'</select></div>';
}
function form_fields(string $kind,array $values=[],bool $adminClub=false): string {
    $out='';
    foreach(fields($kind) as $key=>[$label,$type,$required,$max]){
        if($adminClub&&$kind==='club'&&$key!=='name') $required=false;
        if($kind==='club'&&$key==='area'){
            $current=(string)($values[$key]??'');
            $out.=council_area_field(known_council_areas($current),$current,$required);
            continue;
        }
        if($kind==='event'&&$key==='venue') $out.=event_venue_selector(approved_event_venues(),(string)($values[$key]??''));
        $v=h((string)($values[$key]??''));
        $attr=' id="'.h($key).'" name="'.h($key).'" maxlength="'.$max.'"'.($required?' required':'');
        $out.='<div class="field"><label for="'.h($key).'">'.h($label).($required?' *':'').'</label>';
        $out.=$type==='textarea'?'<textarea'.$attr.' rows="4">'.$v.'</textarea>':'<input type="'.h($type).'"'.$attr.' value="'.$v.'">';
        $out.='</div>';
        if($kind==='club'&&$key==='sessions'){
            $json=isset($values['schedule_data'])?json_encode($values['schedule_data'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE):'';
            $out.='<div id="schedule-editor" class="schedule-editor" data-optional="'.($adminClub?'yes':'no').'" hidden><input type="hidden" name="schedule_data" id="schedule-data" disabled value="'.h($json).'"></div>';
        }
    }
    return $out;
}
function page(string $title,string $body): void {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    header("X-Robots-Tag: noindex, nofollow");
    echo '<!doctype html><html lang="en-GB"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.h($title).' | NIWFF</title><link rel="stylesheet" href="/assets/site.css?v='.h((string)filemtime(dirname(__DIR__).'/assets/site.css')).'"><script src="/assets/schedule.js?v='.h((string)filemtime(dirname(__DIR__).'/assets/schedule.js')).'" defer></script><header class="site-header"><div class="shell header-row"><a class="brand" href="/">NI Walking Football Federation</a><nav><a style="color:white" href="/competitions/">Events</a> · <a style="color:white" href="/find-a-club/">Find a Club</a></nav></div></header><main class="shell section"><h1>'.h($title).'</h1>'.$body.'<footer class="portal-version"><p><a href="mailto:info@niwalkingfootball.com">Contact us at info@niwalkingfootball.com</a></p></footer></main></html>';
}
function errors_html(array $errors): string {
    return $errors?'<div class="notice" role="alert"><ul>'.implode('',array_map(fn($e)=>'<li>'.h($e).'</li>',$errors)).'</ul></div>':'';
}
function notify_admin(int $id,string $kind,bool $amended=false): void {
    $c=config();
    if (!filter_var($c['admin_email']??'',FILTER_VALIDATE_EMAIL) || !filter_var($c['from_email']??'',FILTER_VALIDATE_EMAIL)) return;
    // Never include submitted personal data in notification email.
    @mail($c['admin_email'],($amended?'NIWFF amended submission awaiting review':'NIWFF submission awaiting review'),($amended?'An amended ':'A new ').$kind.' submission #'.$id.' is waiting in the NIWFF administration dashboard.','From: '.$c['from_email']);
}
function notify_submitter_review(array $submission,string $status,string $note,string $amendUrl=''): string {
    $to = trim((string)($submission['contact_email'] ?? ''));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return 'Email not sent (no valid contact email).';
    if ($status === 'pending') return 'Email not sent (status returned to pending).';

    $cfg = config();
    $from = trim((string)($cfg['from_email'] ?? ''));
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) return 'Email not sent (from_email not configured).';

    $prettyStatus = ucwords(str_replace('_', ' ', $status));
    $subject = 'NIWFF submission #'.((int)($submission['id'] ?? 0)).' review update';
    $body = "Your NIWFF submission has been reviewed.\n"
        ."Status: {$prettyStatus}\n\n"
        .($note !== '' ? "Reviewer note:\n{$note}\n\n" : '')
        .($amendUrl!=='' ? "Amend your existing submission (link expires in ".amendment_days()." days):\n{$amendUrl}\n" : "Thank you for your submission.\n");
    $headers = "From: {$from}\r\nReply-To: {$from}\r\nContent-Type: text/plain; charset=UTF-8";

    return @mail($to, $subject, $body, $headers)
        ? 'Email sent to '.$to.'.'
        : 'Email failed to '.$to.'.';
}
function amendment_url(string $token): string {
    $base=rtrim((string)(config()['site_base_url']??''),'/');
    if (!preg_match('~^https://[a-zA-Z0-9.-]+(?::[0-9]+)?$~D',$base)) throw new RuntimeException('Configure site_base_url before requesting changes.');
    return $base.'/submit.php?amend='.rawurlencode($token);
}
function amendment_days(): int { return max(1,min(90,(int)(config()['amendment_days']??14))); }
function issue_amendment(PDO $pdo,int $id,string $message): string {
    $token=bin2hex(random_bytes(32)); $url=amendment_url($token);
    $q=$pdo->prepare("INSERT INTO submission_amendments(submission_id,token_hash,expires_at,message) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash),expires_at=VALUES(expires_at),message=VALUES(message)");
    $q->execute([$id,hash('sha256',$token),gmdate('Y-m-d H:i:s',time()+86400*amendment_days()),$message]);
    return $url;
}
function revoke_amendment(PDO $pdo,int $id): void {
    $q=$pdo->prepare('DELETE FROM submission_amendments WHERE submission_id=?');$q->execute([$id]);
}
function save_revision(PDO $pdo,int $id,string $actor,string $payload): void {
    $q=$pdo->prepare('INSERT INTO submission_revisions(submission_id,actor,payload) VALUES(?,?,?)');$q->execute([$id,$actor,$payload]);
}
function admin(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    $q=db()->prepare('SELECT id,username FROM admins WHERE id=? AND active=1');
    $q->execute([$_SESSION['admin_id']]);
    return $q->fetch()?:null;
}
function redirect(string $url): never { header('Location: '.$url,true,303); exit; }
