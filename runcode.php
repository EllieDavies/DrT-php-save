<?php
include 'Optimisation.php';

$x=error_reporting('E_ALL');

$filename="/home/eleanor/Desktop/PHP/work_e.csv";
$fd=fopen($filename, "r") or die("can't open file");
$work = array();
while($data=fgetcsv($fd)){
	if(Is_Numeric($data[3]) && $data[0]==2){
	//if(Is_Numeric($data[3])){
		array_push($work, $data[3]);}
}
fclose($fd);

$set_options=array(     'weight_sla'=>10, 
			'weight_pax'=>1, 
			'weight_staff'=>3, 
			'weight_churn'=>45, 
			'block_width'=>-15, 
			'smoothing_width'=>15, 
			'window_width'=>90, 
			'window_step'=>60, 
			'concavity_limit'=>30, 
			'min_desk'=>0, 
			'max_desk'=>0, 
			'sla'=>12, 
			'time_limit'=>10, 
			'existing_queue'=>0, 
			'existing_staff'=>0, 
);


$a = new Optimisation();
$b=$a->Optimise($work, $set_options);

print_r($b);




?>
