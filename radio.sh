#!/bin/bash
source /usr/local/bin/piradio.conf
#echo "##########----------##########"
#echo ""
while getopts n:f: flag
do
	case "${flag}" in
		n) filename=${OPTARG};;
		f) frequency=${OPTARG};;
                #s) station=${OPTARG};;
	esac
done

IFS=' ' read -r -a audiofiles <<< $filename # split by space

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

for i in "${audiofiles[@]}"; do
#  echo "$i"
  text=$(basename "$i")
  IFS='.' read -r -a textarr <<< $text
#  echo $textarr
#  echo "$text"
  text=$textarr
#  echo "$text"
  sox -t mp3 "$i" -t wav - | sudo /usr/local/bin/PiFmRds/src/pi_fm_rds -freq $frequency -ps "$STATIONNAME" -rt "$text" -audio -
done
