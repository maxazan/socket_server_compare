<?php

error_reporting(E_ALL);
ini_set("default_socket_timeout", 5000);

class SocketTester {

    public $service_port = 30000;
    public $address = 'incakit.com';
    public $connections_max = 1000; //Max connection
    public $connection_pool_count = 100; // adding 10 connection per once
    public $connections_counter = 0;
    public $connection_time_total = 0;
    public $connection_time_max = 0;
    public $connection_time_min = 1000;
    public $connection_error = 0;
    public $write_time_total = 0;
    public $write_time_max = 0;
    public $write_time_min = 1000;
    public $write_error = 0;
    public $read_time_total = 0;
    public $read_time_max = 0;
    public $read_time_min = 1000;
    public $read_error = 0;
    public $sockets = array();
    public $test_message = "Test text";

    /**
     * Adding $count sockets to the pool
     * 
     * @param type $count
     */
    function addSockets($count) {

        for ($i = 0; $i < $count; $i++) {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket === false) {
                echo "Error socket_create(): " . socket_strerror(socket_last_error()) . "\n";
                $this->printResult(false);
            }
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));

            $before_time = microtime(true);
            if (socket_connect($socket, $this->address, $this->service_port)) {
                $connect_time = microtime(true) - $before_time;
                if ($connect_time > $this->connection_time_max) {
                    $this->connection_time_max = $connect_time;
                }
                if ($connect_time < $this->connection_time_min) {
                    $this->connection_time_min = $connect_time;
                }
                $this->connection_time_total = $this->connection_time_total + $connect_time;
                $this->sockets[] = $socket;
            } else {
                echo "Error socket_connect(): " . socket_strerror(socket_last_error()) . "\n";
                $this->printResult(false);
            }
        }
    }

    public function writeToSocket() {

        foreach ($this->sockets as $key=>$socket) {

            $before_time = microtime(true);
            $msg=$this->test_message." from socket ".$key."\n";
            $result = socket_write($socket, $msg, strlen($msg));
            if ($result) {
                $tmp_time = microtime(true) - $before_time;
                if ($tmp_time > $this->write_time_max) {
                    $this->write_time_max = $tmp_time;
                }
                if ($tmp_time < $this->write_time_min) {
                    $this->write_time_min = $tmp_time;
                }
                $this->write_time_total = $this->write_time_total + $tmp_time;
            } else {
                $this->write_error++;
            }
        }
    }

    public function readFromSocket() {
        foreach ($this->sockets as $key=>$socket) {
            $msg=$this->test_message." from socket ".$key."\n";
            if (socket_select($read=array($socket), $write = NULL, $except = NULL, 0)) {
                $before_time = microtime(true);
                $receive_message = socket_read($socket, 2048);
                if ($msg == $receive_message) {
                    $tmp_time = microtime(true) - $before_time;
                    if ($tmp_time > $this->read_time_max) {
                        $this->read_time_max = $tmp_time;
                    }
                    if ($tmp_time < $this->read_time_min) {
                        $this->read_time_min = $tmp_time;
                    }
                    $this->read_time_total = $this->read_time_total + $tmp_time;
                } else {
                    print_r($receive_message);
                    $this->read_error++;
                }
            }
        }
    }

    function run() {
        while (count($this->sockets) < $this->connections_max) {
            print "Adding " . $this->connection_pool_count . " connections...\n";
            $this->addSockets($this->connection_pool_count);
            print "Write to sockets\n";
            $this->writeToSocket();
            print "Read from sockets\n";
            $this->readfromSocket();
            print "Total: " . count($this->sockets) . " connections\n";
        }
        $this->printResult();
    }

    function printResult() {

        print "\nConnections: " . count($this->sockets);
        print "\nTotal connection time: ".$this->connection_time_total."sec";
        print "\nTotal write time: ".$this->write_time_total."sec";
        print "\nTotal read time: ".$this->read_time_total."sec";
        die();
        //print "\nConnection time (min, avg, max): " . $this->connection_time_min . "sec " . ($this->connection_time_total / count($this->sockets)) . "sec " . $this->connection_time_max . "sec";
        //print "\nWrite time (min, avg, max): " . $this->write_time_min . "sec " . ($this->write_time_total / count($this->sockets)) . "sec " . $this->write_time_max . "sec";
        //print "\nRead time (min, avg, max): " . $this->read_time_min . "sec " . ($this->read_time_total / count($this->sockets)) . "sec " . $this->read_time_max . "sec";
    }

}

$socketTester = new SocketTester();
$socketTester->run();
?>