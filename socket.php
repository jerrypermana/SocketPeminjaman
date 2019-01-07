<?php
require 'db_socket.php';
require 'check.php';
require 'configure.php';
require 'check_loan.php';

date_default_timezone_set('Asia/Jakarta');

$nomoritem 		= '';
$date 			= date("Ymd");
$date_1			= date("Y-m-d");
$date_detail	= date("Y-m-d H:i:s");
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

do { //// Coba buang

		echo "\n Accept... ";
		// accept incoming connections
		$com = socket_accept($socket) or die("Could not accept incoming connection \n");
	
	do {
		//session_start();
		
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
			$message = '98YYYNYY100001'.$date.'    '.$hours.'2.00AOUNSYIAH|BXYYYNYYYYNYYNNNYN|'.$sembilanbelakang;
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
			$password = substr($input1[3],2,$strinput);
			
			echo '====######'.$member_id.'###====\n';
			echo '====######'.$password.'###====\n';
			$info = $check_loan->info($member_id);
			
			// Mengecek Jumlah Anggota Pada Database Dengan ID tertentu
			$num_row = $check->check_num_row_member($member_id);
			if($num_row < 1){
				// Menggenerate Pesan
				$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AFANDA BELUM MENJADI ANGGOTA PERPUSTAKAAN UNSYIAH|".$sembilanbelakang;
			}else{
				$cekpass = $check->check_password($password, $member_id);
				if(strlen($password) == 6){
					if($cekpass > 0){
						// Memanggil Nama Anggota
						//$member_name = $check->namaanggota($member_id);
						$info_list_anggota = $check->infoanggota($member_id);
						$member_name = $info_list_anggota[0];
						// Memanggil Status Verifikasi dari Anggota
						$verify		 = $info_list_anggota[1];
						$bebas		 = $info_list_anggota[2];
						if($verify == 0){
							// Menggenerate Pesan
							$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|AE".$member_name."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AFANDA BELUM MELAKUKAN VERIFIKASI, SILAHKAN HUBUNGI PETUGAS SIRKULASI!!!|".$sembilanbelakang;
						}elseif($bebas == 1){
							// Menggenerate Pesan
							$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|AE".$member_name."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AFANDA TIDAK DAPAT LAGI MELAKUKAN PEMINJAMAN BUKU KARENA TELAH MELAKUKAN BEBAS PUSTAKA!!!|".$sembilanbelakang;
						}else{
							$pesan2 		= 'ANDA TELAH DIKENAKAN DENDA PADA PEMINJAMAN SEBELUMNYA. SILAHKAN LUNASI DENDA ANDA TERLEBIH DAHULU SEBELUM MELAKUKAN PEMINJAMAN';
							$tanggal_kembali = $check->takeduedate($member_id);
							if($tanggal_kembali == false){	$cek_denda 	     = false;}else{$cek_denda   	 = $check->denda($date_1, $tanggal_kembali);}
							if($cek_denda == true){
								$pesan 		= 'ANDA TELAH DIKENAKAN DENDA SELAMA LEBIH DARI 7 HARI. SILAHKAN HUBUNGI PETUGAS SIRKULASI UNTUK MELUNASI TAGIHANNYA';
								$message 	= "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|AE".$member_name."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AF".$pesan."|".$sembilanbelakang;
							}else{
								if($check->paidrr($member_id) == true){
									$message 	= "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|AE".$member_name."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AF".$pesan2."|".$sembilanbelakang;
								}else{
									// Menggenerate Pesan
									$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|AE".$member_name."|BHIDR|BLY|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|".$sembilanbelakang;	
								}
							}
						}
					}else{
						$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AFPASSWORD YANG ANDA MASUKKAN SALAH|".$sembilanbelakang;
					}
				}elseif(strlen($password) < 6){
					$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AFPASSWORD TIDAK BOLEH KURANG DARI ENAM DIGIT|".$sembilanbelakang;
				}else{
					$message = "64              001".$date."    ".$hours.$info[0]."AOUNSYIAH|AA".$member_id."|BHIDR|BLN|BV0|CC0|AS0|".$info[1]."|AV0|BU0|CD0|AFPASSWORD TIDAK BOLEH LEBIH DARI ENAM DIGIT|".$sembilanbelakang;					
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
			//echo $item_code.'-----<br>';

			$judul 				= $check->taketitle($item_code);
			/*echo "1<br>";
			if(isset($_SESSION['memberID'])){
				$periode			= $check->LoanPriod($_SESSION['memberID']);
			}else{
				$periode 			= 1;
			}*/
			
			//echo "2<br>";
			$duedate			= $check->due_date(14,$date_1);
			//$_SESSION['duedate'][$item_code] = $duedate;
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
			
			if($biblio_id[1] > 0){
				// Mengecek Judul Buku sama
				$num_cek 			= $check->check_exist($biblio_id[0], $member_id);
				$perp				= 'N';
				// Mengecek Skenario Penolakan
				$cekeksemplar = 	$check->SameEksemplar($item_code, $member_id);

				// Mengecek Skenario Peminjaman Diblock
				$cekbuku =$check->LoanEksemplar($item_code, $member_id);
				
				//Mengecek database yang sudah melakukan perpanjang
				$buku	=$dbs->query ('SELECT * FROM loan WHERE item_code=\''.$item_code.'\' AND is_return=0 AND renewed=1 AND member_id=\''.$member_id.'\'');
				$num_buku =$dbs->fetch_row($buku);
				$tgl_block= date('Y-m-d', strtotime($num_buku[4]. ' + 2 days'));
				$waktuskrg=date('Y-m-d');
				
				if($cekbuku==1 && strtotime($waktuskrg) <= strtotime($tgl_block)){
						$e_j = 0;
						$pesan = 'ANDA TIDAK DAPAT MELAKUKAN PEMINJAMAN DENGAN JUDUL BUKU YANG SAMA 2 HARI';
						
						
				}else {
					
					
					
				if($cekeksemplar == 0){
					if($num_cek > 0){
						$duedate_r = '';
						echo $num_cek."\n";
						$e_j = 0;
						$pesan = 'ANDA TIDAK DAPAT MEMINJAM BUKU DENGAN JUDUL YANG SAMA';
					}else{
						// Memanggil Member_ID, Lama Peminjaman, Batas Peminjaman Dari Anggota
						$member 		= $dbs->query('SELECT m.member_id, mt.loan_periode, mt.loan_limit, mt.loan_limit_night FROM member AS m 
													INNER JOIN mst_member_type AS mt ON m.member_type_id=mt.member_type_id
													WHERE m.member_id='.$member_id.' OR m.pin='.$member_id.'');
						$memberloan 	= $dbs->fetch_row($member);
						$loanpriode 	= $memberloan[1];
						$MemberID 		= $memberloan[0];
						$timenow 		= date('H:i:s');
						$night 			= "19:00:00";
						print "=====Waktu======".$timenow."===== \n";
						if($timenow > $night){
							$loanlimit	= $memberloan[3];
						}
						else{
							$loanlimit	= $memberloan[2];
						}
						print "=====Batas Sebelum======".$loanlimit."===== \n";
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
								$pesan = 'JUMLAH BUKU YANG ANDA PINJAM MELEBIHI KUOTA PEMINJAMAN';
							}else{
								$e_j = 1;
								echo "7 \n";
								$pesan = 'TEKAN TOMBOL KELUAR UNTUK MENDAPATKAN STRUK PEMINJAMAN';
								// Insert Data Peminjaman ke Dalam Tabel Loan di Database
								$msglog = "loan transaction for member(".$member_id.") and book item(".$item_code.") is started by RFID";
								$logtype= "system";
								$IDlog =  $member_id;
								$logLocation = "circulation";
								
								$insertloan 	= $dbs->query("INSERT INTO loan(item_code, member_id, loan_date, due_date, is_lent) VALUES ('$item_code','$member_id','$datenow','$duedate',1)");

								$loginsert		= $dbs->query("INSERT INTO system_log(log_type, id, log_location, log_msg, log_date) VALUES ('$logtype','$IDlog','$logLocation','$msglog','$date_detail')");

							}
						}else{
							$e_j = 0;
							$pesan = 'EKSEMPLAR SEDANG DIPINJAM';
						}
					}
				}else{
					$pesan = 'JUMLAH KALI PERPAJANGAN ANDA TELAH MELEBIHI KUOTA';
					$e_j = 0;
				}
			}
			}else{
				$pesan = 'BUKU YANG AKAN ANDA PINJAM TIDAK TERSEDIA DI PANGKALAN DATA. SILAHKAN HUBUNGI PETUGAS';
				$e_j = 0	;			
			}

			// Menggenerate Pesan
			$message ='12'.$e_j.'NNY'.$date.'    '.$hours.'AOUNSYIAH|AA'.$member_id.'|AB'.$item_code.'|AJ'.$judul_11.'|AF'.$pesan.'|CK001|'.$sembilanbelakang; 
			//session_destroy();
		}
		
		// RENEW/PERPANJANGAN
		/*if($duadepan == '29'){
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
			*//*
			$message = "301YNY".$date."    ".$hours."AOUNSYIAH|AA5848312011|AB099813txbpu2013|AJStatistika Deskriptif Untuk Penelitian : Dilengkapi Perhitungan Manual dan Aplikasi SPSS Versi 17|AH20141219    232323|AFBERHASIL DIPERPANJANG|".$sembilanbelakang;
			echo "=== ".$message."=== \n";
		}*/
		
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