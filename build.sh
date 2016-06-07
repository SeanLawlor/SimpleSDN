#! /bin/bash

#needs to be run as root
if [[ $USER != "root" ]]; then
	echo "this script should be run as root!";
	exit;
fi

curr_dir=$(pwd);
calling_user=`who -m | awk '{print $1}'`;

# #build itg binaries and move to target web-app folder
cd ./itg/src;
make clean all;
make;
cd $curr_dir;
cp ./itg/bin/ITGRecv ./itg/ITGRecv
cp ./itg/bin/ITGSend ./itg/ITGSend

#Make these executable
chmod +x ./itg/ITGSend
chmod +x ./itg/ITGRecv
chmod +x ./floodlight/floodlight.jar

#Make required directory for Floodlight
mkdir /var/lib/floodlight
chmod 777 /var/lib/floodlight

#Mininet scripts need to be added to sudoers file so that the web-app can start Mininet - running entire app as sudo = bad idea 
path_to_mininet=${curr_dir}/mininet/mn.py

#If not already added, use visudo to create entry
if ! grep -R "$calling_user ALL =(ALL) NOPASSWD: $path_to_mininet" '/etc/sudoers'; then
	echo "$calling_user ALL =(ALL) NOPASSWD: $path_to_mininet" | (EDITOR="tee -a" visudo)
fi