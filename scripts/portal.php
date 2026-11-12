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
        'name'=>['Club name','text',true,160],
        'area'=>['Council area','text',true,160],
        'town'=>['Town / location','text',true,160],
        'venue'=>['Venue and address','textarea',true,500],
        'sessions'=>['Session days and times','textarea',true,1000],
        'eligibility'=>['Age groups and men’s / women’s / mixed sessions','textarea',true,1000],
        'cost'=>['Membership / session cost','text',false,200],
        'description'=>['Club description','textarea',true,3000],
        'accessibility'=>['Accessibility information','textarea',false,1000],
        'website'=>['Club website','url',false,500],
        'social'=>['Club social media URL','url',false,500],
        'public_email'=>['Public club email (optional, published if approved)','email',false,254],
    ];
    return [
        'title'=>['Event title','text',true,160],
        'club'=>['Organising club','text',true,160],
        'type'=>['Event type','text',true,160],
        'startDate'=>['Start date','date',true,10],
        'endDate'=>['End date (if different)','date',false,10],
        'time'=>['Start / end times (UK time)','text',false,100],
        'venue'=>['Venue and address','textarea',true,500],
        'eligibility'=>['Eligibility / age categories','textarea',false,1000],
        'cost'=>['Cost and entry details','text',false,200],
        'description'=>['Event description','textarea',true,3000],
        'registration'=>['Registration / information URL','url',false,500],
        'poster'=>['Poster URL (optional; no file upload)','url',false,500],
    ];
}
function valid_date(string $s): bool {
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$s);
    return $d !== false && $d->format('Y-m-d') === $s;
}
function validate_payload(string $kind, array $source): array {
    $data=[]; $errors=[];
    foreach(fields($kind) as $key=>[$label,$type,$required,$max]){
        $raw=$source[$key] ?? '';
        $v=is_string($raw)?trim($raw):'';
        if ($required && $v==='') $errors[]="$label is required.";
        if (strlen($v)>$max) $errors[]="$label is too long.";
        if ($v!=='' && $type==='email' && !filter_var($v,FILTER_VALIDATE_EMAIL)) $errors[]="$label is invalid.";
        if ($v!=='' && $type==='url' && (!filter_var($v,FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($v,PHP_URL_SCHEME)),['http','https'],true))) $errors[]="$label must be an HTTP or HTTPS URL.";
        if ($v!=='' && $type==='date' && !valid_date($v)) $errors[]="$label is invalid.";
        $data[$key]=$v;
    }
    if ($kind==='event' && $data['endDate']!=='' && $data['endDate']<$data['startDate']) $errors[]='End date must not precede start date.';
    return [$data,$errors];
}
function form_fields(string $kind,array $values=[]): string {
    $out='';
    foreach(fields($kind) as $key=>[$label,$type,$required,$max]){
        $v=h((string)($values[$key]??''));
        $attr=' id="'.h($key).'" name="'.h($key).'" maxlength="'.$max.'"'.($required?' required':'');
        $out.='<div class="field"><label for="'.h($key).'">'.h($label).($required?' *':'').'</label>';
        $out.=$type==='textarea'?'<textarea'.$attr.' rows="4">'.$v.'</textarea>':'<input type="'.h($type).'"'.$attr.' value="'.$v.'">';
        $out.='</div>';
    }
    return $out;
}
function page(string $title,string $body): void {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header("X-Robots-Tag: noindex, nofollow");
    echo '<!doctype html><html lang="en-GB"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.h($title).' | NIWFF</title><link rel="stylesheet" href="/assets/site.css"><header class="site-header"><div class="shell header-row"><a class="brand" href="/">NI Walking Football Federation</a><nav><a style="color:white" href="/competitions/">Events</a> · <a style="color:white" href="/find-a-club/">Find a Club</a></nav></div></header><main class="shell section"><h1>'.h($title).'</h1>'.$body.'</main></html>';
}
function errors_html(array $errors): string {
    return $errors?'<div class="notice" role="alert"><ul>'.implode('',array_map(fn($e)=>'<li>'.h($e).'</li>',$errors)).'</ul></div>':'';
}
function notify_admin(int $id,string $kind): void {
    $c=config();
    if (!filter_var($c['admin_email']??'',FILTER_VALIDATE_EMAIL) || !filter_var($c['from_email']??'',FILTER_VALIDATE_EMAIL)) return;
    // Never include submitted personal data in notification email.
    @mail($c['admin_email'],'NIWFF submission awaiting review','A new '.$kind.' submission #'.$id.' is waiting in the NIWFF administration dashboard.','From: '.$c['from_email']);
}
function admin(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    $q=db()->prepare('SELECT id,username FROM admins WHERE id=? AND active=1');
    $q->execute([$_SESSION['admin_id']]);
    return $q->fetch()?:null;
}
function redirect(string $url): never { header('Location: '.$url,true,303); exit; }
