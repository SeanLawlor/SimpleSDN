# SimpleSDN
A browser-based application for interacting with OpenFlow-enabled networks. 
<img src="./preview.png" width="90%">

Allows sample networks to be created and deployed using [Mininet](http://mininet.org).

Visualise and interact with OpenFlow networks through an SDN controller - currently uses a modified version of [Floodlight](http://www.projectfloodlight.org/floodlight/).

---

##Supported Systems
A Linux distribution that supports Open vSwitch is required
	
    Ubuntu >=15.04 (Recommended 15.04 - tried and tested)
    Fedora also supports recent OVS versions

> Note: OpenFlow handshake failing between Mininet & Floodlight in Ubuntu <15.04 versions.

---

##Dependencies

###Installing dependencies on Ubuntu:
`$sudo apt-get install php5 curl default-jre ant make gcc g++ mininet`


Used For | Dependencies
--- | --- | ---
Application server | `php5 curl netstat netcat/nc`
Floodlight         | `default-jre`
Building Floodlight | `ant` 
Compiling D-ITG | `make gcc g++`
Mininet | `mininet`

---

##Building

Build script should first be run as root from the same directory:
	`$ sudo ./build.sh`

This build script does the following: 
* Compiles D-ITG and moves it to the required folder
* Creates a folder required by floodlight (/var/lib/floodlight)
* Adds an exception to the sudoers file (if it does not already exist) to allow the mininet script to be executed by the web application (Changing the path afterwards will require the sudoers file to be updated)

---

##Running

To start the application + Floodlight:
  `$ ./quickstart.sh`

Or just the application using
  `$ ./start.sh`  

If manually starting Floodlight, config file specified using '-cf' flag:
	`$ ./floodlight.jar -cf /Path/To/fl.properties`
	
Mininet can be started for use with this setup using the command followed by requried arguments
  `$ sudo ./mininet/mn.py`

This runs the web-app on port 55555

---

##Development and testing environment:
```
OS:				Ubuntu 15.04 64bit
Mininet:		version 2.2.0b1		(Installed from Ubuntu repo)
Floodlight: 	v1.2 
Php:			PHP 5.6.4-4ubuntu6.4 (Installed from Ubuntu repo)
```
