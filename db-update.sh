#!/bin/bash

DB_USER="www-data"
DB_PASSWD="dbpassword"
DB_NAME="piradio"
TABLE="audiofiles"

path="/home/ast/audio"
audiofiles=($(eval "find $path -name \*.mp3 -print"))

for ((i = 0 ; i < ${#audiofiles[@]} ; i++)); do
  if [[ "${audiofiles[$i]}" == $path* ]]; then
      : # do nothing
  else # if the audio file contains spaces, pin the strings together
    audiofiles[$i-1]="${audiofiles[$i-1]} ${audiofiles[$i]}"
    unset audiofiles[$i]
    audiofiles=( "${audiofiles[@]}" )
    i=$i-1
  fi
done

mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"CREATE TABLE IF NOT EXISTS $TABLE (id MEDIUMINT NOT NULL AUTO_INCREMENT, filename CHAR(100) NOT NULL, path CHAR(200) NOT NULL, length CHAR(50) NOT NULL, PRIMARY KEY (id));";
mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"TRUNCATE $TABLE"; # clear table every time. possible improvement to check if file exists in DB instead of deleting the table content every time

for i in "${audiofiles[@]}"; do
  length=$(soxi -V0 -d "$i") # -V0 Log Level 0, suppress warnings
  length="${length:1}" # delete first char. audio files max length ist therefore 9 hours 59 minutes
  length="${length::-3}" # delete the last 3 chars (Miliseconds)
  if [[ $length == 0:* ]]; then length="${length:2}"; else : ; fi # if length is lower than one hour, delete the hour chars
  IFS='/' read -r -a patharray <<< $i # split by '/'
  filename="${patharray[-1]}" # get the last element of splitted string
  path=$i
  mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"INSERT INTO audiofiles (filename, path, length) VALUES ('$filename', '$path', '$length');"
done
