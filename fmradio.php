<?php

if(isset($_POST['btnStartRadio'])) {
	$audiopath = $_POST['audiopath'];
	$frequency = $_POST['frequency'];
        $message = "Radio was started";
//        $message = $_POST['audiopath'];
//        $message = $_POST['frequency'];
        echo "<script type='text/javascript'>alert('$message');</script>";
        startRadio($audiopath,$frequency);
}

if(isset($_POST['btnStopRadio'])) {
        $message = "Radio was stoped";
        echo "<script type='text/javascript'>alert('$message');</script>";
        stopRadio();
}

function stopRadio(){
        exec("ps -fC fm_transmitter", $result, $code);
        for($i = 0; $i < count($result); $i++){
                if (strpos($result[$i], 'fm_transmitter') !== false){
                        $pid = preg_split('/\s+/',$result[$i]);
			$pid = $pid[1];
                        echo "$pid<br>";
                        exec('sudo /bin/kill -INT ' . $pid);
                }
        }
}

if(isset($_POST['AudioList'])) {
  $audiolist = getAudioList();
  echo json_encode($audiolist);
}

function startRadio($audiopath,$frequency){
        stopRadio();
/*
        $a1 = "/home/ast/audio/short/19201102_kdka_radio_pitsburgh_first_radio_broadcast_.mp3";
        $a2 = "/home/ast/audio/short/19231029_mitteilung_aus_dem_vox_haus_krutschke_.mp3";
        $a3 = "/home/ast/audio/short/19260903_berlin_funkprolog_zur_einweihung_des_funkturms_reko_.mp3";
        $a4 = "/home/ast/audio/short/19320404_funkstunde_berlin_zeitansage_.mp3";
        $a5 = "/home/ast/audio/short/19340000_reichssender_berlin_echo_am_abend_absage_.mp3";
        $audiofiles = array();
        array_push($audiofiles,$a1,$a2,$a3,$a4,$a5);
        $audiofilesstring = "";*/

	$audiofilesstring = $audiopath;

        foreach ($audiofiles as $val){
                $audiofilesstring = $audiofilesstring . $val . " ";
        }

        exec('sudo /bin/sh /home/ast/radio.sh -f ' . $frequency . ' -n "' . $audiofilesstring . '"' . " > /dev/null 2>/dev/null &");
//      echo date(DATE_RFC822);
}

function getAudioList(){
	exec('find /home/ast/audio/ -name \*.mp3 -print',$result,$code);
	$audiolist = array();
	for($i=0; $i < count($result); $i++) {
		$row = array();
		$row['path'] = $result[$i];

		$row_tmp =  explode('/',$result[$i]);
		$row_tmp = end($row_tmp);
		$row['filename'] = $row_tmp;

		array_push($audiolist, $row);
	}
	return $audiolist;
}

function getFrequency(){
	exec('cat /var/www/html/tmp/frequency.txt', $result);
}

?>
