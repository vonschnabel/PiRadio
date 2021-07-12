#!/bin/bash

DB_USER="www-data"
DB_PASSWD="dbpassword"
DB_NAME="piradio"
TABLE="audiofiles"

path="/home/ast/audio"
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

#printf '%s\n' "${audiofiles[@]}"

audiofilestmp=("${audiofiles[@]}")

for ((i = 0 ; i < ${#audiofilestmp[@]} ; i++)); do # delete the default path from the variables
  audiofilestmp[$i]=${audiofilestmp[$i]#$path}
done

#declare -i maxpathlength=0 #brauch ich das überhaupt
audiopaths=()

for ((i = 0 ; i < ${#audiofilestmp[@]} ; i++)); do # create the folders list
  readarray -d / -t arr <<<${audiofilestmp[i]}
  declare -i len=${#arr[@]}-2
#  if [[ $len > $maxpathlength ]]; then
#    maxpathlength=$len
#  fi
  if [ ! -z "${arr[-2]}" ]; then # mp3 files are skipped. paths are created for all files which are located in subfolders of the main folder $path.
    pathtmp=""
    for ((j = $len ; j > 0 ; j--)); do
      pathtmp="$pathtmp/${arr[-${j}-1]}"
      audiopaths+=("$pathtmp")
    done
#####    audiopaths+=("$pathtmp")
#    echo $pathtmp
  fi
#  echo "$len  ${audiofilestmp[i]}"
done

#printf '%s\n' "${audiopaths[@]}"

for ((i = 0 ; i < ${#audiopaths[@]} ; i++)); do # delete duplicates from folders list
  #echo ${audiopaths[i]}
  for ((j = 0 ; j < ${#audiopaths[@]} ; j++)); do
    if [ $i != $j ]; then
      #echo "i: ${i} j ${j}"
      if [ "${audiopaths[$i]}" == "${audiopaths[$j]}" ]; then
#        echo "${audiopaths[$i]}  ${audiopaths[$j]}"
        unset audiopaths[$j]
        audiopaths=( "${audiopaths[@]}" )
        j=$j-1
      fi
    fi
  done
done

#printf '%s\n' "${audiopaths[@]}"
#echo ""

IFS=$'\n' sortedaudiopaths=($(sort <<<"${audiopaths[*]}")); unset IFS # sort the array in alphabetical order
#printf '%s\n' "${sortedaudiopaths[@]}"

# Transfer files and folder structure into database
mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"CREATE TABLE IF NOT EXISTS $TABLE (id MEDIUMINT NOT NULL AUTO_INCREMENT, filename CHAR(100), path CHAR(200) NOT NULL, length CHAR(50), parent MEDIUMINT, PRIMARY KEY (id));";
mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"TRUNCATE $TABLE"; # clear table every time. possible improvement to check if file exists in DB instead of deleting the table content every time

for i in "${sortedaudiopaths[@]}"; do
  path=$i
  mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"INSERT INTO audiofiles (filename, path, length) VALUES (NULL, '$path', NULL);"
done

for i in "${audiofiles[@]}"; do
  length=$(soxi -V0 -d "$i") # -V0 Log Level 0, suppress warnings
  length="${length:1}" # delete first char. audio files max length ist therefore 9 hours 59 minutes
  length="${length::-3}" # delete the last 3 chars (Miliseconds)
  if [[ $length == 0:* ]]; then length="${length:2}"; else : ; fi # if length is lower than one hour, delete the hour chars
  IFS='/' read -r -a patharray <<< $i # split by '/'
  filename="${patharray[-1]}" # get the last element of splitted string
  path=$i
  mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"INSERT INTO $TABLE (filename, path, length) VALUES ('$filename', '$path', '$length');"
done

#pathaudiofiles=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD<<<"SELECT path FROM $TABLE where filename is not NULL")
#pathfolders=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD<<<"SELECT path FROM $TABLE where filename is NULL")
#idfolders=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD<<<"SELECT id FROM $TABLE where filename is NULL")

pathaudiofiles=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD -se "SELECT path FROM $TABLE where filename is not NULL")
pathfolders=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD -se "SELECT path FROM $TABLE where filename is NULL")
idfolders=$(mysql -N -D$DB_NAME -u$DB_USER -p$DB_PASSWD -se "SELECT id FROM $TABLE where filename is NULL")

readarray -t arrfolderspath <<<${pathfolders}
readarray -t arrfoldersid <<<${idfolders}
readarray -t arraudiofilespath <<<${pathaudiofiles}

#printf '%s\n' "${#arrfolderspath[@]}"
#printf '%s\n' "${arraudiofilespath[@]}"
#echo ""
#echo ""
#printf '%s\n' "${arrfolderspath[@]}"
#echo ""
#echo ""
#printf '%s\n' "${arrfoldersid[@]}"

#: '
for ((i = 0 ; i < ${#arraudiofilespath[@]} ; i++)); do
  filename=${arraudiofilespath[$i]}
#  echo  ${arraudiofilespath[$i]}
#  echo $i
  arraudiofilespath[$i]=${arraudiofilespath[$i]#$path}
  readarray -d / -t arr <<<${arraudiofilespath[$i]}
  declare -i len=${#arr[@]}-2

#  echo ${arraudiofilespath[$i]}
  pathtmp=""
  for ((j = $len ; j > 0 ; j--)); do
    pathtmp="$pathtmp/${arr[-${j}-1]}"
  done
#  echo ${pathtmp}

  for ((k = 0 ; k < ${#arrfolderspath[@]} ; k++)); do
#    echo "$k"
    if [[ "${arrfolderspath[$k]}" == $pathtmp ]]; then
#      echo "${arrfolderspath[$k]} ${arrfoldersid[$k]}"
      echo "$pathtmp"
#      echo "$filename"
#      mysql -D$DB_NAME -u$DB_USER -p$DB_PASSWD -e"UPDATE audiofiles SET parent = ${arrfoldersid[$k]} WHERE path = '$filename';"
    fi
  done
#  echo ""

done
#printf '%s\n' "${arrfoldersid[@]}"
#echo ${audiofiles[0,0]}

echo "fertig"
#'
