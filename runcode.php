<?php
include 'Optimisation.php';

$filename="/home/eleanor/Desktop/PHP/work_e.csv";
$fd=fopen($filename, "r") or die("can't open file");
$work = array();
while($data=fgetcsv($fd)){
	if(Is_Numeric($data[3]) && $data[0]==2){
		array_push($work, $data[3]);}
}
fclose($fd);


$a = new Optimisation();
$b=$a->Optimise($work);

print_r($b);




?>
