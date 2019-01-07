<?php 
//require 'db_socket.php';

class Check extends Sambungan {
	

	// Fungsi Memanggil Nama Anggota 
	function infoanggota($member_id){
		$name 	= $this->query('SELECT member_name, verify, bebas FROM member WHERE member_id='.$member_id.'');
		$r_name = $this->fetch_row($name);
		return $r_name;
	}
	/*// Fungsi Memanggil Varifikasi Anggota
	function verify($member_id){
		$verify_r 	= $this->query('SELECT verify FROM member WHERE member_id='.$member_id.'');
		$r_verify 	= $this->fetch_row($verify_r);
		return $r_verify[0];
	}
	*/
	// Fungsi Mengecek Keberadaan Anggota
	function check_password($password, $member_id){
		$myallquery 	= $this->query("SELECT * FROM member WHERE mpasswd=MD5('".$password."') AND (member_id='".$member_id."' OR pin='".$member_id."') ");
		$num_row_query 	= $this->num_rows($myallquery);
		return $num_row_query;
	}
	
	function check_num_row_member($member_id){
		$myallquery 	= $this->query('SELECT * FROM member WHERE member_id=\''.$member_id.'\' OR pin=\''.$member_id.'\'');
		$num_row_query 	= $this->num_rows($myallquery);
		return $num_row_query;
	}
	
	function member_take_id($input){
			$strinput = strlen($input);
			$strinput = $strinput-2;
			$member_id = substr($input,2,$strinput);
			return $member_id;
	}
	
	// Fungsi Memanggil Lama 
	/*function LoanPriod($member_id){
		$member = $this->query('SELECT mt.loan_periode 
								FROM member AS m 
								LEFT JOIN mst_member_type AS mt 
								ON m.member_type_id=mt.member_type_id
							    WHERE m.member_id='.$member_id.'');
		$m_row  = $this->fetch_row($member);
		$loanP	= $m_row[0];
		return $loanP;
	}*/
	
	// Memanggil Judul Buku
	function taketitle($item_code){
		$query_result 	= $this->query('SELECT b.title 
										FROM biblio AS b 
										LEFT JOIN item AS i 
										ON b.biblio_id=i.biblio_id 
										WHERE i.item_code=\''.$item_code.'\'');
		$myquery 		= $this->fetch_row($query_result);
		return $myquery[0];
	}
	
	// Memanggil ID Buku
	function takeBiblioID($item_code){
		$query 		= $this->query('SELECT b.biblio_id FROM item AS i LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id WHERE i.item_code=\''.$item_code.'\'');
		$myquery 	= $this->fetch_row($query);
		$num_Bid 	= $this->num_rows($query);
		$result[]   = $myquery[0];
		$result[]	= $num_Bid;
		return $result;
	}
	
	// Mengecek keberadaan Judul Buku
	function check_exist($biblioID, $member_id){
		$cek = $this->query('SELECT b.biblio_id FROM loan AS l 
							LEFT JOIN item AS i ON l.item_code=i.item_code 
							LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id 
							WHERE b.biblio_id=\''.$biblioID.'\' AND l.is_return=0 AND l.member_id=\''.$member_id.'\'');
		$num_check 	= $this->num_rows($cek);
		return $num_check;
	}
	
	// Mengecek Jumlah Anggota
	function check_num_member_loan($member_id){
	
		$loan_eject 	= $this->query('SELECT member_id FROM loan WHERE member_id='.$member_id.' AND is_return=0');
		$loan_num 		= $this->num_rows($loan_eject);
		return $loan_num;
	}
	
	// Tanggal Pengembalian
	function due_date($periode, $date){
		
		$datet 			= str_replace('-', '/', $date);
		$duedate_t 		= date('Ymd',strtotime($datet . "+$periode days"));
		$year = substr($duedate_t,0,4);
		$month = substr($duedate_t,4,2);
		$day = substr($duedate_t,6,2);

		$duedate	= $day.'/'.$month.'/'.$year;
		return $duedate;
	}
	
	function denda($datenow, $due_date){
	
		if($datenow > $due_date){
			$day_7 = $this->selisihHari($due_date, $datenow);
			
			echo "Date Now : ".$datenow."\n";
			echo "Due Date : ".$due_date."\n";
			echo '+++++++'.$day_7."++++++++\n";
			
			if($day_7 > 7){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	function takeduedate($member_id){
		$duedate 		= $this->query('SELECT due_date FROM loan WHERE member_id=\''.$member_id.'\' AND is_return=0');
		$num_rowsduedate= $this->num_rows($duedate);
		if($num_rowsduedate == 0){
			return false;
		}else{
			$duedate_check 	= $this->fetch_row($duedate);
			return $duedate_check[0];
		}
	}
	
	function paidrr($member_id){
		$paid 		= $this->query('SELECT fines_id FROM fines WHERE member_id=\''.$member_id.'\' AND lunas=0');
		$num_paid= $this->num_rows($paid);
		if($num_paid == 0){
			return false;
		}else{
			return true;
		}
	}
	
	function selisihHari($tglAwal, $tglAkhir){

		// memecah string tanggal awal untuk mendapatkan
		// tanggal, bulan, tahun
		$pecah1 = explode("-", $tglAwal);
		$date1 	= $pecah1[2];
		$month1 = $pecah1[1];
		$year1 	= $pecah1[0];

		// memecah string tanggal akhir untuk mendapatkan
		// tanggal, bulan, tahun
		$pecah2 = explode("-", $tglAkhir);
		$date2 	= $pecah2[2];
		$month2 = $pecah2[1];
		$year2 	= $pecah2[0];

		// mencari total selisih hari dari tanggal awal dan akhir
		$jd1 = GregorianToJD($month1, $date1, $year1);
		$jd2 = GregorianToJD($month2, $date2, $year2);

		$selisih = $jd2 - $jd1;
		return $selisih;
	}
	
	
	// Mengecek Eksemplar Yang Sama pada satu Member ID
	function SameEksemplar($item_code, $member_id){
	
		$eksemplar 		= $this->query('SELECT loan_id FROM loan WHERE item_code=\''.$item_code.'\' AND is_return=0 AND member_id=\''.$member_id.'\'');
		$num_eksemplar = $this->num_rows($eksemplar);
	
		return $num_eksemplar;
	}
	function LoanEksemplar($item_code,$member_id){
		
		$buku		=$this->query ('SELECT due_date FROM loan WHERE item_code=\''.$item_code.'\' AND is_return=0 AND renewed=1 AND member_id=\''.$member_id.'\'');
		$num_buku = $this->num_rows($buku);
				
		return $num_buku;
	}
	// Fungsi untuk mengecek kali perpanjangan
	/*function kaliperpajangan($member_id, $item_code){
		$membertype = $this->query('SELECT m.member_id, mst.reborrow_limit AS batas FROM member AS m 
									LEFT JOIN mst_member_type AS mst ON m.member_type_id=mst.member_type_id 
									LEFT JOIN loan AS l ON m.member_id=l.member_id 
									WHERE m.member_id=\''.$member_id.'\' AND l.item_code=\''.$item_code.'\' AND is_return=0 AND l.renewed>=batas');
		$result = $this->num_rows($membertype);
		
		if($result == 0){
			
			return $e_j = 1;
		}else{
			return $e_j = 0;
		}
	}*/
	
	// Mengecek Eksemplar Yang Sama pada beda Member ID
	function exist_eksemplar($item_code, $member_id){
		$eksemplar 		= $this->query('SELECT loan_id FROM loan WHERE item_code=\''.$item_code.'\' AND is_return=0 AND member_id!=\''.$member_id.'\'');
		$num_eksemplar = $this->num_rows($eksemplar);

		return $num_eksemplar;
	}

}
?>