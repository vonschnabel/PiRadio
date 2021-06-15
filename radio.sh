while getopts n:f: flag
do
	case "${flag}" in
		n) filename=${OPTARG};;
		f) frequency=${OPTARG};;
	esac
done
#echo "Audiofile $filename";
#echo "Frequency $frequency";
sox $filename -r 22050 -c 1 -b 16 -t wav - | sudo /home/ast/fm_transmitter/fm_transmitter -f $frequency - > /dev/null
#echo "hallo";
#sleep 600;
#echo "welt";
