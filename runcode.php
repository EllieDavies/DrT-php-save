<?php
include 'Optimisation.php';

$x=error_reporting('E_ALL');

$filename="/home/eleanor/Desktop/PHP/work_e.csv";
$fd=fopen($filename, "r") or die("can't open file");
$work = array();
$desks=array();
$test=array();
while($data=fgetcsv($fd)){
	if(Is_Numeric($data[3]) && $data[0]==2){
	//if(Is_Numeric($data[3])){
		array_push($work, $data[3]);
		array_push($desks, 0);
		array_push($test, 0);}
}
fclose($fd);

if($test==$desks) print "passed \n";
$max_desk=array();
for($i=0; $i<count($work); $i++){
array_push($max_desk, rand(10, 20));
}

$set_options=array(     'weight_sla'=>10, 
			'weight_pax'=>1, 
			'weight_staff'=>3, 
			'weight_churn'=>45, 
			'block_width'=>15, 
			'smoothing_width'=>15, 
			'window_width'=>60, 
			'window_step'=>60, 
			'concavity_limit'=>30, 
			'min_desk'=>1, 
			'max_desk'=>$max_desk, 
			'sla'=>12, 
			'time_limit'=>21, 
			'existing_queue'=>0, 
			'existing_staff'=>0, 
);


$a = new Optimisation();
try{
$b=$a->Optimise($work,$desks,$set_options);}
catch(Exception $e){
	echo "Caught exception ",$e->getMessage(),"\n";}

print_r($desks);

print "returned $b \n";

?>
