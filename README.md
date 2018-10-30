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
Edit /var/www/html/setting.php to your desired configuration

#### Installing Images
Place .qcow2 images to your backing image directory (set in settings.php)


##### Convert .vdmk to .qcow2
```
qemu-img convert -f vmdk -O qcow2 SecurityOnion_[20170907]-disk001.vmdk securityonion.qcow2;
```
##### Convert Multipart VDMK to single QCOW2
```
files=(DirectoryOfMultipartVDMK*); qemu-img convert -f vmdk -O qcow2 ${files[@]} ${files%-s001.vmdk}.qcow2;
```
