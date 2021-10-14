<?php
/*  // get config variables
  $conf_array = parse_ini_file("/usr/local/bin/piradio.conf");
  $audiofilepath = $conf_array[path];
  $DB_USER = $conf_array[DB_USER];
  $DB_PASSWD = $conf_array[DB_PASSWD];
  $DB_NAME = $conf_array[DB_NAME];
  $TABLE = $conf_array[TABLE];

  echo "path: $audiofilepath<br>";
  echo "user: $DB_USER<br>";
  echo "password: $DB_PASSWD<br>";
  echo "database: $DB_NAME<br>";
  echo "table: $TABLE<br>";*/

  if(isset($_POST['btnStartRadio'])) {
    $playlist = $_POST['playlist'];
    $frequency = $_POST['frequency'];
    $playliststring = implode(" ", $playlist);
    startRadio($playliststring, $frequency);
  }

  if(isset($_POST['btnStopRadio'])) {
    stopRadio();
  }

  if(isset($_POST['getAudiofiles'])) {
    echo getAudiofiles();
  }

  if(isset($_POST['getFolders'])) {
    echo getFolders();
  }

  if(isset($_GET['getAudioJSON'])) {
    echo getAudioJSON();
  }

  function skipSong(){
    exec("ps -fC fm_transmitter", $result, $code);
    for($i = 0; $i < count($result); $i++){
      if (strpos($result[$i], 'fm_transmitter') !== false){
        $pid = preg_split('/\s+/',$result[$i]);
        $pid = $pid[1];
        exec('sudo /bin/kill -INT ' . $pid);
      }
    }
  }

  function stopRadio(){
    exec("pgrep -f radio.sh", $result, $code);
    for($i = 0; $i < count($result); $i++){
      //echo "$pid<br>";
      //echo "$result[$i]<br>";
      exec('sudo /bin/kill -9 ' . $result[$i]);
    }
    exec("ps -fC fm_transmitter", $result, $code);
    for($i = 0; $i < count($result); $i++){
      if (strpos($result[$i], 'fm_transmitter') !== false){
        $pid = preg_split('/\s+/',$result[$i]);
        $pid = $pid[1];
        //echo "$pid<br>";
        exec('sudo /bin/kill -INT ' . $pid);
      }
    }
  }

  if(isset($_POST['AudioList'])) {
    $audiolist = getAudioList();
    echo json_encode($audiolist);
  }

  function startRadio($audiopath, $frequency){
    stopRadio();
    usleep(300000);
    exec('sudo /bin/bash /usr/local/bin/radio.sh -f ' . $frequency . ' -n "' . $audiopath . '"' . " > /dev/null 2>/dev/null &");
  }

  function getAudioList(){ // deprecated
    exec('find $audiofilepath -name \*.mp3 -print',$result,$code);
    $audiolist = array();
    for($i=0; $i < count($result); $i++) {
      $row = array();
      $row['path'] = $result[$i];

      //exec('soxi -d "' .  $result[$i] . '"',$filelength);
      //$row['filelength'] = $filelength[0];
      //echo "$result[$i]  $filelength[0]<br>";

      $row_tmp =  explode('/',$result[$i]);
      $row_tmp = end($row_tmp);
      $row['filename'] = $row_tmp;

      unset($filelength);

      array_push($audiolist, $row);
    }
    return $audiolist;
  }

  function getFrequency(){ // deprecated
    exec('cat /var/www/html/tmp/frequency.txt', $result);
  }

  function getAudiofiles(){
    $db = mysqli_connect('localhost','$DB_USER','$DB_PASSWD','$DB_NAME') or die('Unable to open database.');

    $query = "SELECT * FROM $TABLE order by parent_id";
    mysqli_query($db, $query) or die('Error querying database.');

    $result = mysqli_query($db, $query);
    $audiofiles = [];

    while ($row = mysqli_fetch_array($result)) {
      $row_assoc = [];
      $row_assoc['id'] = $row['id'];
      $row_assoc['filename'] = $row['filename'];
      $row_assoc['text'] = $row['filename'];
      $row_assoc['path'] = $row['path'];
      $row_assoc['length'] = $row['length'];
      $row_assoc['parent_id'] = $row['parent_id'];
      array_push($audiofiles, $row_assoc);
    }

    mysqli_close($db);
    echo json_encode($audiofiles);
  }

  function getFolders(){
    $db = mysqli_connect('localhost','$DB_USER','$DB_PASSWD','$DB_NAME') or die('Unable to open database.');

    $query = "select id, parent_id, filename from $TABLE where length is NULL order by parent_id;";
    mysqli_query($db, $query) or die('Error querying database.');

    $result = mysqli_query($db, $query);
    $folders = [];

    while ($row = mysqli_fetch_array($result)) {
      $row_assoc = [];
      $row_assoc['id'] = $row['id'];
      $row_assoc['filename'] = $row['filename'];
      $row_assoc['parent_id'] = $row['parent_id'];
      array_push($folders, $row_assoc);
    }

    mysqli_close($db);
    echo json_encode($folders);
  }

  function getAudioJSON(){
    // get config variables
    $conf_array = parse_ini_file("/usr/local/bin/piradio.conf");
    $DB_USER = $conf_array[DB_USER];
    $DB_PASSWD = $conf_array[DB_PASSWD];
    $DB_NAME = $conf_array[DB_NAME];
    $TABLE = $conf_array[TABLE];

    $db = mysqli_connect('localhost', $DB_USER , $DB_PASSWD,$DB_NAME) or die('Unable to open database.');

    $query = "select id, parent_id, filename from $TABLE where length is NULL order by parent_id;";
    mysqli_query($db, $query) or die('Error querying database.');

    $result = mysqli_query($db, $query);
    $folders = [];

    while ($row = mysqli_fetch_array($result)) {
      $row_assoc = [];
      $row_assoc['id'] = $row['id'];
//      $row_assoc['filename'] = $row['filename'];
      $row_assoc['text'] = $row['filename'];
      $row_assoc['parent_id'] = $row['parent_id'];
      array_push($folders, $row_assoc);
    }

//    $query = "select id, parent_id, filename, path, length from $TABLE where length is not NULL order by parent_id;";
    $query = "select id, parent_id, filename, path, length from $TABLE where length is not NULL order by filename;";
    mysqli_query($db, $query) or die('Error querying database.');

    $result = mysqli_query($db, $query);
    $files = [];

    while ($row = mysqli_fetch_array($result)) {
      $row_assoc = [];
      $row_assoc['id'] = $row['id'];
//      $row_assoc['filename'] = $row['filename'];
      $row_assoc['text'] = $row['filename'];
      $row_assoc['parent_id'] = $row['parent_id'];
      $row_assoc['path'] = $row['path'];
      $row_assoc['length'] = $row['length'];
      array_push($files, $row_assoc);
    }

    $data = array();
    $data['folders'] = $folders;
    $data['files'] = $files;

    mysqli_close($db);
    echo json_encode($data);
  }

?>
