<?php
include 'Optimisation.php';
$work=array();
for($i=0; $i < 10; $i++){
	array_push($work, 16);	
}
$set_options=array(     'weight_sla'=>10, 
		'weight_pax'=>1, 
		'weight_staff'=>3, 
		'weight_churn'=>45, 
		'block_width'=>2, 
		'smoothing_width'=>3, 
		'window_width'=>8, 
		'window_step'=>4, 
		'concavity_limit'=>30, 
		'min_desk'=>1, 
		'max_desk'=>15, 
		'sla'=>12, 
		'time_limit'=>100, 
		'existing_queue'=>0, 
		'existing_staff'=>0, 
		);
$a=new Optimisation();
$b=$a->Optimise($work, $set_options);

print_r($b);

?>
