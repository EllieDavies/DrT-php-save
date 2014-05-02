<?php
require_once('Optimisation.php');
class ExceptionTest extends PHPUnit_Framework_TestCase
{
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Win Width is smaller than win step - so some bins will be skipped over in the calculation
	*/
	public function testException_WinStepLessThanWinWidth()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, 15);
			array_push($desks, 0);
			array_push($queues, 0);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6, 
				'window_step'=>7, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Concavity limit must be greater than 0
	*/
	public function testException_NegativeConcavityLimit()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks=array();
		$queues=array();
		for($i=0; $i<30 ; $i++){
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, 15);
			array_push($desks, 0);
			array_push($queues, 0);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>-1, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>100, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Time limit must be greater than 0
	*/
	public function testException_NegativeTimeLimit()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 15);
			array_push($work, 15);
			array_push($answer, 15);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>-1, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Cannot have negative numbers of desks open
	*/
	public function testException_NegativeDeskNumbers()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, 15);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>-1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured, max_desk array must be all integers
	*/
	public function testException_NegativeMaximumDesks()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();	
		$queues = array();	
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, -1);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>0, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured, max desk array must be all greater than or equal to min desks
	*/
	public function testException_MaxDeskLessThanMinDesk()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, 7);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>8, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Minimum desks open must be an integer
	*/
	public function testException_NonIntegerMinimumDesks()
	{
		$work = array();
		$answer = array();
		$max_desk = array();
		$desks=array();
		$queues=array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, 7);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>0.5, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured, max_desk array must be all integers
	*/
	public function testException_NonIntegerMaximumDesks()
	{
		$work = array();
		$answer = array();
		$max_desk = array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, 7.5);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Block width must be a positive integer 
	*/
	public function testException_NonIntegerBlockWidth()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($max_desk, 10);
			array_push($work, 15);
			array_push($answer, 15);
			array_push($desks, 0);
			array_push($queues, 0);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3.5, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Block width must be a positive integer 
	*/
	public function testException_NegativeBlockWidth()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks= array();
		$queues= array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
			array_push($work, 15);
			array_push($answer, 15);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>-3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Window width must be a positive integer
	*/
	public function testException_NonIntegerWindowWidth()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, 10);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>6.5, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Window width must be a positive integer
	*/
	public function testException_NegativeWindowWidth()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
			array_push($work, 15);
			array_push($answer, 15);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>-6, 
				'window_step'=>-7, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Window width should be an even multiple of Block width
	*/
	public function testException_WindowWidthNotEvenMultipleOfBlockWidth()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($work, 15);
			array_push($answer, 15);
			array_push($max_desk, 10);
			array_push($desks, 10);
			array_push($queues, 10);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>3, 
				'window_width'=>9, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Work is not divisible by block width
	*/
	public function testException_WorkNotDivisibleByBlockWidth()
	{
		$work = array();
		$answer = array();
		$max_desk = array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks,0);
			array_push($queues,0);
			array_push($max_desk, 10);
			array_push($work, 15);
			array_push($answer, 15);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>4, 
				'window_width'=>16, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Window step should be a multiple of or equal to block width
	*/
	public function testException_WindowStepNotMultipleOfBlockWidth()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
			array_push($work, 15);
			array_push($answer, 15);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>2, 
				'window_width'=>16, 
				'window_step'=>7, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks,$queues,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Missing argument 3 for Optimisation::Optimise()
	*/
	public function testException_MissingArgumentsForOptimise()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
			array_push($work, 15);
			array_push($answer, 15);
		}
		$set_options=array('weight_sla'=>10, 
				'weight_pax'=>1, 
				'weight_staff'=>3, 
				'weight_churn'=>45, 
				'block_width'=>2, 
				'window_width'=>16, 
				'window_step'=>7, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>$max_desk, 
				'sla'=>12, 
				'time_limit'=>10, 
				'input_queue'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Input queue length specified but also need input queue time
	*/
	public function testException_InputQueueNoInputTime()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
			array_push($work, 15);
			array_push($answer, 15);
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
				'time_limit'=>10, 
				'input_queue'=>190, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks, $queues,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Input queue time specified but not input queue length
	*/
	public function testException_InputQueueTimeNoInputQueueLength()
	{
		$work = array();
		$answer = array();
		$max_desk=array();
		$desks = array();
		$queues = array();
		for($i=0; $i<30 ; $i++){
			array_push($desks, 0);
			array_push($queues, 0);
			array_push($max_desk, 10);
			array_push($work, 15);
			array_push($answer, 15);
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
				'time_limit'=>10, 
				'input_queue_time'=>10, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$desks, $queues,$set_options);
	}





















}
