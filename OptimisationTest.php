<?php
require_once('Optimisation.php');

class OptimisationTest extends PHPUnit_Framework_TestCase
{

	public function testTiming1()
	{
	$work=array();
	$desks=array();
	$queues=array();
	$max_desk = array();
	for($i=0; $i<1440; $i++){
		array_push($desks, 0);
		array_push($queues, 0);
	}	
	for($i=0; $i < 1440; $i++){
		$x=rand(0, 100);
		if($x/100 < 0.95){
		array_push($work, rand(0, 10));}
		else{
		array_push($work, rand(10, 50));}
		array_push($max_desk, 10);
	}

	$set_options=array(     'weight_sla'=>10, 
			'weight_pax'=>1, 
			'weight_staff'=>3, 
			'weight_churn'=>45, 
			'block_width'=>15, 
			'window_width'=>90, 
			'window_step'=>60, 
			'concavity_limit'=>30, 
			'min_desk'=>1, 
			'max_desk'=>$max_desk, 
			'sla'=>12, 
			'time_limit'=>100, 
			'input_queue'=>0, 
			);
	$timestart = microtime(true);
	$a=new Optimisation();
	$b=$a->Optimise($work, $desks,$queues,$set_options);
	$timeend=microtime(true);
	$time_interval = $timeend - $timestart;
	$this->assertLessThan(2, $time_interval);
	}
	
	public function testTiming2()
		{
			$filename="/home/eleanor/Desktop/PHP/work_e.csv";
			$fd=fopen($filename, "r") or die("can't open file");
			$work = array();
			$desks=array();
			$queues=array();
			$max_desk = array();
			for($i=0; $i<1440*7; $i++){
				array_push($desks, 0);
				array_push($queues, 0);
				array_push($max_desk, 10);
			}	
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
					'window_width'=>90, 
					'window_step'=>60, 
					'concavity_limit'=>30, 
					'min_desk'=>1, 
					'max_desk'=>$max_desk, 
					'sla'=>12, 
					'time_limit'=>100, 
					'input_queue'=>0, 
					);
			$timestart=microtime(true);
			$a = new Optimisation();
			$b=$a->Optimise($work, $desks,$queues,$set_options);
			$timeend=microtime(true);
			$time_interval = $timeend- $timestart;
			$this->assertLessThan(7, $time_interval);
		}


	public function testHeavyWorkAtEnd()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<60; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
		}	
		for($i=0; $i<60 ; $i++){
			if($i<55){ array_push($work, 0);}
			if($i>=55){array_push($work, 25);}
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);

		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues, $set_options);

		$answer = Array(1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10);
		$this->assertEquals($answer, $desks);
	}
	public function testTooMuchWork()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<60; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
	}	
		$answer = array();
		for($i=0; $i<60 ; $i++){
			array_push($work, 25);
			array_push($answer,10);
		}
		$set_options=array(     'weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);


		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues, $set_options);
		$this->assertEquals($answer, $desks);
	}
	public function test_InputQueue()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
		}	
		$answer = array();
		for($i=0; $i<90; $i++){
			array_push($work, 0);
			if($i < 60){
			array_push($answer,1);}
			if($i >= 60){
			array_push($answer, 10);}
		}
		$set_options=array(     'weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>1000, 
				'input_queue_time'=>60,
				);


		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($answer, $desks);
	}

	public function testStep()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
		}	
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
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($answer, $desks);
	}
	
	public function testNoWork()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<1440; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
		}	
		$answer = array();
		for($i=0; $i<1440 ; $i++){
			array_push($work, 0);
			array_push($answer,1);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);


		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($answer, $desks);
	}
	
	public function testQueue_fullcapacity()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$storequeues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($storequeues, 0);
			array_push($max_desk, 10);
		}	
		$answer = array();
		for($i=0; $i<90 ; $i++){
			array_push($work, 10);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($storequeues, $queues);
	}

	public function testQueue_notenoughcapacity()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$storequeues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($storequeues, intval($i/10+1));
			array_push($max_desk, 10);
		}	
		$answer = array();
		for($i=0; $i<90 ; $i++){
			array_push($work, 11);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($storequeues, $queues);
	}
	
	public function testQueue_spikycapacity()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$storequeues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			if($i%2==0)
			array_push($storequeues, 1);
			else
			array_push($storequeues, 0);
			array_push($max_desk, 2);
		}	
		$answer = array();
		for($i=0; $i<90 ; $i++){
			if($i%2==0)
			array_push($work, 4);
			else
			array_push($work, 0);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($storequeues, $queues);
	}
	
	public function testWindowStepAndWidth()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<30; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 15);
		}	
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
				'window_width'=>12, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($answer, $desks);
	}

	public function testReturnCode_CompletedSuccessfully()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
		}	
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
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($b, 0);
	}

	public function testReturnCode_Timeout()
	{
		$filename="/home/eleanor/Desktop/PHP/work_e.csv";
		$fd=fopen($filename, "r") or die("can't open file");
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<1440*7; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 20);
		}	
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
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>1, 
				'input_queue'=>0, 
				);
		$timestart=microtime(true);
		$a = new Optimisation();
		try{
		$b=$a->Optimise($work, $desks,$queues, $set_options);}
		catch(Exception $e){
			echo "Caught exception ",$e->getMessage(),"\n";}
		$this->assertEquals($b,1);
	}

	public function testReturnCode_InsufficientManpower()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
		}	
		$answer = array();
		for($i=0; $i<90 ; $i++){
			if($i<30 || $i>=60){ 
				array_push($work, 0);
				array_push($answer, 1);
			}
			if($i>=30 && $i<60){
				array_push($work, 20);
				array_push($answer, 10);
				}
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($b, 2);
	}
	
        public function testReturnCode_InsufficientManpowerAndConfigChange()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
		}	
		$answer = array();
		for($i=0; $i<90 ; $i++){
			if($i<30 || $i>=60){ 
				array_push($work, 0);
				array_push($answer, 1);
			}
			if($i>=30 && $i<60){
				array_push($work, 20);
				array_push($answer, 10);
				}
		}
		$set_options=array('weight_sla'=>10, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($b, 23);
	}

        public function testReturnCode_ConfigChange()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<90; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
		}	
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
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($b, 3);
	}

	public function testReturnCode_TimeoutandInsufficentManpower()
	{
		$filename="/home/eleanor/Desktop/PHP/work_e.csv";
		$fd=fopen($filename, "r") or die("can't open file");
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<1440*7; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 3);
		}	
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
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>1, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues, $set_options);
		$this->assertEquals($b,12);
	}

	public function testReturnCode_TimeoutandConfigChange()
	{
		$filename="/home/eleanor/Desktop/PHP/work_e.csv";
		$fd=fopen($filename, "r") or die("can't open file");
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<1440*7; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 20);
		}	
		while($data=fgetcsv($fd)){
			if(Is_Numeric($data[3])){
				array_push($work, $data[3]);}
		}
		fclose($fd);
		$set_options=array(     'weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>1, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($b,13);
	}

	public function testReturnCode_TimeoutandConfigChangeandInsufficentManpower()
	{
		$filename="/home/eleanor/Desktop/PHP/work_e.csv";
		$fd=fopen($filename, "r") or die("can't open file");
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = array();
		for($i=0; $i<1440*7; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 2);
		}	
		while($data=fgetcsv($fd)){
			if(Is_Numeric($data[3])){
				array_push($work, $data[3]);}
		}
		fclose($fd);
		$set_options=array(     'weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>1, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues,$set_options);
		$this->assertEquals($b,123);
	}
	public function testMaxDeskNotArray()
	{
		$work = array();
		$desks=array();
		$queues=array();
		$max_desk = 10;
		for($i=0; $i<60; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
		}	
		for($i=0; $i<60 ; $i++){
			if($i<55){ array_push($work, 0);}
			if($i>=55){array_push($work, 25);}
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>15, 
				'window_width'=>90, 
				'window_step'=>60, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);

		$a = new Optimisation();
		$b=$a->Optimise($work, $desks,$queues, $set_options);

		$answer = Array(1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,10,10,10,10,10,10,10,10,10,10,10,10,10,10,10);
		$this->assertEquals($answer, $desks);
	}



}
