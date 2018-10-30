sudo apt-get update
sudo apt-get install apache2 -y
sudo chmod -R 777 /var/www/html
sudo apt-get install sqlite3 -y
sudo apt-get install php -y
sudo apt-get install php7.2-sqlite3 -y
sudo apt-get install php7.2-dev -y
sudo apt install qemu-kvm libvirt-bin bridge-utils -y
sudo apt install python-minimal -y


sudo cp src/libvirt-php.so /usr/lib/php/20170718/
sudo cp src/libvirt-php.la /usr/lib/php/20170718/
sudo cp src/libvirt-php.ini /etc/php/7.2/cli/conf.d/
sudo cp src/libvirt-php.ini /etc/php/7.2/apache2/conf.d/
sudo cp src/qemu /etc/libvirt/hooks
sudo chmod 777 /etc/libvirt/hooks/qemu
sudo chmod -R 777 /var/run/libvirt/libvirt-sock
sudo cp -r src/html/* /var/www/html
sudo chmod -R 777 /var/www/html
sudo systemctl restart libvirtd
sudo rm /var/www/html/index.html
sudo cp src/qemu /etc/libvirt/hooks
sudo wget "https://fedorapeople.org/groups/virt/virtio-win/direct-downloads/archive-virtio/virtio-win-0.1.149-1/virtio-win-0.1.149.iso" -P /var/www/html/includes/
sudo chmod 777 /var/www/html/includes/virtio-win-0.1.149.iso


sudo adduser www-data libvirt-qemu
sudo adduser www-data libvirt


echo "* * * * * php -f /var/www/html/includes/lab_session_checker.php" > mycron
crontab mycron
rm mycron

sudo systemctl restart libvirtd
sudo service apache2 restart
