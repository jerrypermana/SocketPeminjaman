<?php 
class Sambungan {
// Fungsi Untuk Membuka Koneksi Ke Database
	protected function ConnectMysql()
	{
		$server = "192.168.1.6"; //server database mysql
		$username = "middleware4RFID"; // username mysql
		$password = "user4middleRFID"; // password mysql
		$connection = mysql_connect($server,$username,$password) or die ("Koneksi Putus");
		return $connection;
	}
	 // Fungsi Untuk Memilih Database Yang Akan Di gunakan
	private function DataBase()
	{
		$db = "uilis"; // nama database mysql
		$connectdb = mysql_select_db($db) or die (" Database Tidak ditemukan");
		return $connectdb;
	}
	 // Fungsi Untuk Menutup Koneksi Dari Database
	function CloseLink()
	{
		$tutup = mysql_close($this->ConnectMysql()) or die ("Koneksi Tidak Tersambung");
		return $tutup;
	}
	 // Fungsi Membuka Koneksi Dan Memilih Database
	function OpenLink()
	{
		$this->ConnectMysql();
		$this->DataBase();
	}
	// mysql query
	function query($query){
		$myquery = mysql_query($query);
		return $myquery;
	}

	 // Fungsi untuk melakukan fetch assoc
	function fetch_assoc($asc){
		$assoc = mysql_fetch_assoc($asc);
		return $assoc; 
	}

	 // Fungsi untuk melakukan fetch row
	function fetch_row($rw){
		$row = mysql_fetch_row($rw);
		return $row; 
	}
	
	function num_rows($n_row){
		$num_row = mysql_num_rows($n_row);
		return $num_row;
	}
}

$dbs = new  Sambungan();
?> 