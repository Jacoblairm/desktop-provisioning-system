sudo apt-get install sqlite3
sqlite> PRAGMA foreign_keys = ON
sudo apt-get install php7.2-sqlite3
sudo apt-get install php7.2-dev 

wget http://libvirt.org/sources/php/libvirt-php-0.5.4.tar.gz
gunzip -c libvirt-php-0.5.4.tar.gz | tar xvf -
put libvirt-php.c in the src folder
 cd libvirt-php-0.5.4/
./configure.sh
make
sudo make install
### Copy libvirt-php.ini from /etc/php/7.2/cli/conf.d and put in /etc/php/7.2/apache2/conf.d
#libvirt should appear  $ php -m | grep libvirt


adduser www-data libvirt-qemu
adduser www-data libvirtd
adduser www-data libvirt

#/var/www/html needs read/write permissions 777

#cron install
* * * * * php -f /var/www/html/includes/lab_session_checker.php
