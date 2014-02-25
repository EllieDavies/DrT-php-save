<?php
require_once('Optimisation.php');
class ExceptionTest extends PHPUnit_Framework_TestCase
{
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Smoothing width must be less than or equal to window width
	*/
	public function testException()
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
				'smoothing_width'=>31, 
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
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Win Width is smaller than win step - so some bins will be skipped over in the calculation
	*/
	public function testException1()
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
				'window_width'=>6, 
				'window_step'=>7, 
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
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Concavity limit must be greater than 0
	*/
	public function testException2()
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
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>-1, 
				'min_desk'=>1, 
				'max_desk'=>15, 
				'sla'=>12, 
				'time_limit'=>100, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Time limit must be greater than 0
	*/
	public function testException3()
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
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>15, 
				'sla'=>12, 
				'time_limit'=>0, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Time limit must be greater than 0
	*/
	public function testException4()
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
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>15, 
				'sla'=>12, 
				'time_limit'=>0, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Cannot have negative numbers of desks open
	*/
	public function testException5()
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
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>-1, 
				'max_desk'=>15, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Cannot have negative numbers of desks open
	*/
	public function testException6()
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
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>0, 
				'max_desk'=>-1, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Maximum desks open is smaller than minimum desks open
	*/
	public function testException7()
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
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>8, 
				'max_desk'=>7, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Minimum and maximum desks open must be integers
	*/
	public function testException8()
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
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>0.5, 
				'max_desk'=>7, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Minimum and maximum desks open must be integers
	*/
	public function testException9()
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
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>7.5, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Block width must be a positive integer 
	*/
	public function testException10()
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
				'block_width'=>3.5, 
				'smoothing_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Block width must be a positive integer 
	*/
	public function testException11()
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
				'block_width'=>-3, 
				'smoothing_width'=>3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Smoothing width must be a positive integer
	*/
	public function testException12()
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
				'smoothing_width'=>3.5, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Smoothing width must be a positive integer
	*/
	public function testException13()
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
				'smoothing_width'=>-3, 
				'window_width'=>6, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Window width must be a positive integer
	*/
	public function testException14()
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
				'window_width'=>6.5, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Window width must be a positive integer
	*/
	public function testException15()
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
				'window_width'=>-6, 
				'window_step'=>-7, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}

	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Window width should be an even multiple of Block width
	*/
	public function testException16()
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
				'window_width'=>9, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Work is not divisible by block width
	*/
	public function testException17()
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
				'block_width'=>4, 
				'smoothing_width'=>3, 
				'window_width'=>16, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Smoothing width must be less than or equal to window width
	*/
	public function testException18()
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
				'block_width'=>2, 
				'smoothing_width'=>20, 
				'window_width'=>16, 
				'window_step'=>6, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}
	/**
	* @expectedException exception
	* @expectedExceptionMessage Incorrectly configured. Window step should be a multiple of or equal to block width
	*/
	public function testException19()
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
				'block_width'=>2, 
				'smoothing_width'=>16, 
				'window_width'=>16, 
				'window_step'=>7, 
				'concavity_limit'=>30, 
				'min_desk'=>1, 
				'max_desk'=>10, 
				'sla'=>12, 
				'time_limit'=>10, 
				'existing_queue'=>0, 
				'existing_staff'=>0, 
				);
		$a = new Optimisation();
		$b=$a->Optimise($work,$set_options);
	}





















}
