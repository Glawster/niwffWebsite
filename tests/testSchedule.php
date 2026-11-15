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
$records=[
 ['kind'=>'club','payload'=>json_encode(['schedule_data'=>$schedule,'contact_email'=>'private@example.com'])],
 ['kind'=>'club','payload'=>json_encode(['schedule_data'=>['venues'=>[['name'=>'aurora','address'=>'Test address']]]])],
 ['kind'=>'club','payload'=>json_encode(['venue'=>'Ambiguous legacy venue text'])],
 ['kind'=>'event','payload'=>json_encode(['venue'=>'New Sports Hall — Example Road'])],
 ['kind'=>'event','payload'=>json_encode(['venue'=>'Aurora — Test address'])],
];
$options=event_venue_options($records);
check($options===['Aurora — Test address','Clandeboye Park — Second address','New Sports Hall — Example Road'],'Venue catalogue must be sorted, deduplicated and use approved public venue fields only');
$picker=event_venue_selector($options,'New Sports Hall — Example Road');
check(str_contains($picker,'Other / new venue')&&str_contains($picker,'value="New Sports Hall — Example Road" selected'),'Known venue selection not retained');
check(str_contains(event_venue_selector($options,'Unlisted location'),'value="" selected'),'New venue should select manual entry');
check(!str_contains($picker,'private@example.com'),'Private fields exposed');
$event=['title'=>'Test event','club'=>'Example club','type'=>'Friendly','startDate'=>'2026-12-01','description'=>'Test event','venue'=>'Unlisted hall — New address'];
[$data,$errors]=validate_payload('event',$event);
check(!$errors&&$data['venue']===$event['venue'],'New venue must be accepted without catalogue membership');
echo "Schedule and venue tests passed.".PHP_EOL;
