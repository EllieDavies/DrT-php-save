<?php
require_once('Optimisation.php');

class OptimisationTest extends PHPUnit_Framework_TestCase
{
	public function testTiming1()
	{
	$work=array();
	for($i=0; $i < 1440; $i++){
		$x=rand(0, 100);
		if($x/100 < 0.8){
		array_push($work, rand(0, 10));}
		else{
		array_push($work, rand(10, 15));}
	}
	$set_options=array(     'weight_sla'=>10, 
			'weight_pax'=>1, 
			'weight_staff'=>3, 
			'weight_churn'=>45, 
			'block_width'=>15, 
			'smoothing_width'=>15, 
			'window_width'=>90, 
			'window_step'=>60, 
			'concavity_limit'=>30, 
			'min_desk'=>1, 
			'max_desk'=>10, 
			'sla'=>12, 
			'time_limit'=>100, 
			'existing_queue'=>0, 
			'existing_staff'=>0, 
			);
	$timestart = microtime(true);
	$a=new Optimisation();
	$b=$a->Optimise($work, $set_options);
	$timeend=microtime(true);
	$time_interval = $timeend - $timestart;
	print "$time_interval \n"; 
	$this->assertLessThan(2, $time_interval);
	}
	
	public function testTiming2()
		{
			$filename="/home/eleanor/Desktop/PHP/work_e.csv";
			$fd=fopen($filename, "r") or die("can't open file");
			$work = array();
			while($data=fgetcsv($fd)){
				if(Is_Numeric($data[3])){
					array_push($work, $data[3]);}
			}
			fclose($fd);
			$set_options=array(     'weight_sla'=>10, 
					'weight_pax'=>1, 
					'weight_staff'=>3, 
					'weight_churn'=>45, 
					'block_width'=>15, 
					'smoothing_width'=>15, 
					'window_width'=>90, 
					'window_step'=>60, 
					'concavity_limit'=>30, 
					'min_desk'=>1, 
					'max_desk'=>10, 
					'sla'=>12, 
					'time_limit'=>100, 
					'existing_queue'=>0, 
					'existing_staff'=>0, 
					);
			$timestart=microtime(true);
			$a = new Optimisation();
			$b=$a->Optimise($work, $set_options);
			$timeend=microtime(true);
			$time_interval = $timeend- $timestart;
			$this->assertLessThan(7, $time_interval);
		}

	public function testArray()
		{
			$filename="/home/eleanor/Desktop/PHP/work_e.csv";
			$fd=fopen($filename, "r") or die("can't open file");
			$work = array();
			while($data=fgetcsv($fd)){
				if(Is_Numeric($data[3]) && $data[0]==2){
					array_push($work, $data[3]);}
			}
			fclose($fd);
			$set_options=array(     'weight_sla'=>10, 
					'weight_pax'=>1, 
					'weight_staff'=>3, 
					'weight_churn'=>45, 
					'block_width'=>15, 
					'smoothing_width'=>15, 
					'window_width'=>90, 
					'window_step'=>60, 
					'concavity_limit'=>30, 
					'min_desk'=>1, 
					'max_desk'=>10, 
					'sla'=>12, 
					'time_limit'=>100, 
					'existing_queue'=>0, 
					'existing_staff'=>0, 
					);
			$a = new Optimisation();
			$b=$a->Optimise($work,$set_options);
			$this->assertEquals(count($work), count($b));
		}

	public function testHeavyWorkAtEnd()
	{
		$work = array();
		for($i=0; $i<60 ; $i++){
			if($i<55){ array_push($work, 0);}
			if($i>=55){array_push($work, 25);}
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'smoothing_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>100, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);

		$a = new Optimisation();
		$b=$a->Optimise($work, $set_options);

		$result = Array(1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10);
		$this->assertEquals($result, $b);
	}
	public function testTooMuchWork()
	{
		$work = array();
		$answer = array();
		for($i=0; $i<1440 ; $i++){
			array_push($work, 25);
			array_push($answer,10);
		}
		$set_options=array(     'weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'smoothing_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>100, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);


		$a = new Optimisation();
		$b=$a->Optimise($work, $set_options);
		$this->assertEquals($answer, $b);
	}
	public function testNoWork()
	{
		$work = array();
		$answer = array();
		for($i=0; $i<1440 ; $i++){
			array_push($work, 0);
			array_push($answer,1);
		}
		$set_options=array(     'weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'smoothing_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>100, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);


		$a = new Optimisation();
		$b=$a->Optimise($work, $set_options);
		$this->assertEquals($answer, $b);
	}

	public function testStep()
	{
		$work = array();
		$answer = array();
		for($i=0; $i<90 ; $i++){
			if($i<30 || $i>=60){ 
				array_push($work, 0);
				array_push($answer, 1);
			}
			if($i>=30 && $i<60){
				array_push($work, 10);
				array_push($answer, 10);
				}
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'smoothing_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>100, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
		$this->assertEquals($answer, $b);
	}
	
	public function testWindowStepAndWidth()
	{
		$work = array();
		$answer = array();
		for($i=0; $i<30 ; $i++){
			array_push($work, 15);
			array_push($answer, 15);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'smoothing_width'=>3, 
				'window_width'=>12, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>15, 
				'sla'=>12, 
				'time_limit'=>100, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
		$this->assertEquals($answer, $b);
	}

	public function testTimingVeryLargeAirport()
	{
	$work=array();
	for($i=0; $i < 1440; $i++){
		if(rand(0, 100)/100 < 0.8){
		array_push($work, rand(0, 100));}
		else{
		array_push($work, rand(100, 150));}
	}
	$set_options=array('weight_sla'=>10, 
			'weight_pax'=>1, 
			'weight_staff'=>3, 
			'weight_churn'=>45, 
			'block_width'=>15, 
			'smoothing_width'=>15, 
			'window_width'=>90, 
			'window_step'=>60, 
			'concavity_limit'=>30, 
			'min_desk'=>1, 
			'max_desk'=>100, 
			'sla'=>12, 
			'time_limit'=>100, 
			'existing_queue'=>0, 
			'existing_staff'=>0, 
			);
	$timestart = microtime(true);
	$a=new Optimisation();
	$b=$a->Optimise($work, $set_options);
	$timeend=microtime(true);
	$time_interval = $timeend - $timestart;
	print $time_interval;
	$this->assertLessThan(20, $time_interval);
	}
}
