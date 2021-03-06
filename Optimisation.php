<?php

class Optimisation 
{
	private $no_manpower;
	private $config_change;
	private $timed_out;
	private $time_end;
	private $min_desk = array();
	private $max_desk = array();
	private $sla;
	private $weight_sla;
	private $weight_pax;
	private $weight_staff;
	private $weight_churn;
	private $block_width;
	private $window_width;
	private $window_step;
	private $concavity_limit;
	private $current_work = array();
	private $current_work_step = array();
	private $existing_queue;
	private $input_queue;
	private $input_queue_time;
	private $existing_staff;
	private $time_limit;
	private $work_length;
	public  $desks;
	private $start_time;
	private $current_timeslot;


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
		$this->window_width=$set_options["window_width"]; //must be an even multiple of block width
		$this->window_step=$set_options["window_step"];
	}
	if(!array_key_exists("concavity_limit", $set_options)){
		$this->concavity_limit = 30;
		$this->config_change=True;
	}
	else{ $this->concavity_limit=$set_options["concavity_limit"];}
	if(!array_key_exists("input_queue_time", $set_options) && array_key_exists("input_queue", $set_options) && $set_options["input_queue"]!=0)
		throw new Exception('Input queue length specified but also need input queue time');
	elseif(!array_key_exists("input_queue_time", $set_options)) $this->input_queue_time = -1;
	else{ $this->input_queue_time = $set_options["input_queue_time"];}
	if(!array_key_exists("input_queue", $set_options) && array_key_exists("input_queue_time", $set_options))
		throw new Exception('Input queue time specified but not input queue length');
	if(!array_key_exists("input_queue", $set_options)){ $this->input_queue=0;}
	else{ $this->input_queue=$set_options["input_queue"];}
	if(!array_key_exists("min_desk", $set_options)){throw new Exception('Undefined minimum desk requirement');}
	elseif(count($set_options["min_desk"])==1){for($push=0; $push < $this->work_length; $push++){array_push($this->min_desk, $set_options["min_desk"]);}}
	else{$this->min_desk=$set_options["min_desk"];}
	if(!array_key_exists("max_desk", $set_options)){throw new Exception('Undefined maximum desk requirement');}
	elseif(count($set_options["max_desk"])==1){for($push=0; $push < $this->work_length; $push++){array_push($this->max_desk, $set_options["max_desk"]);}}
	else{$this->max_desk=$set_options["max_desk"];}
	if(!array_key_exists("sla", $set_options)){throw new Exception('Undefined SLA');}
	else{ $this->sla=ceil(0.8*$set_options["sla"]);}
	if(!array_key_exists("time_limit", $set_options)){throw new Exception('Undefined SLA');}
	else{ $this->time_limit=$set_options["time_limit"];}
	return 1;
}

private function validate_config($set_options){
	if($this->time_limit < 0) throw new Exception('Incorrectly configured. Time limit must be greater than 0'); 
	if($this->window_step > $this->window_width) throw new Exception('Incorrectly configured. Win Width is smaller than win step - so some bins will be skipped over in the calculation');
	if($this->concavity_limit <0) throw new Exception('Incorrectly configured. Concavity limit must be greater than 0');
	foreach($this->min_desk as $min){ if($min < 0) throw new Exception('Incorrectly configured. Cannot have negative numbers of desks open');
		if(!is_int($min)) throw new Exception('Incorrectly configured. Minimum desks open must be an integer');}
	if(!is_int($this->block_width) || $this->block_width<0) throw new Exception('Incorrectly configured. Block width must be a positive integer');
	if(!is_int($this->window_width) || $this->window_width<0) throw new Exception('Incorrectly configured. Window width must be a positive integer');
	if($this->window_width % (2*$this->block_width) !=0)throw new Exception('Incorrectly configured. Window width should be an even multiple of Block width');
	if($this->work_length%$this->block_width !=0)throw new Exception('Incorrectly configured. Work is not divisible by block width');
	if($this->window_step%$this->block_width!=0 || $this->window_step<0)throw new Exception('Incorrectly configured. Window step should be a multiple of or equal to block width');
	if($this->window_width > $this->work_length) $this->window_width = $this->work_length;
	foreach($this->max_desk as $testdesk) if($testdesk < 0 || !is_int($testdesk)) throw new Exception('Incorrectly configured, max_desk array must be all integers');
	for($deskloop=0; $deskloop < count($this->max_desk); $deskloop++){ if($this->max_desk[$deskloop] < $this->min_desk[$deskloop]) throw new Exception('Incorrectly configured, max desk array must be all greater than or equal to min desks');}
	//	if(!is_int($this->input_queue_time) || $this->input_queue_time < 0) throw new Exception('Incorrectly configured. Input queue time must be a positive integer');
	return "1";
}


function Optimise($work, &$desks, &$queues, $set_options){
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
		$desks=$this->initial_estimate($work, $this->block_width);
		$queues = $this->calculate_queue_length($work, $desks);}
		//Iterate over the windows. Windows will tend to overlap with each other. 
		try{
			for($time=$win_start; $time<count($work); $time=$time+$this->window_step){
				$this->current_timeslot = $time;
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
				$queue_array = array();
				//If we are approaching the end of the day and the day does not divide neatly into blocks of window_width, change the last window to be smaller so the whole day can be processed. 
				if($time+$this->window_step >(count($work)-$this->window_width)){
					$this->window_width=count($work)-($time+$this->window_step);
					//get rid of the bits of the arrays that are no longer needed as win width is now smaller. The values themselves are reset on the next loop through. 
					array_splice($this->current_work, $this->window_width);
					array_splice($this->current_work_step, $this->window_width);
				}
				if(time()-$this->start_time > $this->time_limit){
					$queues = $this->calculate_queue_length($work, $desks);
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
$queues = $this->calculate_queue_length($work, $desks);
if($this->config_change == True && $this->no_manpower==True) return "23";
if($this->no_manpower == True) return "2";
if($this->config_change == True) return "3";
else return "0";
}

private function initial_estimate($work, $width)
{
	$timetest2 = microtime(true);
	$arraybins = count($work)/$width;
	//Takes the maximum desks array and checks that it is in 15 minute blocks. If it is not - assume the lowest available number of desks available in a block is the maximum number of desks for that block.
	for($i=0; $i<$arraybins; $i++){
		$lowest_max_desk=9999999;
		$highest_min_desk=0;
		for($j=0;$j<$width;$j++){
			$element=$i*$width+$j;
			if($this->max_desk[$element] < $lowest_max_desk) $lowest_max_desk=$this->max_desk[$element];
			if($this->min_desk[$element] > $highest_min_desk) $highest_min_desk = $this->min_desk[$element];
		}
		for($k=0;$k<$width;$k++){
			$element=$i*$width+$k;
			$this->max_desk[$element]=$lowest_max_desk;
			$this->min_desk[$element] = $highest_min_desk;
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
			$block_mean+=$work[$element];
		}
		$block_mean=ceil($block_mean/$width);
		if($block_mean > $this->max_desk[$i*$width]) $block_mean = $this->max_desk[$i*$width];
		if($block_mean < $this->min_desk[$i*$width]) $block_mean = $this->min_desk[$i*$width];
		for($ave=0; $ave<$width; $ave++){
			$element=$i*$width+$ave;
			$desk_rec[$element]=$block_mean;
		}
		$more_work_than_max_desk=0;
		for($l = 0; $l<$width; $l++){
			$timeslot = $i*$width+$l;
			$shifted_work[$timeslot]=$work[$timeslot]+$more_work_than_max_desk;
			if($shifted_work[$timeslot] > $desk_rec[$timeslot]) {$more_work_than_max_desk=$shifted_work[$timeslot]-$desk_rec[$timeslot];
				$shifted_work[$timeslot] = $desk_rec[$timeslot];
			}
			else{ $more_work_than_max_desk=0;
			}
		}
	}
	$timeendtest2 = microtime(true)-$timetest2;
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
			if($cursor < $n-1){ $cursor = $cursor + 1; }
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
	//print "staff pen : $staff_penalty churn pen : $churn_penalty pax pen : $pax_penalty sla pen : $sla_penalty \n";
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
		for($get_q =1 ;$get_q < count($this->existing_queue); $get_q++){
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
		if($this->current_timeslot+$t == $this->input_queue_time) $q = array($this->input_queue);
		//Add the work from this minute to the queue.  
		array_push($q, $work[$t]);
		//Get the number of desks open. 
		$resource = $capacity[$t];
		//age is how long the queue is - how many minutes the passengers have been waiting. 
		$age = count($q);
		//print "queue : ";
		//print_r($q);
		//print "resource = $resource \n";
		while($age > 0){
			//if($t==0 || $t==(count($work)-1)){
			//Surplus is how much resource is left after processing the first slot in the queue. 
			$surplus = $resource - $q[0];
			//If there is resource left over
			if($surplus >=0){
				//Add the length of time these passengers have been waiting to the queue.
				//print "length they have been waiting $q[0] \n";
				$total_wait=$total_wait+$q[0]*($age-1);
				//print "new total wait $total_wait \n";
				//Add the wait to excess wait if there has been an sla breach. 
				if($age - 1 >= $this->sla){
					$excess_wait = $excess_wait + $q[0]*($age-1);
				}
				//Remove the work in that slot from the queue as it has been processed.
				unset($q[0]);
				$q = array_values($q);
				//The resource that is left over in this slot. 
				$resource = $surplus;
				//print "leftover resource = $resource \n";
			}
			else {
				//Add the wait of the passengers processed in this time slot to the queue. (this is not the passengers in the slot - as only some are processed - the rest will be waiting longer).
				//print "length they have been waiting $resource \n";
				$total_wait = $total_wait + $resource*($age-1);
				if($age-1 >= $this->sla){
					$excess_wait = $excess_wait + $resource*($age - 1);
				}
				//Remove the number of passengers processed from this queue slot. 
				$q[0] = $q[0] - $resource;
				$resource= 0;
				//print "leftover resource = $resource \n";
				//All the resource has now been used up so move onto the next minute.  
				//print "total wait = $total_wait \n";
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
	if($sum_rem_queue !=0 && $lastdesk > 0){
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
					if($surplus >=0){
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
	if($sum_rem_queue !=0 && $lastdesk == 0){
		$total_wait+=(count($q)+$this->window_width)*$sum_rem_queue;
		if(count($q) - 1 > $this->sla){
			$excess_wait+=(count($q)-1-$this->sla+$this->window_width)*$sum_rem_queue;
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
		for($j = 1; $j < ($this->max_desk[$timeslot]-$this->min_desk[$timeslot])+1;$j++){
			if($orig-$j >= $this->min_desk[$timeslot]) array_push($points[$cursor], ($orig-$j));
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
			for($j = 1; $j < ($this->max_desk[$timeslot]-$this->min_desk[$timeslot])+1;$j++){
				if($orig-$j >= $this->min_desk[$timeslot]) array_push($points[$i], ($orig-$j));
				if($orig+$j <= $this->max_desk[$timeslot]) array_push($points[$i], ($orig+$j));
			}
		}
		return $points;
	} 

	private function calculate_queue_length($work, $capacity){
		$wait = array();
		$orig_wait = array();
		$util = array();
		$total_wait = 0;
		$excess_wait = 0;
		$q=array();
		//Loop through the time slots
		$average_utilisation=0;
		for($t=0; $t < count($work)+60; $t++){
			if($t == $this->input_queue_time){$q = array($this->input_queue); } 
			//Add the work from this minute to the queue.  
			if($t<count($work)){
				array_push($q, $work[$t]);}
			else{array_push($q, 0);}
			//print "time = $t \n";
			//print " work added  \n";
			//print_r($work[$t]);
			//print " total queue \n";
			//print_r($q);
			//Get the number of desks open. 
			if($t < count($work)){
				$resource = $capacity[$t];}
			else{$resource = $capacity[count($work)-1];}
			//age is how long the queue is - how many minutes the passengers have been waiting. 
			$age = count($q);
			while($age > 0){
				//Surplus is how much resource is left after processing the first slot in the queue. 
				$surplus = $resource - $q[0];
				//If there is resource left over
				if($surplus >=0){
					//Add the length of time these passengers have been waiting to the queue. 
					$total_wait=$total_wait+$q[0]*($age-1);
					$wait[$t] = 0;
					if($q[0] > 0){
						$q_len=count($q)-1;
						$wait[$t-$q_len] = $q_len;
					}
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
					if($q[0] > 0){
						$wait[$t] = 0;
						$q_len=count($q)-1;
						$wait[$t-$q_len] = $q_len;
					}
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
			$orig_wait[$t]=count($q);
			if($t < count($work)){
				$util[$t] = 1 - ($resource / $capacity[$t]);
				$average_utilisation+=1-$resource/$capacity[$t];}
		}
		$average_utilisation = $average_utilisation/count($work);
		for($time =1; $time < count($work); $time++){
			if($wait[$time]==0 && $wait[$time-1] > 1){
				$wait[$time]=$wait[$time-1]-1;	
			}
		}
		for($remove = count($work); $remove < count($work)+60; $remove++){
			unset($wait[$remove]);
		}
		return $wait;	

	}


}
?>
