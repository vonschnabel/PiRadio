<?php
require 'fmradio.php';

  if(isset($_POST['btnReboot'])) {
      $message = "System wir neu gestartet";
      echo "<script type='text/javascript'>alert('$message');</script>";
      exec('sudo /sbin/reboot');
  }
  if(isset($_POST['btnShutdown'])) {
      $message = "System wird heruntergefahren";
      echo "<script type='text/javascript'>alert('$message');</script>";
      exec('sudo /sbin/shutdown -h now');
  }

  if(isset($_POST['btnVPN'])) {
    $connect = $_POST['connect'];
    $vpnnetwork = $_POST['network'];
    if($connect == "true"){
      exec('sudo /sbin/iptables -t nat -A POSTROUTING -o ' . $vpnnetwork . ' -j MASQUERADE');
      exec('sudo /sbin/iptables -A FORWARD -i ' . $vpnnetwork . ' -o wlan1 -j ACCEPT');
      exec('sudo /sbin/iptables -A FORWARD -i wlan1 -o ' . $vpnnetwork . ' -j ACCEPT');
      exec('sudo /bin/systemctl start wg-quick@' . $vpnnetwork);
      echo json_encode("Wireguard started");
    }
    elseif($connect == "false"){
      exec('sudo /bin/systemctl stop wg-quick@' . $vpnnetwork);
      exec('sudo /sbin/iptables -t nat -D POSTROUTING -o ' . $vpnnetwork . ' -j MASQUERADE');
      exec('sudo /sbin/iptables -D FORWARD -i ' . $vpnnetwork . ' -o wlan1 -j ACCEPT');
      exec('sudo /sbin/iptables -D FORWARD -i wlan1 -o ' . $vpnnetwork . ' -j ACCEPT');
//      echo json_encode("Wireguard stopped");
    }
  }

/*  if(isset($_POST['VPNStatus'])) {
    $stat = getVPNStatus();
//    echo json_encode($stat);
    if($stat == "active"){
      echo json_encode(true);
    }
    elseif($stat == "inactive"){
      echo json_encode(false);
    }
  }*/

  if(isset($_POST['VPNNetworkList'])) {
    $networks = getVPNNetworkList();
    echo json_encode($networks);
  }

  if(isset($_POST['btnScanWiFi'])) {
      $networks = wifiscan();
      print_r($networks);
  }

  //////////////>>><<<hier in eine Funktion kapseln
  $stats = getSystemConfig();
  $dhcprangestart = $stats[0];
  $dhcprangeend = $stats[1];
  $dhcpleasetime = $stats[2];
  $ssidwlan0 = $stats[3];
  $pskwlan0 = $stats[4];
  $ipeth0 = $stats[5];
  $ipwlan0 = $stats[6];
  $ipwlan1 = $stats[7];
  $ssidwlan1 = $stats[8];
  $countrycodewlan0 = $stats[9];
  $channelwlan0 = $stats[10];
  $dhcpdns = $stats[11];
  $dhcprange = $stats[12];
  $wlan0dns = $stats[13];

  if(isset($_POST['btnHotspot'])) {
    $ssid = $_POST['ssid'];
    $password = $_POST['password'];
    $countrycode = $_POST['countrycode'];
    $frq24 = "2.4 GHz";
    $frq50 = "5.0 GHz";
    $tempvar = $_POST['hwmode'];
    if ($tempvar == $frq24){
      $hwmode = "g";
    }
    elseif ($tempvar == $frq50){
      $hwmode = "a";
    }
    $channel = $_POST['channel'];

    $stats = getSystemConfig(); // load the previous Config if a Value is not set
    if($ssid == ""){
      $ssid = $stats[3];
    }
    if($password == ""){
      $password = $stats[4];
    }
    if($countrycode == ""){
      $countrycode = $stats[9];
    }
    if($channel == ""){
      $channel = $stats[10];
    }
    //echo "$ssid<br>";
    //echo "$password<br>";
    //echo "$countrycode<br>";
    //echo "$hwmode<br>";
    //echo "$channel<br><br>";
    writeHostAPDConf($ssid, $password, $countrycode, $hwmode, $channel);
    exec('sudo /bin/systemctl stop hostapd.service');
    exec('sudo /bin/cp /var/www/html/tmp/hostapd.conf /etc/hostapd/hostapd.conf');
    exec('sudo /bin/systemctl start hostapd.service');
    echo "hostapd Conf erstellt";
  }

  if(isset($_POST['btnNetwork'])) {
    $IPwlan0 = $_POST['staticIP'];
    $DNSwlan0 = $_POST['dnswlan0'];
    $DHCPStartIP = $_POST['rangeStart'];
    $DHCPEndIP = $_POST['rangeEnd'];
    $DNSClients = $_POST['dnsClients'];
    $leaseTime = $_POST['leaseTime'];

    $stats = getSystemConfig(); // load the previous Config if a Value is not set
    if($IPwlan0 == ""){
      $IPwlan0 = $stats[6];
    }
    if($DNSwlan0 == ""){
      $DNSwlan0 = $stats[13];
    }
    elseif($DNSwlan0 == "-"){
      $DNSwlan0 = "";
    }
    if($DHCPStartIP == ""){
      $DHCPStartIP = $stats[0];
    }
    if($DHCPEndIP == ""){
      $DHCPEndIP = $stats[1];
    }
    if($DNSClients == ""){
      $DNSClients = $stats[11];
    }
    if($leaseTime == ""){
      $leaseTime = $stats[2];
    }
    //echo "$IPwlan0<br>";
    //echo "$DNSwlan0<br>";
    //echo "$DHCPStartIP<br>";
    //echo "$DHCPEndIP<br>";
    //echo "$DNSClients<br>";
    //echo "$leaseTime<br><br>";
    writeDHCPCDConf($IPwlan0, $DNSwlan0);
    writeDNSMasqConf($IPwlan0, $DHCPStartIP, $DHCPEndIP, $leaseTime, $DNSClients);
    exec('sudo /bin/systemctl stop hostapd.service');
    exec('sudo /bin/systemctl stop dnsmasq.service');
    exec('sudo /bin/systemctl stop dhcpcd.service');
    exec('sudo /bin/cp /var/www/html/tmp/090_raspap.conf /etc/dnsmasq.d/090_raspap.conf');
    exec('sudo /bin/cp /var/www/html/tmp/dhcpcd.conf /etc/dhcpcd.conf');
    exec('sudo /bin/systemctl start hostapd.service');
    exec('sudo /bin/systemctl start dhcpcd.service');
    exec('sudo /bin/systemctl start dnsmasq.service');
    echo "dnsmasq conf erstellt<br>";
    echo "dhcpcd conf erstellt";
  }


if(isset($_POST['btndevicetest'])){
  echo wifiscan();
}

if(isset($_POST['scanresults'])){
  echo wifiscanresults();
}

if(isset($_POST['writeWPAConf'])){
  $ssid = $_POST['ssid'];
  $password = $_POST['password'];
  if ($_POST['hidden'] == "true"){
    $hidden = "true";
    writeWPAConf($ssid, $password, $hidden, $countrycodewlan0);
  }
  else{
    $hidden = "false";
    writeWPAConf($ssid, $password, $hidden, $countrycodewlan0);
  }
  echo json_encode("wpa_supplicant Config erstellt");
}

if(isset($_POST['btnAddNetwork'])){
  $ssid = $_POST['ssid'];
  $psk = $_POST['password'];
  if ($_POST['hidden'] == "true"){
    $hidden = true;
  }
  else{
    $hidden = false;
  }
  //$hidden = $_POST['hidden'];
  addNetwork($ssid,$psk,$hidden);
  echo json_encode("Network added");
//  echo json_encode(addNetwork($ssid,$psk,$hidden));
}

if(isset($_POST['ConfiguredNetworks'])){
  echo getConfiguredNetworks();
}

if(isset($_POST['ConnectedClients'])){
  echo getStationDump();
}

function wifiscan(){
  //echo "wpa_cli scan ";
  $chkok = exec('sudo /sbin/wpa_cli -i wlan1 scan');
  if ($chkok === "OK") {
    echo "true";
  }
  else{
    echo "false";
  }
}

function wifiscanresults(){
    exec('sudo /sbin/wpa_cli -i wlan1 scan_results', $result);

    $networks = array();
    foreach ($result as $key => $value) {
      $row = explode("\t", $value);
      if (!isset($row[4])) {
        continue;
      }

	if($row[2] > -50){
		$row[2] = "[ / / / / ]";
	}
	elseif($row[2] > -60){
		$row[2] = "[ / / / ]";
	}
	elseif($row[2] > -70){
		$row[2] = "[ / / ]";
	}
	else{
		$row[2] = "[ / ]";
	}

//	$row[3] = str_replace("[","",$row[3]);
//	$row[3] = str_replace("]"," ",$row[3]);

      $row_assoc['bssid'] = $row[0];
      $row_assoc['frequency'] = $row[1];
      $row_assoc['signal level'] = $row[2];
      $row_assoc['flags'] = $row[3];
      $row_assoc['ssid'] = $row[4];

      $networks[] = $row_assoc;
    }
    //return $networks;
    echo json_encode($networks);
}

if(isset($_POST['btnRemoveNetwork'])) {
  $id = $_POST['id'];
  removeNetwork($id);
  echo json_encode("Network removed");
}

if(isset($_POST['btnSelectNetwork'])) {
  $id = $_POST['id'];
  selectNetwork($id);
  echo json_encode("connect to Network");
}

if(isset($_POST['btnChangeMACAddress'])) {
  $newmac = $_POST['macaddress'];
  exec('sudo /sbin/ip link set wlan1 down');
  exec('sudo /sbin/ip link set wlan1 address ' . $newmac);
  exec('sudo /sbin/ip link set wlan1 up');
}

/*function getVPNStatus(){
  $vpnstatus = exec('sudo /bin/systemctl is-active wg-quick@peer1');
  return $vpnstatus;
}*/

function getVPNNetworkList(){
  exec('sudo /bin/ls /etc/wireguard',$result,$code);
  $vpnlist =  preg_grep('/\.conf/', $result);
  $vpnlist = array_values($vpnlist);
  for($i=0; $i < count($vpnlist); $i++) {
    $vpnlist[$i] = explode('.',$vpnlist[$i]);
    $vpnlist[$i] = $vpnlist[$i][0];
    $row = exec('sudo /bin/systemctl is-active wg-quick@' . $vpnlist[$i]);
    $vpnlist[$i] = array($vpnlist[$i],$row);
  }
  return $vpnlist;
}

/*function getVPNStatushtmltag(){
  $vpnstatus = exec('sudo /bin/systemctl is-active wg-quick@peer1');
  if($vpnstatus == "active"){
    $vpnstatus = "checked";
  }
  elseif($vpnstatus == "inactive"){
    $vpnstatus = "";
  }
  return $vpnstatus;
}*/

function getStationDump(){
  exec('sudo /sbin/iw dev wlan0 station dump',$result,$code);
  $returnarray = array();

  for($i=0; $i < count($result); $i++) {
    if(strpos($result[$i],'Station') === 0){
      if(isset($row)){
        array_push($returnarray, $row );
      }
      $row = array();
      $station = explode(' ',$result[$i]);
      $station = $station[1];
      $row['station'] = $station;
    }
    if(strpos($result[$i],'rx bytes') === 1){
      $rxbytes = explode(':',$result[$i]);
      $rxbytes = $rxbytes[1];
      $j = 0;
      while($rxbytes > 1000){
        $rxbytes = $rxbytes / 1024;
        $j++;
      }
      $rxbytes = number_format($rxbytes, 2);
      if($j == 0){
        $rxbytes = $rxbytes . " Byte";
      }
      elseif($j == 1){
        $rxbytes = $rxbytes . " KB";
      }
      elseif($j == 2){
        $rxbytes = $rxbytes . " MB";
      }
      elseif($j == 3){
        $rxbytes = $rxbytes . " GB";
      }
      $row['rxbytes'] = $rxbytes;
    }
    if(strpos($result[$i],'tx bytes') === 1){
      $txbytes = explode(':',$result[$i]);
      $txbytes = $txbytes[1];
      $j = 0;
      while($txbytes > 1000){
        $txbytes = $txbytes / 1024;
        $j++;
      }
      $txbytes = number_format($txbytes, 2);
      if($j == 0){
        $txbytes = $txbytes . " Byte";
      }
      elseif($j == 1){
        $txbytes = $txbytes . " KB";
      }
      elseif($j == 2){
        $txbytes = $txbytes . " MB";
      }
      elseif($j == 3){
        $txbytes = $txbytes . " GB";
      }
      $row['txbytes'] = $txbytes;
    }
    if(strpos($result[$i],'rx bitrate') === 1){
      $rxbitrate = explode(':',$result[$i]);
      $rxbitrate = trim($rxbitrate[1], "\t");
      $row['rxbitrate'] = $rxbitrate;
    }
    if(strpos($result[$i],'tx bitrate') === 1){
      $txbitrate = explode(':',$result[$i]);
      $txbitrate = trim($txbitrate[1], "\t");
      $row['txbitrate'] = $txbitrate;
    }
    if(strpos($result[$i],'signal') === 1){
      $signal = explode(':',$result[$i]);
      $signal = $signal[1];
      $signal = explode(' ',$signal);
      $signal = trim($signal[2], "\t");
      $row['signal'] = $signal;
    }
    if(strpos($result[$i],'connected time') === 1){
      $connectedtime = explode(':',$result[$i]);
      $connectedtime = explode(' ',$connectedtime[1]);
      $connectedtime = trim($connectedtime[0], "\t");
      $connectedtime = getElapsedTime($connectedtime);
      $row['connectedtime'] = $connectedtime;
    }
  }
  array_push($returnarray, $row );

  $stats = getSystemConfig();
  $dhcprange = $stats[12];

  for($i=0; $i < count($returnarray); $i++) {
    $mac = $returnarray[$i]['station'];
    exec("/bin/ip neigh | grep $mac | grep $dhcprange", $resultip,$codeip);
    $ip = explode(' ',$resultip[0]);
    $ip = $ip[0];
    unset($resultip, $codeip);

    if(!empty($ip)){
      $returnarray[$i]['ip'] = $ip;
    }
  }

  echo json_encode($returnarray);
}

function getSystemConfig(){

	$filecontent = "";
        if ($file = fopen("/etc/hostapd/hostapd.conf", "r")) {
            while(!feof($file)) {
                $line = fgets($file);
                if(strpos($line,'ssid=') !== false){
                        if(strpos($line,'ignore_broadcast_ssid') !== false){
                                //do nothing
                        }
                        else{
                                $result = explode('=',$line);
                                $ssidwlan0 = $result[1];

                        }
                }
                if(strpos($line,'wpa_passphrase') !== false){
                        $result = explode('=',$line);
                        $pskwlan0 = $result[1];
                }
		if(strpos($line,'country_code=') !== false){
                        $result = explode('=',$line);
                        $countrycodewlan0 = $result[1];
                }
		if(strpos($line,'channel=') !== false){
                        $result = explode('=',$line);
                        $channelwlan0 = $result[1];
                }

            }
            fclose($file);
        }

        if ($file = fopen("/etc/dhcpcd.conf", "r")) {
	    $wlan0dns = "-not set-";
	    while(!feof($file)) {
                $line = fgets($file);
                if(strpos($line,'static domain_name_server=') !== false){
			if(strpos($line,'#') !== false){
				//do nothing
			}
			else{
				$result = explode('=',$line);
				$wlan0dns = $result[1];
			}
		}
	    }
	    fclose($file);
        }

        //if ($file = fopen("/etc/dnsmasq.conf", "r")) {
        if ($file = fopen("/etc/dnsmasq.d/090_raspap.conf", "r")) {
            $dhcprange = "-not set-";
            $dhcpdns = "-not set-";
            while(!feof($file)) {
                $line = fgets($file);
                if(strpos($line,'dhcp-range=') !== false){
                        if(strpos($line,'#') !== false){
                                //do nothing
                        }
                        else{
                                $result = explode('=',$line);
                                $dhcprange = $result[1];
                        }
                }
		if(strpos($line,'dhcp-option=option:dns-server,') !== false){
                        if(strpos($line,'#') !== false){
                                //do nothing
                        }
                        else{
                                $result = explode('dhcp-option=option:dns-server,',$line);
                                $dhcpdns = $result[1];
                        }
                }
            }
            fclose($file);
        }
        $tempvar = explode(',',$dhcprange);
        $dhcprangestart = $tempvar[0];
        $dhcprangeend = $tempvar[1];
        $dhcpleasetime = $tempvar[3];

        $dhcprangestart = explode('.',$dhcprangestart);
        $dhcprangestart = $dhcprangestart[3];
        $dhcprangeend = explode('.',$dhcprangeend);
        $dhcprangeend = $dhcprangeend[3];

        $ipeth0 = exec("ip -4 addr show eth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}'");
        $ipwlan0 = exec("ip -4 addr show wlan0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}'");
        $ipwlan1 = exec("ip -4 addr show wlan1 | grep -oP '(?<=inet\s)\d+(\.\d+){3}'");

        $ssidwlan1 = exec("iwgetid -r");

	$tempvar = explode(',',$dhcprange);
	$tempvar = $tempvar[0];
	$tempvar = explode('.',$tempvar);
	$dhcprange = $tempvar[0] .".". $tempvar[1] .".". $tempvar[2] .".";

	return array($dhcprangestart, $dhcprangeend, $dhcpleasetime, $ssidwlan0, $pskwlan0, $ipeth0, $ipwlan0, $ipwlan1, $ssidwlan1, $countrycodewlan0, $channelwlan0, $dhcpdns, $dhcprange, $wlan0dns);

}

/*
function getFile(){
	$filecontent = "";
	if ($file = fopen("/etc/hostapd/hostapd.conf", "r")) {
    	    while(!feof($file)) {
        	$line = fgets($file);
        	$filecontent = $filecontent .= $line . "<br>";
		//# do same stuff with the $line
		//echo "$line<br>";
    	    }
    	    fclose($file);
	}

	//echo $file;
	echo $filecontent;
}*/

function getMACAddress(){
	exec('/bin/cat /sys/class/net/wlan1/address',$result);
	return $result[0];
}

function getWPAState(){
        exec('sudo /sbin/wpa_cli status',$result);
        for($i = 0; $i < count($result); $i++){
                if(strpos($result[$i],'wpa_state=') === 0){
                        $wpa_state = explode('=',$result[$i]);
                        $wpa_state = $wpa_state[1];
                        return $wpa_state;
                }
        }
}

function getWLAN1_SSID(){
        exec('sudo /sbin/wpa_cli status',$result);
        for($i = 0; $i < count($result); $i++){
                if(strpos($result[$i],'ssid=') === 0){
                        $ssid = explode('=',$result[$i]);
                        $ssid = $ssid[1];
                        return $ssid;
                }
        }

}

function getWLAN1_IP(){
        exec('sudo /sbin/wpa_cli status',$result);
        for($i = 0; $i < count($result); $i++){
                if(strpos($result[$i],'ip_address') === 0){
                        $ip = explode('=',$result[$i]);
                        $ip = $ip[1];
                        return $ip;
                }
        }

}

function getWLAN1_Signal(){
	exec('sudo /sbin/wpa_cli -i wlan1 signal_poll',$result);
	$rssi = explode('=',$result[0]);
//	$rssi = $rssi[1];
	$linkspeed = explode('=',$result[1]);
//	$linkspeed = $linkspeed[1];
	$noise = explode('=',$result[2]);
//	$noise = $noise[1];
	$frequency = explode('=',$result[3]);
//	$frequency = $frequency[1];*/

//$rssi[1] = -80;

	if($rssi[1] > -50){
		$result[0] = "[ / / / / ]";
	}
	elseif($rssi[1] > -60){
	        $result[0] = "[ / / / ]";
	}
	elseif($rssi[1] > -70){
	        $result[0] = "[ / / ]";
	}
	else{
		$result[0] = "[ / ]";
	}
	//$result[0] = $rssi[1]; // kann weg
	$result[1] = $linkspeed[1];
	$result[2] = $noise[1];
	$result[3] = $frequency[1];
	return $result;
}

function getConfiguredNetworks(){
        exec('sudo /sbin/wpa_cli list_networks',$result);
        $networks = array();
        for($i = 2; $i < count($result); $i++){
//                $net = preg_split('/\s+/',$result[$i]);
                $net = preg_split('/[\t]/',$result[$i]);
//                $net = $net[1];
//                array_push($networks,$net);
		$row['id'] = $net[0];
		$row['ssid'] = $net[1];
		$row['state'] = $net[3];
		$networks[] = $row;
        }
	echo json_encode($networks);
	//return $networks;
}

function removeNetwork($id){
	exec("sudo /sbin/wpa_cli remove_network $id",$result,$code);
	exec("sudo /sbin/wpa_cli save_config",$result,$code);
        exec("sudo /sbin/wpa_cli -i wlan1 reconfigure",$result,$code);
}

function selectNetwork($id){
	exec("sudo /sbin/wpa_cli -i wlan1 select_network $id",$result,$code);
}

function addNetwork($ssid,$psk,$hidden){
        exec('sudo /sbin/wpa_cli list_networks',$result);
        $networks = array();
        for($i = 2; $i < count($result); $i++){
                $net = preg_split('/[\t]/',$result[$i]);
                $row['id'] = $net[0];
                $row['ssid'] = $net[1];
                $row['state'] = $net[3];
                $networks[] = $row;
        }

	for($i = 0; $i < count($networks); $i++){
		if($networks[$i]['ssid'] === $ssid){
			removeNetwork($i);
		}
	}
	exec("sudo /sbin/wpa_cli add_network",$resultnetwork,$code);
	exec("sudo /sbin/wpa_cli set_network $resultnetwork[1] ssid '\"$ssid\"'",$result,$code);
        exec("sudo /sbin/wpa_cli set_network $resultnetwork[1] psk '\"$psk\"'",$result,$code);
	if($hidden === true){
        	exec("sudo /sbin/wpa_cli set_network $resultnetwork[1] scan_ssid 1",$result,$code);
	}
	if(strlen($psk) == 0){
		exec("sudo /sbin/wpa_cli set_network $resultnetwork[1] key_mgmt NONE",$result,$code);
	}
	else{
		exec("sudo /sbin/wpa_cli set_network $resultnetwork[1] key_mgmt WPA-PSK",$result,$code);
	}
        exec("sudo /sbin/wpa_cli save_config",$result,$code);
}
//addNetwork("beitenu","aaaaaaaaaaaaaa",true);
//removeNetwork(6);
//echo "hallo";

function writeDHCPCDConf($staticIP, $staticDNS){
	$interface = "wlan0";
	$staticIPMask = "/24";

	$dhcpcd_file = fopen('/var/www/html/tmp/dhcpcd.conf', 'w') or die("Unable to open file!");
	fwrite($dhcpcd_file, "hostname".PHP_EOL);
	fwrite($dhcpcd_file, "clientid".PHP_EOL);
	fwrite($dhcpcd_file, "persistent".PHP_EOL);
	fwrite($dhcpcd_file, "option rapid_commit".PHP_EOL);
	fwrite($dhcpcd_file, "option domain_name_servers, domain_name, domain_search, host_name".PHP_EOL);
	fwrite($dhcpcd_file, "option classless_static_routes".PHP_EOL);
//	fwrite($dhcpcd_file, "option interface_mtu".PHP_EOL);
	fwrite($dhcpcd_file, "require dhcp_server_identifier".PHP_EOL);
//	fwrite($dhcpcd_file, "option ntp_servers".PHP_EOL);
	fwrite($dhcpcd_file, "slaac private".PHP_EOL);
	fwrite($dhcpcd_file, "nohook lookup-hostname".PHP_EOL);
	fwrite($dhcpcd_file, "interface $interface".PHP_EOL);
	fwrite($dhcpcd_file, "\tstatic ip_address=$staticIP$staticIPMask".PHP_EOL);
	fwrite($dhcpcd_file, "\tnohook wpa_supplicant".PHP_EOL);
	if (strlen($staticDNS) == 0){
		//echo "dhcpcd_file";
	}
	else {
		fwrite($dhcpcd_file, "\tstatic domain_name_server=$staticDNS".PHP_EOL);
	}
	fclose($dhcpcd_file);
}

function writeDNSMasqConf($staticIP, $DHCPRangeStart, $DHCPRangeEnd, $LeaseTime, $DNSServer){
        //$staticIP = strrev($staticIP);
        $result = explode('.',$staticIP);
        $IPRange = $result[0].".".$result[1].".".$result[2].".";
        //$IPRange = strrev($IPRange);

	$interface = "wlan0";
	$DHCPRangeStart = $IPRange . $DHCPRangeStart;
	$DHCPRangeEnd = $IPRange . $DHCPRangeEnd;
	$Subnetmask = "255.255.255.0";
	$NoDHCPInterface = "eth0,wlan1";

	$dnsmasq_file = fopen('/var/www/html/tmp/090_raspap.conf', 'w') or die("Unable to open file!");
	fwrite($dnsmasq_file, "interface=$interface".PHP_EOL);
	fwrite($dnsmasq_file, "dhcp-range=$DHCPRangeStart,$DHCPRangeEnd,$Subnetmask,$LeaseTime".PHP_EOL);
	fwrite($dnsmasq_file, "dhcp-option=option:dns-server,$DNSServer".PHP_EOL);
	fwrite($dnsmasq_file, "no-dhcp-interface=$NoDHCPInterface".PHP_EOL);
	fclose($dnsmasq_file);
}

function writeWPAConf($ssid, $psk, $hidden, $countryCode){
        $wpa_file = fopen('/etc/wpa_supplicant/wpa_supplicant.conf', 'r') or die("Unable to open file!");
 	$result = array();
	$searchstringssid = 'ssid="' . $ssid . '"';
	while(! feof($wpa_file)){
	  array_push($result,fgets($wpa_file));
	}

	// check if Network exists in Config and delete entry
	$i = 0;
	while($i < count($result)){
	  if(strcmp(trim(preg_replace('/\t+/', '', $result[$i])),$searchstringssid) == 0){
  	   unset($result[$i-1]);
 	   unset($result[$i]);
 	   $i++;
 	   while(strcmp(trim(preg_replace('/\t+/', '', $result[$i])),"}") != 0){
	      unset($result[$i]);
	      $i++;
	    }
	    if(strcmp(trim(preg_replace('/\t+/', '', $result[$i])),"}") == 0){
	     unset($result[$i]);
	    }
	  }
	  $i++;
	}
	$result = array_values($result);

	// delete two or more empty lines
	$i = 0;
	while($i < count($result)){
	  if(strcmp(trim(preg_replace('/\t+/', '', $result[$i])),"") == 0 && strcmp(trim(preg_replace('/\t+/', '', $result[$i])),"") == 0){
	    unset($result[$i]);
	  }
	  $i++;
	}
	$result = array_values($result);

	// check if the two mandatory lines exist only once
	$searchstring = 'ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev';
	$searchstring2 = 'update_config=1';
	$searchstring3 = 'country=' . $countryCode;
	$searchstringcount = 0;
	$searchstring2count = 0;
        $searchstring3count = 0;
        $i = 0;
        while($i < count($result)){
	  if(strcmp(trim(preg_replace('/\t+/', '', $result[$i])),$searchstring) == 0){
            $searchstringcount++;
          }
	  if(strcmp(trim(preg_replace('/\t+/', '', $result[$i])),$searchstring2) == 0){
            $searchstring2count++;
          }
	  if(strpos($result[$i],'country=') === 0){
	    if(strcmp(trim(preg_replace('/\t+/', '', $result[$i])),$searchstring3) == 0){
	      $searchstring3count++;
	    }
	    else{
	      unset($result[$i]);
	    }
          }
          if($searchstringcount > 1){
            unset($result[$i]);
	    $searchstringcount--;
          }
          if($searchstring2count > 1){
            unset($result[$i]);
	    $searchstring2count--;
          }
          if($searchstring3count > 1){
            unset($result[$i]);
            $searchstring3count--;
          }
	  $i++;
	}

        if($searchstring3count == 0){
          //array_unshift($result, "\n");
          //array_unshift($result, "country=" . $countryCode . "\n");
	  array_splice($result, 2, 0, "country=" . $countryCode . "\n");
          array_splice($result, 3, 0, "\n");
        }
	if($searchstring2count == 0){
	  array_unshift($result, "update_config=1\n");
	}
        if($searchstringcount == 0){
          array_unshift($result, "ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev\n");
        }
	$result = array_values($result);

/*$i = 0;
while($i < count($result)){
	echo "$result[$i]<br>";
	$i++;
}*/

	$wpa_file = fopen('/var/www/html/tmp/wpa_supplicant.conf', 'w') or die("Unable to open file!");
	$i = 0;
	while($i < count($result)){
	  fwrite($wpa_file, "$result[$i]");
	  $i++;
	}
	fclose($wpa_file);

	// Append WPA Config to File
	$wpa_file = fopen('/var/www/html/tmp/wpa_supplicant.conf', 'a') or die("Unable to open file!");

	if(strlen($psk) == 0){
//	  fwrite($wpa_file, "---".PHP_EOL);
	  fwrite($wpa_file, "network={".PHP_EOL);
	  fwrite($wpa_file, "\tssid=\"".$ssid."\"".PHP_EOL);
	  fwrite($wpa_file, "\tkey_mgmt=NONE".PHP_EOL);
	  fwrite($wpa_file, "}".PHP_EOL);
	}
	else{
	  //fwrite($wpa_file, "".PHP_EOL);
	  fwrite($wpa_file, "network={".PHP_EOL);
	  fwrite($wpa_file, "\tssid=\"".$ssid."\"".PHP_EOL);
	  fwrite($wpa_file, "\tpsk=\"".$psk."\"".PHP_EOL);
          if($hidden == "true"){
            fwrite($wpa_file, "\tscan_ssid=1".PHP_EOL);
          }
	  fwrite($wpa_file, "\tkey_mgmt=WPA-PSK".PHP_EOL);
	  fwrite($wpa_file, "}".PHP_EOL);
	  fclose($wpa_file);
	}

        exec('sudo /bin/cp /var/www/html/tmp/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf');
        exec('sudo /sbin/wpa_cli -i wlan1 reconfigure');
	unlink('/var/www/html/tmp/wpa_supplicant.conf');
}

function deleteSSID($ssid){
	// check if Network exists in Config and delete entry
        $wpa_file = fopen('/etc/wpa_supplicant/wpa_supplicant.conf', 'r') or die("Unable to open file!");
 	$result = array();
	$searchstring = 'ssid="' . $ssid . '"';
	while(! feof($wpa_file)){
	  array_push($result,fgets($wpa_file));
	}

	$i = 0;
	while($i < count($result)){
	  if(strcmp(trim(preg_replace('/\t+/', '', $result[$i])),$searchstring) == 0){
  	   unset($result[$i-1]);
 	   unset($result[$i]);
 	   $i++;
 	   while(strcmp($result[$i], "}") != 1){
	      unset($result[$i]);
	      $i++;
	    }
	    if(strcmp($result[$i], "}") == 1){
	     unset($result[$i]);
	    }
	  }
	  $i++;
	}
	$result = array_values($result);

	// delete two or more empty lines
	$i = 0;
	while($i < count($result)){
	  if(strcmp($result[$i], "") == 1 && strcmp($result[$i+1], "") == 1){
	    unset($result[$i]);
	  }
	  $i++;
	}
	$result = array_values($result);

	$wpa_file = fopen('/var/www/html/tmp/wpa_supplicant.conf', 'w') or die("Unable to open file!");
	$i = 0;
	while($i < count($result)){
	  fwrite($wpa_file, "$result[$i]");
	  $i++;
	}
	fclose($wpa_file);

        exec('sudo /bin/cp /var/www/html/tmp/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf');
        exec('sudo /sbin/wpa_cli -i wlan1 reconfigure');
	unlink('/var/www/html/tmp/wpa_supplicant.conf');
}

function writeHostAPDConf($ssid, $psk, $country_code, $hw_mode, $channel){

	//$ssid = "Beitenu";
	//$psk = "abbaotto";
	//$country_code = "DE";
	$interface = "wlan0";
	//$hw_mode = "g";
	//$channel = "7";

	$hostapd_file = fopen('/var/www/html/tmp/hostapd.conf', 'w') or die("Unable to open file!");

	fwrite($hostapd_file, "ctrl_interface=/var/run/hostapd".PHP_EOL);
	fwrite($hostapd_file, "ctrl_interface_group=0".PHP_EOL);
	fwrite($hostapd_file, "country_code=$country_code".PHP_EOL);
	fwrite($hostapd_file, "interface=$interface".PHP_EOL);
	fwrite($hostapd_file, "ssid=$ssid".PHP_EOL);
	fwrite($hostapd_file, "hw_mode=$hw_mode".PHP_EOL);
	fwrite($hostapd_file, "channel=$channel".PHP_EOL);
	fwrite($hostapd_file, "macaddr_acl=0".PHP_EOL);
	fwrite($hostapd_file, "auth_algs=1".PHP_EOL);
	fwrite($hostapd_file, "ignore_broadcast_ssid=0".PHP_EOL);
	fwrite($hostapd_file, "wpa=2".PHP_EOL);
	fwrite($hostapd_file, "wpa_passphrase=$psk".PHP_EOL);
	fwrite($hostapd_file, "wpa_key_mgmt=WPA-PSK".PHP_EOL);
	fwrite($hostapd_file, "wpa_pairwise=TKIP".PHP_EOL);
	fwrite($hostapd_file, "rsn_pairwise=CCMP".PHP_EOL);
	fclose($hostapd_file);
}

function getElapsedTime($time){

  $day = 86400;
  $hour = 3600;
  $minute = 60;

  $d = 0;
  $h = 0;
  $m = 0;
  $s = 0;

  while($time >= $day){
    $time = $time - $day;
    $d++;
  }
  while($time >= $hour){
    $time = $time - $hour;
    $h++;
  }
  while($time >= $minute){
    $time = $time - $minute;
    $m++;
  }
  $s = $time;

  $elapsedtime = "";

  if($d > 0){
    $elapsedtime = $elapsedtime . $d . " days ";
  }
  if($h > 0){
    if($h < 10){
      $h = "0" . $h;
    }
    $elapsedtime = $elapsedtime . $h . ":";
  }
  elseif($h == 0){
    $elapsedtime = $elapsedtime . "00" . ":";
  }
  if($m > 0){
    if($m < 10){
      $m = "0" . $m;
    }
    $elapsedtime = $elapsedtime . $m . ":";
  }
  elseif($m == 0){
    $elapsedtime = $elapsedtime . "00" . ":";
  }
  if($s > 0){
    if($s < 10){
      $s = "0" . $s;
    }
    $elapsedtime = $elapsedtime . $s;
  }
  elseif($s == 0){
    $elapsedtime = $elapsedtime . "00";
  }

  return  $elapsedtime;
}

function cleanUp(){
	echo "cleanUp";
	unlink('/var/www/html/tmp/090_raspap.conf');
	unlink('/var/www/html/tmp/dhcpcd.conf');
	unlink('/var/www/html/tmp/hostapd.conf');
	unlink('/var/www/html/tmp/wpa_supplicant.conf');
}

//echo getWPAState();
//echo "<br>";
//echo getWLAN1_SSID();
//echo "<br>";
//echo getWLAN1_IP();
//echo "<br>";

?>
