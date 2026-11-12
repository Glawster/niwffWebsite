<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/portal.php';
if($argc!==2||!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/',$argv[1])){fwrite(STDERR,"Usage: php scripts/createAdmin.php USERNAME\n");exit(1);}
fwrite(STDERR,"Enter a strong password (at least 14 characters; input hidden): ");
$state=trim((string)shell_exec('stty -g 2>/dev/null'));
if($state===''){fwrite(STDERR,"A terminal is required.\n");exit(1);}
try{shell_exec('stty -echo');$password=rtrim((string)fgets(STDIN),"\r\n");}finally{shell_exec('stty '.escapeshellarg($state));fwrite(STDERR,"\n");}
if(strlen($password)<14||strlen($password)>200){fwrite(STDERR,"Password must be 14–200 characters.\n");exit(1);}
$q=db()->prepare('INSERT INTO admins(username,password_hash) VALUES(?,?)');
$q->execute([$argv[1],password_hash($password,PASSWORD_DEFAULT)]);
echo "Administrator created.\n";
