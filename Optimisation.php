<?php

class Optimisation 
{
	private $xmin;
	private $xmax;
	private $sla;
	private $weight_sla;
	private $weight_pax;
	private $weight_staff;
	private $weight_churn;
	private $block_width;
	private $smoothing_width;
	private $win_width;
	private $win_step;
	private $concavity_limit;
	private $current_work = array();
	private $current_work_step = array();
	private $qstart;
	private $churn_start;
	private $time_break;

	function Optimise($work){
		$starttime = time();
		$this->time_limit = 10;
		$this->sla=12;
		$this->weight_sla=10;
		$this->weight_pax=1;
		$this->weight_staff=3;
		$this->weight_churn=45;
		$this->xmin=1; //The minimum number of people on desks
		$this->xmax=10; //Maximum number of people on desks
		$this->block_width = 15; //number of timeslots forming smallest block for changing desks - i.e. shortest time someone can be on a desk
		$this->smoothing_width = 15; 
		$this->win_width = 90; //must be an even multiple of block width
		$this->concavity_limit=30;
		$this->qstart=0;
		$this->churn_start=0;
		$this->win_step = 60;
		$worklength = count($work);
		$win_start = 0;
		if($this->win_step > $this->win_width) throw new Exception('Incorrectly configured. Win Width is smaller than win step - so some bins will be skipped over in the calculation');
		if($this->win_width > count($work)) $this->win_width = count($work);
		if($this->concavity_limit <0) throw new Exception('Incorrectly configured. Concavity limit must be greater than 0');
		if($this->time_limit <= 0) throw new Exception('Incorrectly configured. Time limit must be greater than 0'); 
		if($this->xmin < 0 || $this->xmax<0) throw new Exception('Incorrectly configured. Cannot have negative numbers of desks open');
		if($this->xmin > $this->xmax) throw new Exception('Incorrectly configured. Maximum desks open is smaller than minimum desks open');
		if(!is_int($this->xmin) || !is_int($this->xmax)) throw new Exception('Incorrectly configured. Minimum and maximum desks open must be integers'); 
		if(!is_int($this->block_width)) throw new Exception('Incorrectly configured. Block width must be an integer');
		if(!is_int($this->smoothing_width)) throw new Exception('Incorrectly configured. Smoothing width must be an integer');
		if(!is_int($this->win_width)) throw new Exception('Incorrectly configured. Window width must be an integer');
		if($this->win_width % (2*$this->block_width) !=0)throw new Exception('Incorrectly configured. Window width should be an even multiple of Block width');
		if(count($work)%$this->block_width !=0)throw new Exception('Incorrectly configured. Work is not divisible by block width');
		//call initial estimate which smooths out the work to provide a guess at how many desks should be open.
		$desks=$this->initial_est($work, $this->block_width, $this->smoothing_width);
		//Iterate over the windows. Windows will tend to overlap with each other. 
		for($time=$win_start; $time<count($work); $time=$time+$this->win_step){
			//Fill an array called current_work with the work within the window we are looking at.
			for($arraypart=0; $arraypart<$this->win_width; $arraypart++){
				$element = $time+$arraypart;
				$this->current_work[$arraypart]=$work[$element];
				//The current work not including any overlaps with the next step. Needed to figure out the residual queue at the start of the next step.
				if($arraypart < $this->win_step){
					$this->current_work_step[$arraypart]=$work[$element];
				}
			}
			$block_guess=array();
			$element_i=0;
			//fill an array with the guesses for each block - but condense each block down to 1 value (as opposed to one value repeated). 
			for($blocks=$time; $blocks < ($time+$this->win_width) ; $blocks=$blocks+$this->block_width){
				$block_guess[$element_i] = $desks[$blocks];
				$element_i++;
			}
			$block_i=0;
			$block_optimum_expand=array();
			$block_optimum_expand_step=array();
			//calls the optimisation function
			$block_optimum =$this->branch_bound($block_guess);
			for($blocks=$time; $blocks < ($time+$this->win_width) ; $blocks=$blocks+$this->block_width){
				//refill the desks with the newly optimised estimate - with the blocks uncondensed (i.e. 1 value per minute instead of 1 value per block)	
				for($element_j=0; $element_j < $this->block_width; $element_j++){
					$desks[$blocks+$element_j] = $block_optimum[$block_i];
					//create a block optimum array with 1 value per minute not including any overlaps with the next step. Needed to figure out the residual queue at the start of the next step. 
					$timeandstep = $time+$this->win_step;
					if($blocks< $time+$this->win_step){
						if($blocks < count($work)){
						$block_optimum_expand_step[$block_i*$this->block_width+$element_j] = $block_optimum[$block_i];
						}
					}
				}
				$block_i++;
			}
			//Find the last desk of the window before any overlap in order to use as the churn start for the next window.
			$this->churn_start=end($block_optimum_expand_step);
			//Find the residual queue at the end of the last step before any overlap. This residual queue will need to be processed at the start of the next window. 
			$simqueue= $this->process_work($this->current_work_step, $block_optimum_expand_step);
			$this->qstart=$simqueue["residual"];  
			//If we are approaching the end of the day and the day does not divide neatly into blocks of win_width, change the last window to be smaller so the whole day can be processed. 
			if($time+$this->win_step >(count($work)-$this->win_width)){
				$this->win_width=count($work)-($time+$this->win_step);
				//get rid of the bits of the arrays that are no longer needed as win width is now smaller. The values themselves are reset on the next loop through. 
				array_splice($this->current_work, $this->win_width);
				array_splice($this->current_work_step, $this->win_width);
			}
			$newtime = time();
			$timepassed = $newtime - $starttime;
			//If the processing time exceeds the time limit, return our best guess so far. 
			if($timepassed > $this->time_limit){
				return $desks;
			}
		}
		return $desks;
	}

	private function initial_est($work, $width, $s_width)
	{
		$smoothwork=$work;
		$desks=array();
		//Applying a moving average to the work to smooth it out to help us guess desk requirements. Smooth using the previous s_width steps. Start at s_width to ensure there are sufficient previous bins to use for smoothing. 
		for($j=$s_width; $j < count($work); $j++){
			for($k=1;$k < $s_width ; $k++){
				$element_smooth = $j-$k;
				$rounded_work = round($work[$element_smooth]);
				$smoothwork[$j]=$smoothwork[$j]+round($work[$j-$k]);
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
			$smoothwork[$j] = round($storemean);
		}
		$work=$smoothwork;
		$arraybins=count($work)/$width;
		//Now form the data into the blocks. Each block must have the same number of desks. The block is the minimum time a desk can be open for. 
		for($i=0; $i<$arraybins; $i++){
			$mean=0;
			//To make an estimate for each block, take the mean of the smoothed work in each block. 
			for($j=0;$j<$width;$j++){
				$element=$i*$width+$j;
				$mean=$mean+round($work[$element]);
			}
			$mean=ceil($mean/$width);
			//If the mean is larger or smaller than xmax or xmin then set to xmax or xmin. 
			if($mean<$this->xmin) $mean=$this->xmin;
			if($mean>$this->xmax) $mean=$this->xmax;
			//For the length of block, add to the array the number of desks estimate. 
			for($k=0;$k<$width;$k++){
				array_push($desks, "$mean");
			}
		}
		return $desks;
	}

	private function branch_bound($starting_x)
	{
		$x=$incumbent=$starting_x;
		$n=count($x);
		//Check the cost of the initial estimate.
		$best_so_far = $this->cost($incumbent);
		//Set up a 2-D array. For each desk guess, there is a list of neighbouring points, starting with those closest to the desk guess and moving outwards until both xmin and xmax are reached. 
		$points = $this-> neighbouring_points_setup($starting_x);
		//move the cursor to the last desk in the window.
		$cursor = $n-1;
		while($cursor >= 0){
			while(count($points[$cursor])>0){
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
			$points= $this->neighbouring_points_refill($points, $incumbent[$cursor], $cursor);
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
		$simres =$this->process_work($this->current_work, $expand_desk_guess);
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
		$work_length = count($work);
		$capacity_length = count($capacity);
		if($work_length!=$capacity_length) throw new Exception('Internal error. Work length does not equal capacity length');
		//If there is any preexisting queue left over from the previous time window - this needs to be processed first. Note - as time windows frequently overlaap - we find qstart using the previous time window with overlap removed.  
		if(count($this->qstart) > 0){
			$startq = $this->qstart[0];
			$q=array("$startq");
			for($get_q =1 ;$get_q < count($this->qstart)-1; $get_q++){
				array_push($q, $this->qstart[$get_q]);
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
			for($pax=0;$pax <count($q); $pax++){
				$adding_tot = $q[$pax]*(count($q)-2-$pax);
				$total_wait+=$q[$pax]*(count($q)-2-$pax);
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
		$xlag = $this->churn_start;
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

	private function neighbouring_points_refill($x, $orig, $cursor){
		//Refill the neighbouring points at the point cursor. x is the array. orig is the best guess so far at the point cursor is at. cursor tells the function which points in x to refill. 
		$points = $x;
		for($j = 1; $j < ($this->xmax-$this->xmin);$j++){
			if($orig-$j >= $this->xmin) array_push($points[$cursor], ($orig-$j));
			if($orig+$j <= $this->xmax) array_push($points[$cursor], ($orig+$j));

		}
		return $points;
	} 

	private function neighbouring_points_setup($x){
		//Fill all the points of x with neighbouring points - creating a 2 D array. 
		$points = $x;
		for($i = 0; $i< count($x) ; $i++){
			$orig = $points[$i];
			$points[$i] = array();
			for($j = 1; $j < ($this->xmax-$this->xmin);$j++){
				if($orig-$j >= $this->xmin) array_push($points[$i], ($orig-$j));
				if($orig+$j <= $this->xmax) array_push($points[$i], ($orig+$j));

			}
		}
		return $points;
	} 
}
?>
