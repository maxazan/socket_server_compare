<?php
error_reporting(E_ALL);
ini_set("default_socket_timeout", 5000);

$service_port = 30000;
$address = 'incakit.com';

echo "Testing $address:$service_port...";
$connections_count=10000;
$result=true;
$counter=0;

$connection_time_total=0;
$connection_time_max=0;
$connection_time_min=1000;

$write_time_total=0;
$write_time_max=0;
$write_time_min=1000;


$sockets=array();
print "";
while ($result and $counter<$connections_count){

	$counter++;

	//Initialize TCP socket
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
	if ($socket === false) {
		echo "Can`t connect socket_create(): " . socket_strerror(socket_last_error()) . "\n";
		die();
	}

	//Connect
	$before_time=microtime(true);
	$result = socket_connect($socket, $address, $service_port);
	if ($result) {
		$connect_time=microtime(true)-$before_time;
		if($connect_time>$connection_time_max){
			$connection_time_max=$connect_time;
		}
		if($connect_time<$connection_time_min){
			$connection_time_min=$connect_time;
		}
		$connection_time_total=$connection_time_total+$connect_time;
		print "\nConnection #".$counter." connect ".$connect_time."sec";
	} else {
		$counter--;
		break;
	}
	//Write
	$before_time=microtime(true);
	$in="echo text\n";
	$result=socket_write($socket, $in, strlen($in));
	if ($result) {
		$tmp_time=microtime(true)-$before_time;
		if($tmp_time>$write_time_max){
			$write_time_max=$tmp_time;
		}
		if($tmp_time<$write_time_min){
			$write_time_min=$tmp_time;
		}
		$write_time_total=$write_time_total+$tmp_time;
		print "\nConnection #".$counter." write ".$tmp_time."sec";
	} else {
		$counter--;
		break;
	}
	$sockets[]=$socket;
/*	while ($out = socket_read($socket, strlen($in))) {
		if($in!=$out){
			$counter--;
			break;
		}
	}	*/
	
}
foreach($sockets as $socket){
	socket_close($socket);
}
print "\nMax connections: ".$counter."                                                     ";
print "\nConnection time (min, avg, max): ".$connection_time_min."sec ".($connection_time_total/$counter)."sec ".$connection_time_max."sec";
print "\nWrite time (min, avg, max): ".$write_time_min."sec ".($write_time_total/$counter)."sec ".$write_time_max."sec";

/*
$in = "HEAD / HTTP/1.1\r\n";
$in .= "Host: www.example.com\r\n";
$in .= "Connection: Close\r\n\r\n";
$out = '';

echo "Send HTTP HEAD";
socket_write($socket, $in, strlen($in));
echo "OK.\n";

echo "Responce:\n\n";
while ($out = socket_read($socket, 2048)) {
    echo $out;
}
*/
?>