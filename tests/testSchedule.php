<?php
declare(strict_types=1);
require dirname(__DIR__).'/scripts/portal.php';
function check(bool $ok,string $message): void { if(!$ok){fwrite(STDERR,$message.PHP_EOL);exit(1);} }
$base=['name'=>'Example Walking Football','area'=>'Ards and North Down','town'=>'Bangor','description'=>'Test club','eligibility'=>'Adults'];
$schedule=['venues'=>[['name'=>'Aurora','address'=>'Test address'],['name'=>'Clandeboye Park','address'=>'Second address']],'sessions'=>[['day'=>'Monday','venue'=>0,'start'=>'10:00','end'=>'11:00','category'=>'Mixed'],['day'=>'Friday','venue'=>1,'start'=>'11:00','end'=>'12:00','category'=>'Men']]];
[$data,$errors]=validate_payload('club',$base+['schedule_data'=>json_encode($schedule)]);
check(!$errors,'Valid guided schedule rejected: '.implode('; ',$errors));
check($data['schedule_data']===$schedule,'Structured schedule not retained');
check(str_contains($data['sessions'],'Friday 11:00–12:00 at Clandeboye Park (Men)'),'Session summary wrong');
check(str_contains($data['venue'],'Aurora — Test address'),'Venue summary wrong');
$bad=$schedule;$bad['sessions'][0]['venue']=99;
[, $errors]=validate_payload('club',$base+['schedule_data'=>json_encode($bad)]);
check((bool)$errors,'Invalid location accepted');
$bad=$schedule;$bad['sessions'][0]['end']='09:00';
[, $errors]=validate_payload('club',$base+['schedule_data'=>json_encode($bad)]);
check((bool)$errors,'Invalid finish time accepted');
$bad=$schedule;$bad['sessions'][0]['day']='Funday';
[, $errors]=validate_payload('club',$base+['schedule_data'=>json_encode($bad)]);
check((bool)$errors,'Invalid day accepted');
[$data,$errors]=validate_payload('club',$base+['venue'=>'Existing venue','sessions'=>'Existing free-text schedule']);
check(!$errors&&$data['sessions']==='Existing free-text schedule'&&!isset($data['schedule_data']),'Legacy free-text compatibility failed');
echo "Schedule tests passed.".PHP_EOL;
