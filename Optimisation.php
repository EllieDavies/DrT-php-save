<?php

class Optimisation 
{
	private $no_manpower;
	private $config_change;
	private $timed_out;
	private $time_end;
	private $min_desk;
	private $max_desk = array();
	private $sla;
	private $weight_sla;
	private $weight_pax;
	private $weight_staff;
	private $weight_churn;
	private $block_width;
	private $smoothing_width;
	private $window_width;
	private $window_step;
	private $concavity_limit;
	private $current_work = array();
	private $current_work_step = array();
	private $existing_queue;
	private $existing_staff;
	private $time_limit;
	private $work_length;
	public $desks;
	private $start_time;
	private $test1=array();
	private $test2=array();
	private $test=array();


	private function configure($set_options){
		$this->config_change=False;
		$this->check_keys_exist($set_options);
		$this->validate_config($set_options);//}
		return "1";
	}
	private function check_keys_exist($set_options){
		if(!array_key_exists("weight_sla",$set_options) || !array_key_exists("weight_pax", $set_options) || !array_key_exists("weight_staff", $set_options) || !array_key_exists("weight_churn", $set_options)){
			$this->weight_sla=10;
			$this->weight_pax=1;
			$this->weight_staff=3;
			$this->weight_churn=45;
			$this->config_change=True;
		}
		else{
			$this->weight_sla=$set_options["weight_sla"];
			$this->weight_pax=$set_options["weight_pax"];
			$this->weight_staff=$set_options["weight_staff"];
			$this->weight_churn=$set_options["weight_churn"];
		}
		if(!array_key_exists("block_width", $set_options) || !array_key_exists("window_width", $set_options) || !array_key_exists("window_step", $set_options)){
			$this->block_width=15;
			$this->window_width=90;
			$this->window_step=60;
			$this->config_change=True;
		}
		else{
			$this->block_width=$set_options["block_width"]; //number of minutes forming smallest block for changing desks - i.e. shortest time someone can be on a desk
			$this->smoothing_width=$set_options["smoothing_width"];
			$this->window_width=$set_options["window_width"]; //must be an even multiple of block width
			$this->window_step=$set_options["window_step"];
		}
		if(!array_key_exists("concavity_limit", $set_options)){
			$this->concavity_limit = 30;
			$this->config_change=True;
		}
		else{ $this->concavity_limit=$set_options["concavity_limit"];}
		if(!array_key_exists("existing_queue", $set_options)){ $this->existing_queue=0;}
		else{ $this->existing_queue=$set_options["existing_queue"];}
		if(!array_key_exists("existing_staff", $set_options)){ $this->existing_staff=0;}
		else{ $this->existing_staff=$set_options["existing_staff"];}
		if(!array_key_exists("smoothing_width", $set_options)){ $this->smoothing_width=$this->block_width;}
		else{$this->smoothing_width=$set_options["smoothing_width"];}
		if(!array_key_exists("min_desk", $set_options)){throw new Exception('Undefined minimum desk requirement');}
		else{$this->min_desk=$set_options["min_desk"];}
		if(!array_key_exists("max_desk", $set_options)){throw new Exception('Undefined maximum desk requirement');}
		else{ $this->max_desk=$set_options["max_desk"];}
		if(!array_key_exists("sla", $set_options)){throw new Exception('Undefined SLA');}
		else{ $this->sla=$set_options["sla"];}
		if(!array_key_exists("time_limit", $set_options)){throw new Exception('Undefined SLA');}
		else{ $this->time_limit=$set_options["time_limit"];}
		return 1;
	}

	private function validate_config($set_options){
		if($this->time_limit < 0) throw new Exception('Incorrectly configured. Time limit must be greater than 0'); 
		if($this->window_step > $this->window_width) throw new Exception('Incorrectly configured. Win Width is smaller than win step - so some bins will be skipped over in the calculation');
		if($this->concavity_limit <0) throw new Exception('Incorrectly configured. Concavity limit must be greater than 0');
		if($this->min_desk < 0) throw new Exception('Incorrectly configured. Cannot have negative numbers of desks open');
		if(!is_int($this->min_desk)) throw new Exception('Incorrectly configured. Minimum desks open must be an integer'); 
		if(!is_int($this->block_width) || $this->block_width<0) throw new Exception('Incorrectly configured. Block width must be a positive integer');
		if(!is_int($this->smoothing_width) || $this->smoothing_width<0) throw new Exception('Incorrectly configured. Smoothing width must be a positive integer');
		if(!is_int($this->window_width) || $this->window_width<0) throw new Exception('Incorrectly configured. Window width must be a positive integer');
		if($this->window_width % (2*$this->block_width) !=0)throw new Exception('Incorrectly configured. Window width should be an even multiple of Block width');
		if($this->work_length%$this->block_width !=0)throw new Exception('Incorrectly configured. Work is not divisible by block width');
		if($this->smoothing_width > $this->window_width)throw new Exception('Incorrectly configured. Smoothing width must be less than or equal to window width');
		if($this->window_step%$this->block_width!=0 || $this->window_step<0)throw new Exception('Incorrectly configured. Window step should be a multiple of or equal to block width');
		if($this->window_width > $this->work_length) $this->window_width = $this->work_length;
		foreach($this->max_desk as $testdesk) if($testdesk < 0 || !is_int($testdesk)) throw new Exception('Incorrectly configured, max_desk array must be all integers');
		foreach($this->max_desk as $testdesk) if($testdesk < $this->min_desk) throw new Exception('Incorrectly configured, max desk array must be all greater than or equal to min desks');
		return "1";
	}


	function Optimise($work, &$desks, $set_options){
		$simqueue=array();
		$this->no_manpower=False;
		$this->start_time=time();
		$this->work_length = count($work);
		if(count($work)!=count($desks)) throw new exception('work and desks are different lengths.Both should be 1440');
		$options=$this->configure($set_options);//}
		$win_start = 0;
		//call initial estimate which smooths out the work to provide a guess at how many desks should be open.
		$empty_arr=array();
		for($z=0; $z<$this->work_length;$z++){array_push($empty_arr, 0);}
		if($empty_arr==$desks){
		$desks=$this->initial_estimate($work, $this->block_width, $this->smoothing_width);
		$desks=$this->initial_estimate1($work, $this->block_width, $this->smoothing_width);
		$desks=$this->initial_estimate2($work, $this->block_width, $this->smoothing_width);}
		//Iterate over the windows. Windows will tend to overlap with each other. 
		try{
		for($time=$win_start; $time<count($work); $time=$time+$this->window_step){
			//Fill an array called current_work with the work within the window we are looking at.
			for($arraypart=0; $arraypart<$this->window_width; $arraypart++){
				$element = $time+$arraypart;
				$this->current_work[$arraypart]=$work[$element];
				//The current work not including any overlaps with the next step. Needed to figure out the residual queue at the start of the next step.
				if($arraypart < $this->window_step){
					$this->current_work_step[$arraypart]=$work[$element];
				}
			}
			$block_guess=array();
			$element_i=0;
			//fill an array with the guesses for each block - but condense each block down to 1 value (as opposed to one value repeated). 
			for($blocks=$time; $blocks < ($time+$this->window_width) ; $blocks=$blocks+$this->block_width){
				$block_guess[$element_i] = $desks[$blocks];
				$element_i++;
			}
			$block_i=0;
			$block_optimum_expand=array();
			$block_optimum_expand_step=array();
			//calls the optimisation function
			$block_optimum =$this->branch_bound($block_guess, $time);
			for($blocks=$time; $blocks < ($time+$this->window_width) ; $blocks=$blocks+$this->block_width){
				//refill the desks with the newly optimised estimate - with the blocks uncondensed (i.e. 1 value per minute instead of 1 value per block)	
				for($element_j=0; $element_j < $this->block_width; $element_j++){
					$desks[$blocks+$element_j] = $block_optimum[$block_i];
					//create a block optimum array with 1 value per minute not including any overlaps with the next step. Needed to figure out the residual queue at the start of the next step. 
					$timeandstep = $time+$this->window_step;
					if($blocks< $time+$this->window_step){
						if($blocks < count($work)){
							$block_num=$block_i*$this->block_width+$element_j;
							$block_optimum_expand_step[$block_num] = $block_optimum[$block_i];
						}
					}
				}
				$block_i++;
			}
			//Find the last desk of the window before any overlap in order to use as the churn start for the next window.
			$this->existing_staff=end($block_optimum_expand_step);
			//Find the residual queue at the end of the last step before any overlap. This residual queue will need to be processed at the start of the next window. 
			$simqueue=$this->process_work($this->current_work_step, $block_optimum_expand_step);
			if(count($simqueue["residual"]) > 0){
			$this->existing_queue=$simqueue["residual"];}
			else{$this->existing_queue = 0;} 
			if($simqueue["excess_wait"] > 0){$this->no_manpower = True;}
			//If we are approaching the end of the day and the day does not divide neatly into blocks of window_width, change the last window to be smaller so the whole day can be processed. 
			if($time+$this->window_step >(count($work)-$this->window_width)){
				$this->window_width=count($work)-($time+$this->window_step);
				//get rid of the bits of the arrays that are no longer needed as win width is now smaller. The values themselves are reset on the next loop through. 
				array_splice($this->current_work, $this->window_width);
				array_splice($this->current_work_step, $this->window_width);
			}
			if(time()-$this->start_time > $this->time_limit){
				if($this->config_change == True && $this->no_manpower==True) return "123";
				if($this->no_manpower == True) return "12";
				if($this->config_change == True) return "13";
				return "1";
			}
		}
		}
		catch(Exception $e){
			if($this->config_change == True) return "34";
			return "4";
		}	
		
		$this->find_best_init_est($desks);
		if($this->config_change == True && $this->no_manpower==True) return "23";
		if($this->no_manpower == True) return "2";
		if($this->config_change == True) return "3";
		else return "0";
	}
	private function initial_estimate1($work, $width, $s_width)
	{
		$timetest1=microtime(true);
		$arraybins = count($work)/$width;
		//Takes the maximum desks array and checks that it is in 15 minute blocks. If it is not - assume the lowest available number of desks available in a block is the maximum number of desks for that block.
		for($i=0; $i<$arraybins; $i++){
			$lowest_max_desk=9999999;
			for($j=0;$j<$width;$j++){
				$element=$i*$width+$j;
				if($this->max_desk[$element] < $lowest_max_desk) $lowest_max_desk=$this->max_desk[$element];
			}
			for($k=0;$k<$width;$k++){
				$element=$i*$width+$k;
				$this->max_desk[$element]=$lowest_max_desk;
			}
		}
		-$more_work_than_max_desk=0;
		$desk_rec=array();
		$shifted_work=array();
		for($i=0; $i<$arraybins;$i++){
			$block_mean=0;
			$work[$i*$width]+=$more_work_than_max_desk;
			for($j=0;$j<$width;$j++){
				$element=$i*$width+$j;
				print "$work[$element]  ";
				$block_mean+=$work[$element];
			}
			$block_mean=round($block_mean/$width);
			if($block_mean > $this->max_desk[$i*$width]) $block_mean = $this->max_desk[$i*$width];
			if($block_mean < $this->min_desk) $block_mean = $this->min_desk;
			print "desks = $block_mean \n";
			for($ave=0; $ave<$width; $ave++){
				$element=$i*$width+$ave;
				$desk_rec[$element]=$block_mean;
			}
			$more_work_than_max_desk=0;
			for($l = 0; $l<$width; $l++){
				$timeslot = $i*$width+$l;
				$shifted_work[$timeslot]=$work[$timeslot]+$more_work_than_max_desk;
				print "desks $desk_rec[$timeslot] shifted $shifted_work[$timeslot] work  $work[$timeslot] more $more_work_than_max_desk \n";
				if($shifted_work[$timeslot] > $desk_rec[$timeslot]) {$more_work_than_max_desk=$shifted_work[$timeslot]-$desk_rec[$timeslot];
					$shifted_work[$timeslot] = $desk_rec[$timeslot];
				}
				else{ $more_work_than_max_desk=0;}
			}
			print "$more_work_than_max_desk \n";
		}
		print "H2 \n";
		print_r($desk_rec);
		$this->test1=$desk_rec;
		$timeendtest1 = microtime(true)-$timetest1;
		print "TIMING TEST 1 $timeendtest1 \n";
		return $desk_rec;
	}
	private function initial_estimate2($work, $width, $s_width)
	{
		$timetest2 = microtime(true);
		$arraybins = count($work)/$width;
		//Takes the maximum desks array and checks that it is in 15 minute blocks. If it is not - assume the lowest available number of desks available in a block is the maximum number of desks for that block.
		for($i=0; $i<$arraybins; $i++){
			$lowest_max_desk=9999999;
			for($j=0;$j<$width;$j++){
				$element=$i*$width+$j;
				if($this->max_desk[$element] < $lowest_max_desk) $lowest_max_desk=$this->max_desk[$element];
			}
			for($k=0;$k<$width;$k++){
				$element=$i*$width+$k;
				$this->max_desk[$element]=$lowest_max_desk;
			}
		}
		$more_work_than_max_desk=0;
		$desk_rec=array();
		$shifted_work=array();
		for($i=0; $i<$arraybins;$i++){
			$block_mean=0;
			$work[$i*$width]+=$more_work_than_max_desk;
			for($j=0;$j<$width;$j++){
				$element=$i*$width+$j;
				print "$work[$element]  ";
				$block_mean+=$work[$element];
			}
			$block_mean=ceil($block_mean/$width);
			if($block_mean > $this->max_desk[$i*$width]) $block_mean = $this->max_desk[$i*$width];
			if($block_mean < $this->min_desk) $block_mean = $this->min_desk;
			print "desks = $block_mean \n";
			for($ave=0; $ave<$width; $ave++){
				$element=$i*$width+$ave;
				$desk_rec[$element]=$block_mean;
			}
			$more_work_than_max_desk=0;
			for($l = 0; $l<$width; $l++){
				$timeslot = $i*$width+$l;
				$shifted_work[$timeslot]=$work[$timeslot]+$more_work_than_max_desk;
				print "desks $desk_rec[$timeslot] shifted $shifted_work[$timeslot] work  $work[$timeslot] more $more_work_than_max_desk \n";
				if($shifted_work[$timeslot] > $desk_rec[$timeslot]) {$more_work_than_max_desk=$shifted_work[$timeslot]-$desk_rec[$timeslot];
					$shifted_work[$timeslot] = $desk_rec[$timeslot];
					print "$more_work_than_max_desk \n";
				}
				else{ $more_work_than_max_desk=0;
				}
			}
			print "$more_work_than_max_desk \n";
		}
		print "H3 \n";
		print_r($desk_rec);
		$this->test2=$desk_rec;
		$timeendtest2 = microtime(true)-$timetest2;
		print "TIMING TEST 2 $timeendtest2 \n";
		return $desk_rec;
	}


	private function initial_estimate($work, $width, $s_width)
	{
		$timetest = microtime(true);
		$arraybins = count($work)/$width;
		//Takes the maximum desks array and checks that it is in 15 minute blocks. If it is not - assume the lowest available number of desks available in a block is the maximum number of desks for that block.
		for($i=0; $i<$arraybins; $i++){
			$lowest_max_desk=9999999;
			for($j=0;$j<$width;$j++){
				$element=$i*$width+$j;
				if($this->max_desk[$element] < $lowest_max_desk) $lowest_max_desk=$this->max_desk[$element];
			}
			for($k=0;$k<$width;$k++){
				$element=$i*$width+$k;
				$this->max_desk[$element]=$lowest_max_desk;
			}
		}
		$shifted_work=array();
		$more_work_than_max_desk = 0;
		$desk_rec=array();
		for($timeslot = 0; $timeslot<count($work); $timeslot++){
			$shifted_work[$timeslot]=$work[$timeslot]+$more_work_than_max_desk;
			if($shifted_work[$timeslot] > $this->max_desk[$timeslot]) {$more_work_than_max_desk=$shifted_work[$timeslot]-$this->max_desk[$timeslot];
										   $shifted_work[$timeslot] = $this->max_desk[$timeslot];}
			else{$more_work_than_max_desk=0;}
		}
		$smoothwork=$shifted_work;
		//Applying a moving average to the work to smooth it out to help us guess desk requirements. Smooth using the previous s_width steps. Start at s_width to ensure there are sufficient previous bins to use for smoothing. 
		for($j=$s_width; $j < count($work); $j++){
			for($k=1;$k < $s_width ; $k++){
				$element_smooth = $j-$k;
				$smoothwork[$j]=$smoothwork[$j]+$shifted_work[$j-$k];
			}
			$smoothwork[$j]=$smoothwork[$j]/$s_width;
		}
		//Here - deal with the first s_width steps. Just take the mean and round it. 
		$storemean=0;
		for($j=0; $j<$s_width;$j++){
			$storemean=$storemean+$smoothwork[$j];
		}
		$storemean=$storemean/$s_width;
		for($j=0; $j<$s_width; $j++){
			$smoothwork[$j] = $storemean;
		}
		for($i=0; $i<$arraybins;$i++){
			$block_mean=0;
			$smooth_block_max=0;
			for($j=0;$j<$width;$j++){
				$element=$i*$width+$j;
				$block_mean+=$shifted_work[$element];
				if($smoothwork[$element]>$smooth_block_max) $smooth_block_max=$smoothwork[$element];
				print "shifted work - $shifted_work[$element] block mean $block_mean smooth work $smoothwork[$element] smooth max $smooth_block_max \n";
			}
			$block_mean=$block_mean/$width;
			$combine_est=ceil(($block_mean+$smooth_block_max)/2);
			print "final block mean $block_mean final smooth max $smooth_block_max \n";
			print "COMBINED EST - $combine_est \n";
			if($combine_est < $this->min_desk) $combine_est = $this->min_desk;
			for($ave=0; $ave<$width; $ave++){
				$element=$i*$width+$ave;
				$desk_rec[$element]=$combine_est;
			}
		}
		print "H1 \n";
		print_r($desk_rec);
		$this->test=$desk_rec;
		$timeendtest = microtime(true)-$timetest;
		print "TIMING TEST  $timeendtest \n";
		return $desk_rec;
	}

	private function branch_bound($starting_x, $time)
	{
		$x=$incumbent=$starting_x;
		$n=count($x);
		//Check the cost of the initial estimate.
		$best_so_far = $this->cost($incumbent);
		//Set up a 2-D array. For each desk guess, there is a list of neighbouring points, starting with those closest to the desk guess and moving outwards until both min_desk and max_desk are reached. 
		$points = $this-> neighbouring_points_setup($starting_x, $time);
		//move the cursor to the last desk in the window.
		$cursor = $n-1;
		while($cursor >= 0){
			while(count($points[$cursor])>0){
				if(time()-$this->start_time > $this->time_limit){
					return $incumbent;
				}
				//Set the desk guess to the next neighbouring point to be tested and remove the neighbouring point from the list. 
				$x[$cursor]=$points[$cursor][0];
				unset($points[$cursor][0]);
				$points[$cursor] = array_values($points[$cursor]);
				//Find the cost of this new desk guess.
				$trial_z = $this->cost($x);
				//If the cost is greater than the best guess PLUS the concavity limit, then this branch is not worth pursuing. Remove all points more extreme than the best guess so far.
				if($trial_z > ($best_so_far+$this->concavity_limit)){
					for($i=0; $i < count($points[$cursor]); $i++){
						//If the test guess was greater than the best guess so far, remove all other neighbouring points greater than the test guess.
						if($x[$cursor] > $incumbent[$cursor]){
							if($points[$cursor][$i] > $x[$cursor]) {
								unset($points[$cursor][$i]);
								$points[$cursor] = array_values($points[$cursor]);
								$i--;}	
						}
						elseif($x[$cursor] < $incumbent[$cursor]){
							if($points[$cursor][$i] < $x[$cursor]) {
								unset($points[$cursor][$i]); 
								$points[$cursor] = array_values($points[$cursor]);
								$i--;}
						}
					}
					continue;
					//move on to test the next neighbouring point that hasn't been removed. If all are removed, the while loop will exit as the length of points[cursor] will be 0.  
				}
				//If trial z has a lower cost than the best so far, set it as the new best so far.
				if($trial_z < $best_so_far){
					$incumbent = $x;
					$best_so_far = $trial_z;
				}
				//This point of the loop is reached if a new best so far is found or if the cost is greater than the best so far but still within the concavity limit. In this case, it is worth exploring the rest of the branch - so move onto the next desk along towards the end of the branch - only if we are not already on the last desk. (i.e. if we had 4 blocks per window - now desk 3 is optimised, try and optimise desk 4 with the new guess of desk 3.)
				if($cursor < $n-1){ $cursor = $cursor + 1;}
			}
			//Having explored the whole of the branch above, we need to move backwards along to the previous desk (in time). i.e. if we have optimised all of desks 3 and 4, now we need to optimise desk 2. We refill the points for the desk 3 and then move the cursor to desk 2. 
			$points= $this->neighbouring_points_refill($points, $incumbent[$cursor], $cursor, $time);
			$x[$cursor] = $incumbent[$cursor];
			$cursor = $cursor - 1;
		}
		return $incumbent;
	}

	private function cost($desk_guess)
	{	
		$expand_desk_guess=array();
		//We have been passing around the condensed version of the array of desk guesses - with 1 desk per block. In order to check the cost, we must expand this back to having 1 desk per minute. 
		for($array_el =0; $array_el < count($desk_guess); $array_el ++){
			for($i=0; $i<$this->block_width; $i++){
				$expand_desk_guess[$array_el*$this->block_width+$i] = $desk_guess[$array_el];
			}
		}
		//passing process_work the "current_work" which is the actual work arriving at the desks and the expanded desk guess. Each now has 1 array element per minute.  
		$simres=$this->process_work($this->current_work, $expand_desk_guess);
		//The Staff penalty is the number of wasted staff minutes. For each minute, the proportion of staff being used is subtracted from 1 to find the proportion of staff who are idle. This is multiplied by the number of desks - to find the actual amount of staff time wasted.
		$staff_penalty = 0;
		for($use = 0; $use < count($this->current_work); $use ++){
			$util = $simres["util"][$use];
			$staff_penalty += (1 - $util)*$expand_desk_guess[$use];
		}
		//The churn penalty represents the need to avoid constant staff handover. It represents the number of staff that have to reopen closed desks during the period. 
		$churn_penalty = $this->churn($desk_guess);
		//The passenger penalty is the total wait for passengers queueing. 
		$pax_penalty = $simres["total_wait"];
		//The sla penalty is the number of passenger minutes that exceed the sla. To avoid doing this, a high weight is associated with this penalty. 
		$sla_penalty = $simres["excess_wait"];
		//All penalties are multiplied by their relative weights and summed to find the total cost. 
		$total_penalty = $this->weight_pax*$pax_penalty+$this->weight_staff*$staff_penalty+$this->weight_churn*$churn_penalty+$this->weight_sla*$sla_penalty;
		return $total_penalty;
	}

	private function process_work($work, $capacity){
		$this_work_length = count($work);
		$capacity_length = count($capacity);
		if($this_work_length!=$capacity_length) throw new Exception('Internal error. Work length does not equal capacity length');
		//If there is any preexisting queue left over from the previous time window - this needs to be processed first. Note - as time windows frequently overlaap - we find existing_queue using the previous time window with overlap removed.  
		if(count($this->existing_queue) > 0){
			$startq = $this->existing_queue[0];
			$q=array("$startq");
			for($get_q =1 ;$get_q < count($this->existing_queue)-1; $get_q++){
				array_push($q, $this->existing_queue[$get_q]);
			}
		}
		else{
			//If there is no queue, just initialise the array with 0 queue. 
			$q = array("0");
		}
		$wait = array();
		$util = array();
		$total_wait = 0;
		$excess_wait = 0;
		//Loop through the time slots
		for($t=0; $t < count($work); $t++){
			if(time()-$this->start_time > $this->time_limit){
				$dummydata=array();
				for($i=0;$i<$this_work_length; $i++){array_push($dummydata, 0);}
				$return_to_cost = array("total_wait" => "99999999999999",
						"excess_wait"=>"0",
						"util"=>$dummydata,
						"residual"=>"0",
						);
				return $return_to_cost;
			}
			//Add the work from this minute to the queue.  
			array_push($q, $work[$t]);
			//Get the number of desks open. 
			$resource = $capacity[$t];
			//age is how long the queue is - how many minutes the passengers have been waiting. 
			$age = count($q);
			while($age > 0){
				//Surplus is how much resource is left after processing the first slot in the queue. 
				$surplus = $resource - $q[0];
				//If there is resource left over
				if($surplus >=0){
					//Add the length of time these passengers have been waiting to the queue. 
					$total_wait=$total_wait+$q[0]*($age-1);
					//Add the wait to excess wait if there has been an sla breach. 
					if($age - 1 >= $this->sla){
						$excess_wait = $excess_wait + $q[0]*($age-1);
					}
					//Remove the work in that slot from the queue as it has been processed.
					unset($q[0]);
					$q = array_values($q);
					//The resource that is left over in this slot. 
					$resource = $surplus;
				}
				else {
					//Add the wait of the passengers processed in this time slot to the queue. (this is not the passengers in the slot - as only some are processed - the rest will be waiting longer).
					$total_wait = $total_wait + $resource*($age-1);
					if($age-1 >= $this->sla){
						$excess_wait = $excess_wait + $resource*($age - 1);
					}
					//Remove the number of passengers processed from this queue slot. 
					$q[0] = $q[0] - $resource;
					$resource= 0;
					//All the resource has now been used up so move onto the next minute.  
					break;
				}
				//All of the resource has not been used up yet so move to the next slot of the queue. 
				$age = $age - 1;
			}
			$wait[$t] = count($q);
			$util[$t] = 1 - ($resource / $capacity[$t]);
		}

		//Let's find how many people are in the remaining queue after the timeslot is over
		$sum_rem_queue=array_sum($q);
		$lastdesk=end($capacity);
		$over_sla_wait = 0;
		if($sum_rem_queue !=0){
			//how long is capacity full? how many pax are left after full capacity?
			$full_cap=floor($sum_rem_queue/$lastdesk);
			$rem_pax = $sum_rem_queue-($full_cap*$lastdesk);
			//At end of timeslot - how long have pax been in queue?
			$qlength=count($q);
			for($pax=0;$pax <count($q); $pax++){
				$total_wait+=$q[$pax]*(count($q)-$pax-1);
			}
			//total wait for pax after end of time slot. (1st term - length of time capacity is full, 2nd term - process the remainder)
			$fullcapwait = $lastdesk*$full_cap*($full_cap+1)/2;
			$remwait = $rem_pax *($full_cap+1);

			$end_total_wait = $lastdesk * $full_cap*($full_cap+1)/2 + $rem_pax *($full_cap+1);
			$total_wait +=$end_total_wait;

			//minimal underutilised staff so no need for staff penalty. 
			//Excess wait must still be considered - only if longest wait is longer than sla. 
			//longest wait is the longest wait possible - unlikely in practice. Would be result of having loads of work that was not completely processed followed by very little work. 
			$longest_wait = count($q)+$full_cap;
			$is_over_sla_wait = $longest_wait - $this->sla;
			$queuepoint = count($q)-1;
			$excess_q=$q;
			$over_sla_wait = 0;
			//If it is possible that there may be a breach of the sla after the end of the timeslot - loop over the passengers as above to find any excess wait. 
			if($is_over_sla_wait > 0){
				for($ext_time = 0; $ext_time < $full_cap+1; $ext_time++){
					$rem_resource = $lastdesk;
					while(count($excess_q) > 0){
						$surplus = $rem_resource - $excess_q[0];
						if($surplus > 0){
							if(($queuepoint + $ext_time) >= $this->sla){	
								$extra = $excess_q[0]*($queuepoint+$ext_time);	
								$over_sla_wait+= $excess_q[0]*($queuepoint+$ext_time);}		
							$rem_resource = $surplus;
							unset($excess_q[0]);
							$excess_q=array_values($excess_q);
							$queuepoint-=1;
						}
						else{
							if(($queuepoint + $ext_time) >= $this->sla){
								$extra= $rem_resource*($queuepoint+$ext_time);
								$over_sla_wait+= $rem_resource*($queuepoint+$ext_time);}
							$excess_q[0]=$excess_q[0] - $rem_resource;
							break;
						}
					}
				}
			}
		}
		$excess_wait+=$over_sla_wait;
		$return_to_cost = array("total_wait" => "$total_wait",
				"excess_wait"=>"$excess_wait",
				"util"=>$util,
				"residual"=>$q,
				);
		return $return_to_cost;

	}

	private function churn($x){
		$churn = 0;
		$xlag = $this->existing_staff;
		for($i = 0; $i<count($x) ; $i++){
			$xnext = $x[$i];
			//Each time a member of staff has to open a new desk - add this number to churn. 
			if($xnext > $xlag){
				$churn =$churn + ($xnext - $xlag);
				$diff = $xnext - $xlag;
			}
			$xlag = $xnext;
		}
		return $churn;
	}

	private function neighbouring_points_refill($x, $orig, $cursor, $time){
		//Refill the neighbouring points at the point cursor. x is the array. orig is the best guess so far at the point cursor is at. cursor tells the function which points in x to refill. 
		$points = $x;
		$timeslot = $time+$cursor*$this->block_width;
		for($j = 1; $j < ($this->max_desk[$timeslot]-$this->min_desk)+1;$j++){
			if($orig-$j >= $this->min_desk) array_push($points[$cursor], ($orig-$j));
			if($orig+$j <= $this->max_desk[$timeslot]) array_push($points[$cursor], ($orig+$j));

		}
		return $points;
	} 

	private function neighbouring_points_setup($x, $time){
		//Fill all the points of x with neighbouring points - creating a 2 D array. 
		$points = $x;
		for($i = 0; $i< count($x) ; $i++){
			$timeslot = $time+$i*$this->block_width;
			$orig = $points[$i];
			$points[$i] = array();
			for($j = 1; $j < ($this->max_desk[$timeslot]-$this->min_desk)+1;$j++){
				if($orig-$j >= $this->min_desk) array_push($points[$i], ($orig-$j));
				if($orig+$j <= $this->max_desk[$timeslot]) array_push($points[$i], ($orig+$j));
			}
		}
		return $points;
	} 
	private function find_best_init_est($desks){
	print "got here !\n";
	$test1_sum=0;
	$test2_sum=0;
	$test3_sum=0;

	for($time =0; $time<count($desks); $time++){
		$test1_sum+=abs($desks[$time]-$this->test[$time]);
		$test2_sum+=abs($desks[$time]-$this->test1[$time]);
		$test3_sum+=abs($desks[$time]-$this->test2[$time]);	
		}
	print "H1 = $test1_sum H2 = $test2_sum H3 = $test3_sum \n";

		
	}


}
?>
