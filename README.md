# desktop-provisioning-system
Remote desktop provisioning system - Allows the creation of virtual laboratories that utilise virtual machines, accessed through your desired web browser.

### Requirements
The system has only been installed and tested to work on Ubuntu 18.04 x64.

### Installation
```
# sudo apt install unzip
# wget https://github.com/Jacoblairm/desktop-provisioning-system/archive/master.zip
# unzip master.zip
# cd desktop-provisioning-system-master
# sudo chmod +x install.sh
# sudo ./install.sh
```
The system will then take around 5 minutes to install.

Please reboot the system to ensure all services are correctly started.


### Configuration
Edit /var/www/html/settings.php to your desired configuration.
This file allows you to change certain image directories, virtual machine settings, network settings and miscellaneous system settings.
Note - All directories and images you use are required to have full read/write access

#### Image Installation
This system only supports the .qcow2 image format. 
Place your .qcow2 base images to the directory that you have set in the settings.php
Note - Ensure all images have full read/write permissions.

#### Image Conversion
Throughout building this system, I mainly used the Cisco netacad laboratories for testing purposes. These laboratory activities require you to install virtual machines to Virtualbox VMware using a provided .vmdk image file (which is the format used by Virtualbox VMware). To use these images in this system, they need to be converted to a usable format - qcow2.

##### Convert .vmdk to .qcow2
```
# qemu-img convert -f vmdk -O qcow2 inputimage.vmdk outputimage.qcow2
```
##### Convert Multipart .vmdk to single .qcow2
```
# files=(DirectoryOfMultipartVDMK*); qemu-img convert -f vmdk -O qcow2 ${files[@]} ${files%-s001.vmdk}.qcow2;
```
