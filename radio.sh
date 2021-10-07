#!/bin/bash
source /usr/local/bin/piradio.conf

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

bash /usr/local/bin/guard-radio.sh &
guard_PID=$!

for i in "${audiofiles[@]}"; do
#  echo "$i"
  sox "$i" -r 22050 -c 1 -b 16 -t wav - | sudo /usr/local/bin/fm_transmitter/fm_transmitter -f $frequency -
done
kill $guard_PID
