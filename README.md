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


























sudo apt update && sudo apt upgrade -y
sudo apt install mariadb-server php-mysql apache2 php php-mbstring libapache2-mod-php hostapd dnsmasq git make build-essential sox libsox-fmt-mp3 -y
sudo mysql -e "CREATE DATABASE piradio"
sudo mysql -e "CREATE USER 'www-data'@'localhost' IDENTIFIED BY 'dbpassword';"
sudo mysql -e "GRANT ALL PRIVILEGES ON piradio.* TO 'www-data'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
mysql -Dpiradio -uwww-data -pdbpassword -e"CREATE TABLE IF NOT EXISTS audiofiles (id MEDIUMINT NOT NULL AUTO_INCREMENT, filename CHAR(255), path CHAR(255) NOT NULL, length CHAR(50), parent_id MEDIUMINT, PRIMARY KEY (id));";

git clone https://github.com/vonschnabel/PiRadio
sudo mkdir /var/www/html/PiRadio
sudo mv ./PiRadio/080_piradio /etc/sudoers.d/
sudo chown root:root /etc/sudoers.d/080_piradio
sudo chmod 440 /etc/sudoers.d/080_piradio
sudo mv ./PiRadio/piradio.conf /usr/local/bin/
sudo mv ./PiRadio/db-update.sh /usr/local/bin
sudo mv ./PiRadio/guard-radio.sh /usr/local/bin
sudo mv ./PiRadio/radio.sh /usr/local/bin
sudo chmod +x /usr/local/bin/db-update.sh
sudo chmod +x /usr/local/bin/guard-radio.sh
sudo chmod +x /usr/local/bin/radio.sh
ln -s /usr/local/bin/piradio.conf ~/.piradio.conf
sudo mv ./PiRadio/piradio.html /var/www/html/PiRadio/
sudo mv ./PiRadio/fmradio.php /var/www/html/PiRadio/
rm -rf PiRadio/

sudo wget -P /var/www/html/PiRadio https://raw.githack.com/SortableJS/Sortable/master/Sortable.js
sudo wget -P /var/www/html/PiRadio https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css
sudo wget -P /var/www/html/PiRadio https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js
sudo wget -P /var/www/html/PiRadio https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js

git clone https://github.com/markondej/fm_transmitter
sudo mv ./fm_transmitter/ /usr/local/bin
cd /usr/local/bin/fm_transmitter
sudo make

git clone https://github.com/daweilv/treejs
sudo mv ./treejs/dist /var/www/html/PiRadio
sudo mv ./treejs/src /var/www/html/PiRadio
sudo rm -rf treejs/

