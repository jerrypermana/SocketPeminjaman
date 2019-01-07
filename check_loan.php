<?php 
class Check_loan extends Check {

	function info($memberID){
	
	$loan = $this->query('SELECT item_code, loan_date, due_date FROM loan WHERE member_id=\''.$memberID.'\' AND is_return=0 AND return_date is NULL');
		if ($this->num_rows($loan) > 0){
			$AT = '';
			$AT_sum = 0;
			$AU = '';
			$AU_sum = 0;
			while($a_loan = $this->fetch_row($loan)){
				
				$AU .= 'AU'.$a_loan[0].'|';
				$AU_sum++;
				
				if($this->_AT($a_loan[2]) == true){
					$AT .= 'AT'.$a_loan[0].'|';
					$AT_sum++;
				}
			}
			$AT = substr_replace($AT, '', -1);
			$AU = substr_replace($AU, '', -1);
			
			$AT_sum = $this->bil_conv($AT_sum);
			$AU_sum = $this->bil_conv($AU_sum);
			
			$list_info[0] = '0000'.$AT_sum.$AU_sum.'000000000000';
			$list_info[1] = $AT.'|'.$AU;
			
		}else{
			$list_info[0] = '000000000000000000000000';
			$list_info[1] = 'AT0|AU0';
		}
		
		echo var_dump($list_info);
		return $list_info;
	}

	function _AT($duedate){
		$date = date("Y-m-d");
		if($date > $duedate){
			 return true;
		}else{
			return false;
		}
	}
	
	function bil_conv($bil){
		if(strlen($bil) == 1){
			$ex_bil = '000'.$bil;
		}elseif(strlen($bil) == 2 ){
			$ex_bil = '00'.$bil;
		}elseif(strlen($bil) == 3){
			$ex_bil = '0'.$bil;
		}else{
			$ex_bil = $bil;
		}
		
		return $ex_bil;
	}
}
?>