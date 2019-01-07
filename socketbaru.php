<?php
require 'db_socket.php';
require 'check.php';
require 'configure.php';
require 'check_loan.php';


$nomoritem 		= '';
$date 			= date("Ymd");
$date_1			= date("Y-m-d");
$hours 			= date("His");
$member_global  = '';
$dbs->OpenLink();
$check 			= new Check();
$check_loan		= new Check_loan();
set_time_limit(0);

// create socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Could not create socket\n");
echo "\n Sukses create";
echo "\n Host".$host;
echo "\n port".$port;
echo "\n socket".$socket;

// bind socket to port
$result = socket_bind($socket, $host, $port) or die("Could not bind to socket \n");

// put server into passive state and listen for connections
echo "\n SUKSES BINDING";
echo "\n Listening... ";
$result = socket_listen($socket, 5) or die("Could not set up socket listener \n");

do {

		echo "\n Accept... ";
		// accept incoming connections
		$com = socket_accept($socket) or die("Could not accept incoming connection \n");
	
	do {
		session_start();
		
		$input = "";
		$input = socket_read($com, 1048) or die("Could not read input \n");
		echo $input."\n";
		echo "\n Client says: ".$input."\n";
		// clean up input string

		$input 		= trim($input);
		$duadepan	="";
		$duadepan 	= substr($input,0,2);//ambil 2 char diawal
		echo "\n Ambil 2 Char: ".$duadepan."\n";
		
		/**
		  PROSES START UP PC SC
		**/
		if ($duadepan == '99'){
			$sembilanbelakang = substr($input,10,9).chr(13);
			
			// Menggenerate Pesan
			$message = '98YYYYYY100003'.$date.'    '.$hours.'2.00AOUNSYIAH|BXYYYNYYYYNYYNNNYN|'.$sembilanbelakang;
		}
		
		/**
		  PROSES MENGAMBIL DATA ANGGOTA DARI DATABASE
		**/
		if ($duadepan == '63') {
			$result_string = "";
			$result_string = substr($input,38,10); //ambil member ID
			$sembilanbelakang = substr($input,-9,9).chr(13);
			$input1 = explode('|',$input);
			$strinput = strlen($input1[1]);
			$strinput = $strinput-2;
			$member_id = substr($input1[1],2,$strinput);
			echo '######'.$member_id.'###<br>';
			unset($_SESSION['memberID']);
			$_SESSION['memberID']  = $member_id;
			echo $_SESSION['memberID']."----====\n";
			$info = $check_loan->info($member_id);
			
			// Mengecek Jumlah Anggota Pada Database Dengan ID tertentu
			$num_row = $check->check_num_row_member($member_id);
			if($num_row < 1){
			
				// Menggenerate Pesan
				$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AFANDA BELUM MENJADI ANGGOTA PERPUSTAKAAN UNSYIAH|".$sembilanbelakang;
			}else{
			
				// Memanggil Nama Anggota
				$member_name = $check->namaanggota($member_id);
				
				// Memanggil Status Verifikasi dari Anggota
				$verify		 = $check->verify($member_id);
				if($verify == 0){
				
					// Menggenerate Pesan
					$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|AE".$member_name."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AFANDA BELUM MELAKUKAN VERIFIKASI, SILAHKAN HUBUNGI PETUGAS SIRKULASI!!!|".$sembilanbelakang;
				}else{
					

					$tanggal_kembali = $check->takeduedate($member_id);
					
					if($tanggal_kembali == false){	$cek_denda 	     = false;}
					else{							$cek_denda   	 = $check->denda($date_1, $tanggal_kembali);}
						
					if($cek_denda == true){
						$pesan 		= 'ANDA TELAH DIKENAKAN DENDA SELAMA LEBIH DARI 7 HARI. SILAHKAN HUBUNGI PETUGAS SIRKULASI UNTUK MELUNASI TAGIHANNYA';
						$message 	= "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|AE".$member_name."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AF".$pesan."|".$sembilanbelakang;
					}else{
						// Menggenerate Pesan
						$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|AE".$member_name."|BHIDR|BLY|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|".$sembilanbelakang;
					}
				}
			}
			
			echo $message."\n";
		}
		
		/**
		  PROSES MENGAMBIL DATA KATALOG BUKU DARI DATABASE
		**/
		
		if ($duadepan == '17'){
			$sembilanbelakang 	= substr($input,-9,9).chr(13);
			echo $sembilanbelakang."\n";
			//echo $_SESSION['memberID']."----====\n";
			
			$input 				= explode('|',$input);
			$item_code 			= substr($input[1],2,15);
			echo $item_code.'-----<br>';
			
			
				$judul 				= $check->taketitle($item_code);
				echo "1<br>";
				if(isset($_SESSION['memberID'])){
					$periode			= $check->LoanPriod($_SESSION['memberID']);
				}else{
					$periode 			= 1;
				}
				
				echo "2<br>";
				$duedate			= $check->due_date($periode,$date_1);
				$_SESSION['duedate'][$item_code] = $duedate;
				echo 'TANGGAL PENGEMBALIAN :    '.$duedate.'<br>';
				$message 			= "18080001".$date."    ".$hours."CF0|AB".$item_code."|AJ".$judul."|AH".$duedate."|APPustaka UNSYIAH|CK001|".$sembilanbelakang;
			
			// Menggenerate Pesan
			echo $message."\n";
		}
		
		/**
		  PROSES INSERT DATA PEMINJAMAN KE DALAM DATABASE
		**/
		if ($duadepan == '11'){
			
			$explode 			= explode("|", $input);
			$sembilanbelakang 	= substr($input,-9,9).chr(13);
			$member_id 			= substr($explode[1],2);
			$item_code 			= substr($explode[2],2);
			// Memanggil Judul Buku
			$judul_11 			= $check->taketitle($item_code);
			// Memanggil Biblio ID dari Eksemplar
			$biblio_id 			= $check->takeBiblioID($item_code);
			// Mengecek Judul Buku sama
			$num_cek 			= $check->check_exist($biblio_id, $member_id);
			$perp				= 'N';
			// Mengecek Skenario Penolakan
			$cekeksemplar = 	$check->SameEksemplar($item_code, $member_id);

			if($cekeksemplar == 0){
				if($num_cek > 0){
					$duedate_r = '';
					echo $num_cek."\n";
					$e_j = 0;
					$pesan = 'Anda Tidak Dapat Meminjam Buku dengan Judul Yang sama';
				}else{
					// Memanggil Member_ID, Lama Peminjaman, Batas Peminjaman Dari Anggota
					$member 		= $dbs->query('SELECT m.member_id, mt.loan_periode, mt.loan_limit FROM member AS m 
										   LEFT JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
										   WHERE m.member_id='.$member_id.'');
					$memberloan 	= $dbs->fetch_row($member);
					$loanpriode 	= $memberloan[1];
					$MemberID 		= $memberloan[0];
					$loanlimit		= $memberloan[2];
					$timenow 		= date('H:i:s');
					$time_q 		= explode(":",$timenow);
					$time_j 		= $time_q[0].$time_q[1].$time_q[2];
					$datenow 		= date('Y-m-d');
					$datesplit 		= explode("-",$datenow);
					$datejoin 		= $datesplit[0].$datesplit[1].$datesplit[2];
					$date1 			= str_replace('-', '/', $datenow);
					$duedate 		= date('Y-m-d',strtotime($date1 . "+$loanpriode days"));
					
					echo "6 \n";
					
					$exist_eksemplar = $check->exist_eksemplar($item_code, $member_id);
					
					if($exist_eksemplar == 0){
						// Mengecek Kuota Peminjaman Buku
						$loan_num 		= $check->check_num_member_loan($member_id);
						if($loan_num >= $loanlimit){
							$e_j = 0;
							$pesan = 'Jumlah buku yang Anda Pinjam Melebihi Kuota Peminjaman';
						}else{
							$e_j = 1;
							echo "7 \n";
							$pesan = 'Selamat!!!. Anda Berhasil Melakukan Peminjaman Buku';
							// Insert Data Peminjaman ke Dalam Tabel Loan di Database
							$insertloan 	= $dbs->query("INSERT INTO loan(item_code, member_id, loan_date, due_date, is_lent) VALUES ('$item_code','$member_id','$datenow','$duedate',1)");
						}
					}else{
						$e_j = 0;
						$pesan = 'EKSEMPLAR SEDANG DIPINJAM';
					}
				}
			}else{
				$extend = $check->kaliperpajangan($member_id, $item_code);
					if($extend == 1){
						$e_j = 0 ;

					}else{
						$pesan = 'JUMLAH KALI PERPAJANGAN ANDA TELAH MELEBIHI KUOTA';
						$e_j = 0;
					}
			}
			// Menggenerate Pesan
			$message ='12'.$e_j.'YNY'.$date.'    '.$hours.'AOUNSYIAH|AA'.$member_id.'|AB'.$item_code.'|AJ'.$judul_11.'|AF'.$pesan.'|CK001|'.$sembilanbelakang; 
			//session_destroy();
		}
		
		// RENEW/PERPANJANGAN
		if($duadepan == '29'){
			$sembilanbelakang 	= substr($input,-9,9).chr(13);
			echo $sembilanbelakang."\n";
			$split = explode('|',$input);
			$strinput = strlen($split[1]);
			$strinput = $strinput-2;
			$member_id = substr($split[1],2,$strinput);			

			//echo	"######".$member_id."##### \n";
			echo	"######".$input."##### \n";
			/*$loan = $dbs->query("SELECT i.item_code, b.title FROM loan AS l LEFT JOIN item AS i ON l.item_code=i.item_code WHERE ");
			while($myloan = $dbs->fetch_row($loan)){
				$item_code = $myloan[0];
				$title 	= $myloan[1];
			}
			*/
			$message = "301YNY".$date."    ".$hours."AOUNSYIAH|AA5848312011|AB099813txbpu2013|AJStatistika Deskriptif Untuk Penelitian : Dilengkapi Perhitungan Manual dan Aplikasi SPSS Versi 17|AH20141219    232323|AFBERHASIL DIPERPANJANG|".$sembilanbelakang;
			echo "=== ".$message."=== \n";
		}
		
		if($duadepan == '09'){
			$sembilanbelakang 	= substr($input,-9,9).chr(13);
			echo $sembilanbelakang."\n";

			$input 				= explode('|',$input);
			$item_code_09 			= substr($input[2],2,15);
			//echo $item_code_09.'-----<br>';
			$judul 				= $check->taketitle($item_code_09);

			// Menggenerate Pesan
			$message = '101YNN'.$date.'    '.$hours.'AOUNSYIAH|AB'.$item_code_09.'|AJ'.$judul.'|AQUNSYIAH|BXYYYNYYYYNYYNNNYN|'.$sembilanbelakang;			
		}
		
		
		echo " \n ";
		echo $message."\n";
		socket_write($com,$message, strlen($message)) or die("Could not write output \n");
		
	}while(true);
	socket_close($com);
	
}
while(true);
socket_close($socket);
?>