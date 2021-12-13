#!/bin/bash
source /usr/local/bin/piradio.conf
echo "##########----------##########"
echo ""
while getopts n:f:j: flag
do
	case "${flag}" in
		n) filename=${OPTARG};; # local path of the file which will be played
		f) frequency=${OPTARG};; # transmitting frequency
                j) jump=${OPTARG};; # jump to this audiofile position (in seconds)
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

#if [ -z "$jump" ]; then echo "var is blank"; else echo "var is set to '$jump'"; fi
#echo "${audiofiles[0]}" ####
#echo ""

for i in "${audiofiles[@]}"; do
    text=$(basename "$i")
    IFS='.' read -r -a textarr <<< $text
    text=$textarr
  if [ "$i" == "${audiofiles[0]}" ] && ! [ -z "$jump" ]; then
    sox -t mp3 "$i" -t wav - trim $jump | sudo /usr/local/bin/PiFmRds/src/pi_fm_rds -freq $frequency -ps "$STATIONNAME" -rt "$text" -audio -
  else
    sox -t mp3 "$i" -t wav - | sudo /usr/local/bin/PiFmRds/src/pi_fm_rds -freq $frequency -ps "$STATIONNAME" -rt "$text" -audio -
  fi
done
