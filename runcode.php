<?php
include 'Optimisation.php';


$x=error_reporting('E_ALL');

$work = array();
$desks=array();
$test=array();
$filename="/home/eleanor/Testing/DrT-php-save/work_e.csv";
$fd=fopen($filename, "r") or die("can't open file");
while($data=fgetcsv($fd)){
	if(Is_Numeric($data[3]) && $data[0]==2){
	//if(Is_Numeric($data[3])){
		array_push($work, $data[3]);
		//array_push($work, 17);
		array_push($desks, 0);
		array_push($test, 0);}
		
}
fclose($fd);

$queues = array();
$max_desk = 12;
$min_desk = 0;
for($i=0; $i<1440; $i++){
array_push($queues, 0);
}

$set_options=array(     'weight_sla'=>10, 
			'weight_pax'=>0.3, 
			'weight_staff'=>3, 
			'weight_churn'=>45,
			'block_width'=>15, 
			'window_width'=>90, 
			'window_step'=>60, 
			'concavity_limit'=>30, 
			'min_desk'=>$min_desk, 
			'max_desk'=>$max_desk, 
			'sla'=>25, 
			'time_limit'=>21, 
);

$time1=time();
$a = new Optimisation();
try{
$b=$a->Optimise($work,$desks,$queues,$set_options);}
catch(Exception $e){
	echo "Caught exception ",$e->getMessage(),"\n";}
$time2=time()-$time1;

print "time = $time2 \n"; 


$yy = 0;
$oo = 0;
$sumqueue = 0;
foreach($desks as $xx) if($xx > $yx) {print "$xx \n"; $yx=$xx;}
foreach($queues as $qq) {$sumqueue+=$qq;
	if($qq > $oo) {print "max queue $qq \n"; $oo=$qq;;}}
$sumqueue = $sumqueue/count($queues);
print "ave queue $sumqueue \n";

$deskscount = 0;
foreach($desks as $dd){ $deskscount+=$dd;}
$deskscount = $deskscount;

print "tot desk hours - $deskscount \n";


print "returned $b \n";

$myFile="forPlotting.txt";
$fh = fopen($myFile, "w");


for($ft =0 ; $ft < count($desks); $ft++){
$realtime = date("H:i:s",$ft*60);
fwrite($fh, "$realtime $queues[$ft] $desks[$ft] $work[$ft] \n");


}
fclose($fh);


?>
