#!/bin/bash

path="/home/ast/audio"

while getopts n:f: flag
do
	case "${flag}" in
		n) filename=${OPTARG};;
		f) frequency=${OPTARG};;
	esac
done

#echo "Audiofile $filename";
#echo "Frequency $frequency";

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

bash /home/ast/guard-radio.sh &
guard_PID=$!

for i in "${audiofiles[@]}"; do
  sox "$i" -r 22050 -c 1 -b 16 -t wav - | sudo /home/ast/fm_transmitter/fm_transmitter -f $frequency -
done
kill $guard_PID

#sox "$filename" -r 22050 -c 1 -b 16 -t wav - | sudo /home/ast/fm_transmitter/fm_transmitter -f $frequency -
#echo "hallo";
#sleep 600;
#echo "welt";
