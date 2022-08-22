# PiRadio

Installation:

``` 
sudo apt update && sudo apt upgrade -y
sudo apt install git
git clone https://github.com/vonschnabel/PiRadio.git
mv ./PiRadio/setup-piradio.sh ./
chmod +x ./setup-piradio.sh
sudo ./setup-piradio.sh

After the installation has finished, edit the hidden file .piradio.conf in the folder where you have installed PiRadio or edit it directly in /usr/local/bin/piradio.conf
Change the path variable according to your configuration. It should point to your audio folder. Now transfer the audiofiles to your specified audio path. The last step is to update the SQL Database with the audiofile information. Run sudo /usr/local/bin/db-update.sh and wait untill its finnished. Update your database every time you change something in the audio folder.
