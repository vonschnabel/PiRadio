# PiRadio

Installation:

``` 
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 php php-mbstring libapache2-mod-php hostapd dnsmasq git make build-essential sox libsox-fmt-mp3 -y

sudo sed -i 's/Priv/#Priv/g' /lib/systemd/system/apache2.service

sudo mkdir /var/www/html/tmp
sudo chown www-data:www-data /var/www/html/tmp

sudo systemctl unmask hostapd
sudo systemctl enable hostapd

sudo /bin/su -c "echo net.ipv4.ip_forward=1 >> /etc/sysctl.d/99-sysctl.conf"

sudo reboot

wget https://www.w3schools.com/w3css/4/w3.css
wget http://code.jquery.com/jquery-1.11.3.min.js
wget http://code.jquery.com/jquery-migrate-1.2.1.min.js
git clone https://github.com/vonschnabel/PiRadio
git clone https://github.com/vonschnabel/RaspiAP.git
git clone https://github.com/markondej/fm_transmitter

sudo mv ./w3.css /var/www/html/
sudo mv ./jquery-1.11.3.min.js /var/www/html/
sudo mv ./jquery-migrate-1.2.1.min.js /var/www/html/
sudo mv ./PiRadio/090_raspap /etc/sudoers.d
sudo chown root:root /etc/sudoers.d/090_raspap
sudo chmod 440 /etc/sudoers.d/090_raspap
sudo mv ./PiRadio/fmradio.php /var/www/html
sudo mv ./RaspiAP/functions.php /var/www/html/
sudo mv ./RaspiAP/hotspot.php /var/www/html/
sudo mv ./RaspiAP/setup-hotspot.sh setup-hotspot.sh
chmod +x setup-hotspot.sh
sudo mv ./PiRadio/radio.sh radio.sh
chmod +x radio.sh
rm -rf RaspiAP
rm -rf PiRadio
mkdir audio
cd fm_transmitter
make
