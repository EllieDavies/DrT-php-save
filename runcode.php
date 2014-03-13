<?php
include 'Optimisation.php';

$x=error_reporting('E_ALL');

$filename="/home/eleanor/Desktop/PHP/work_e.csv";
$fd=fopen($filename, "r") or die("can't open file");
$work = array();
$desks=array();
$test=array();
while($data=fgetcsv($fd)){
	if(Is_Numeric($data[3]) && $data[0]==1){
	//if(Is_Numeric($data[3])){
		array_push($work, $data[3]);
		array_push($desks, 0);
		array_push($test, 0);}
}
fclose($fd);

if($test==$desks) print "passed \n";
$queues = array();
$max_desk = 25;
for($i=0; $i<count($work); $i++){
array_push($queues, 0);
}

$set_options=array(     'weight_sla'=>20, 
			'weight_pax'=>1, 
			'weight_staff'=>6, 
			'weight_churn'=>200, 
			'block_width'=>15, 
			'window_width'=>60, 
			'window_step'=>60, 
			'concavity_limit'=>30, 
			'min_desk'=>1, 
			'max_desk'=>$max_desk, 
			'sla'=>20, 
			'time_limit'=>21, 
);


$a = new Optimisation();
try{
$b=$a->Optimise($work,$desks,$queues,$set_options);}
catch(Exception $e){
	echo "Caught exception ",$e->getMessage(),"\n";}

$yy = 0;
foreach($desks as $xx) if($xx > $yx) {print "$xx \n"; $yx=$xx;}

print "returned $b \n";

?>
