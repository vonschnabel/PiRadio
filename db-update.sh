#!/bin/bash
source /usr/local/bin/piradio.conf

echo "#############################"
echo "#                           #"
echo "#  PiRadio Database update  #"
echo "#                           #"
echo "#############################"
echo
echo "Audiopath: $path"
echo "Database: $DB_NAME"
echo "Tablename: $TABLE"
echo "DB User: $DB_USER"
echo

audiofiles=($(eval "find $path -name \*.mp3 -print"))

for ((i = 0 ; i < ${#audiofiles[@]} ; i++)); do # fill the list of audiofiles
  if [[ "${audiofiles[$i]}" == $path* ]]; then
      : # do nothing
  else # if the audio file contains spaces, pin the strings together
    audiofiles[$i-1]="${audiofiles[$i-1]} ${audiofiles[$i]}"
    unset audiofiles[$i]
    audiofiles=( "${audiofiles[@]}" )
    i=$i-1
  fi
done

audiofilestmp=("${audiofiles[@]}")

for ((i = 0 ; i < ${#audiofilestmp[@]} ; i++)); do # delete the default path from the variables
  audiofilestmp[$i]=${audiofilestmp[$i]#$path}
done

audiopaths=()

for ((i = 0 ; i < ${#audiofilestmp[@]} ; i++)); do # create the folders list
  readarray -d / -t arr <<<${audiofilestmp[i]}
  declare -i len=${#arr[@]}-2
  if [ ! -z "${arr[-2]}" ]; then # mp3 files are skipped. paths are created for all files which are located in subfolders of the main folder $path.
    pathtmp=""
    for ((j = $len ; j > 0 ; j--)); do
      pathtmp="$pathtmp/${arr[-${j}-1]}"
      audiopaths+=("$pathtmp")
    done
  fi
done


for ((i = 0 ; i < ${#audiopaths[@]} ; i++)); do # delete duplicates from folders list
  for ((j = 0 ; j < ${#audiopaths[@]} ; j++)); do
    if [ $i != $j ]; then
      if [ "${audiopaths[$i]}" == "${audiopaths[$j]}" ]; then
        unset audiopaths[$j]
        audiopaths=( "${audiopaths[@]}" )
        j=$j-1
      fi
    fi
  done
done

IFS=$'\n' sortedaudiopaths=($(sort <<<"${audiopaths[*]}")); unset IFS # sort the array in alphabetical order

# Transfer files and folder structure into database
mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"CREATE TABLE IF NOT EXISTS $TABLE (id MEDIUMINT NOT NULL AUTO_INCREMENT, filename CHAR(100), path CHAR(200) NOT NULL, length CHAR(50), parent_id MEDIUMINT, PRIMARY KEY (id));";
mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"TRUNCATE $TABLE"; # clear table every time. possible improvement to check if file exists in DB instead of deleting the table content every time

for i in "${sortedaudiopaths[@]}"; do
  pathvar=$i
  readarray -d / -t arr <<<$pathvar
  filename=${arr[-1]}
  filename=${filename//$'\n'/} # remove newline
  mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"INSERT INTO audiofiles (filename, path, length) VALUES ('$filename', '$pathvar', NULL);"
done

for i in "${audiofiles[@]}"; do
  length=$(soxi -V0 -d "$i") # -V0 Log Level 0, suppress warnings
  if [ -z "$length" ];then # length could not be read. skipping this file
    echo "the length of this file could not be read, skipping it: $i"
  else
    length="${length:1}" # delete first char. audio files max length ist therefore 9 hours 59 minutes
    length="${length::-3}" # delete the last 3 chars (Miliseconds)
    if [[ $length == 0:* ]]; then length="${length:2}"; else : ; fi # if length is lower than one hour, delete the hour chars
    IFS='/' read -r -a patharray <<< $i # split by '/'
    filename="${patharray[-1]}" # get the last element of splitted string
    pathvar=$i
    mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"INSERT INTO $TABLE (filename, path, length) VALUES ('$filename', '$pathvar', '$length');"
  fi
done


pathaudiofiles=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD -se "SELECT path FROM $TABLE where length is not NULL")
pathfolders=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD -se "SELECT path FROM $TABLE where length is NULL")
idfolders=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD -se "SELECT id FROM $TABLE where length is NULL")

readarray -t arrfolderspath <<<${pathfolders}
readarray -t arrfoldersid <<<${idfolders}
readarray -t arraudiofilespath <<<${pathaudiofiles}

for ((i = 0 ; i < ${#arrfolderspath[@]} ; i++)); do # set the parent ID for the folders
  readarray -d / -t arr <<<${arrfolderspath[$i]}
  declare -i len=${#arr[@]}-1
  if [ $len == 1 ]; then # root folders get the ID 0
    mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"UPDATE $TABLE SET parent_id = 0 WHERE path = '${arrfolderspath[$i]}';"
  else
    parentpath=""
    for ((j = $len ; j > 1 ; j--)); do
      parentpath="${parentpath}/${arr[-$j]}"
    done
    for ((k = 0 ; k < ${#arrfolderspath[@]} ; k++)); do
      if [[ "${arrfolderspath[$k]}" == $parentpath ]]; then
        mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"UPDATE $TABLE SET parent_id = ${arrfoldersid[$k]} WHERE path = '${arrfolderspath[$i]}';"
      fi
    done
  fi
done

for ((i = 0 ; i < ${#arraudiofilespath[@]} ; i++)); do
  filename=${arraudiofilespath[$i]}
  arraudiofilespath[$i]=${arraudiofilespath[$i]#$path}
  readarray -d / -t arr <<<${arraudiofilespath[$i]}
  declare -i len=${#arr[@]}-2

  pathtmp=""
  for ((j = $len ; j > 0 ; j--)); do
    pathtmp="$pathtmp/${arr[-${j}-1]}"
  done

  for ((k = 0 ; k < ${#arrfolderspath[@]} ; k++)); do
    if [[ "${arrfolderspath[$k]}" == $pathtmp ]]; then
      mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"UPDATE $TABLE SET parent_id = ${arrfoldersid[$k]} WHERE path = '$filename';"
    fi
  done
done

# set the parent ID 0 for all files in the root path
mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"UPDATE $TABLE SET parent_id = 0 WHERE (parent_id IS NULL) AND (filename IS NOT NULL);"

echo "script finnished"
