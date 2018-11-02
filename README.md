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


##User Manual
Login with your credentials.
Select the laboratory you would like to access.
Press “Initialise Lab” to create the laboratory session.
Press “Boot machines” to start your session. The laboratory will take a couple seconds to boot up.
You can now access and work on your laboratory activity. Information about your laboratory is displayed as well as the desktop screens that you can interface with. Above each screen is a link to access the desktop environment in a separate browser window/tab.
If the networking of your desktop environments has been properly configured, their IPv4 addresses are shown. If you leave the laboratory page your desktop environments will shut down after a period of time.
You can shut down your machines by pressing “Stop lab”. This will put your machines into an “Off” state. Please ensure to save your work before you press this.
Once your laboratory activity is complete and no longer needed, or you would like to start fresh. You can wipe the lab by pressing “Stop lab” and then “Wipe lab”. This will clear all the progress you have made with the current lab.
To access another laboratory activity, you need to ensure any other lab sessions are shut down by pressing “Stop lab”.






##Admin Manual

####Main page / Virtual networks
Here you can view all the defined domains and networks. Information is shown as well as a few options to stop and view the XML information of the domain.
####Users
Here you can view users, add users or change the password for a user. When adding a user, the username must be unique.
####Laboratories
This page allows you to view laboratories as well as adding them:
VM Count: The number of desktop environments the laboratory will use.
Lab name: The name of the laboratory (required but does not have to be unique)
Select Operating Systems: The operating systems that will be used for each virtual desktop instance.
Additional lab info: Optional, allows you to provide additional information about the laboratory, for example, a lab script or steps to complete the activity etc.

####Operating Systems
This page shows all the installed operating systems as well as their file paths.
To install or remove an operating system, simply delete or add the .qcow2 image to the directory defined in settings.php ($base_image_location).
