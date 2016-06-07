#Mininet
Resources and information about Mininet can be at <http://mininet.org/>.

##Starting the Mininet Server

Mininet can be started by executing the following script:  
(Note: Mininet currently requires root access, so this script needs to be run as the root user.)
    
    `sudo /mininet/mn.py` 
    
The purpose of this script is to start Mininet for use with the SDN controller, but also allow for a certain number of operations to be executed by an external program. 
In particular, it allows a user to run network applications between hosts on the network.
It does so by listening on a port for a range of predefined commands (See Below).

The script has the following arguments

1. `-t <TOPO_NAME>`     
    * Required
    * Specifies which topology should be used
2. `-p <PORT_NUMBER>`
    * Optional (default value:50007)
    * Specifies the listen port
3. `-x`
    * If specified a terminal is opened for each node in the network
4. `-q`
    * Run the switches with queues
    * 2 Queues by default with Minimum transmission Rates



In the case that Mininet does not exit properly, network setup/information may remain. This can affect subsequent executions of Mininet. Simply run the following command to clean up any left-over junk:

    `sudo mn -c`

##Modifying /etc/sudoers
To allow the web application to execute the above script, it is recommended that it is added to the `sudoers` file. This allows the script to be executed without requiring the root password.
Modifying '/etc/sudoers' should be done with the program `visudo`:
    `sudo visudo`
    
Append the following (replacing user and path as required):

    `user ALL =(ALL) NOPASSWD: /_PATH_/_TO_/_FOLDER_/mininet/mn.py`     
    
##Server Commands
The following is a list of the current commands that can be sent to the server:

1. `up?`
    * Command to probe the status of the server
    * Returns "yes"
2. `quit`, `exit` & `stop`
    * Safely stops Mininet and stops execution of the script
3. `pingall` 
    * Sends a 'ping' from each host to every other host
4. `applications?`
    * Returns a JSON encoded array of available applications that can be run between hosts
    * {  'applications':[  {'desc':'Simple Desc', 'cmd':'APP_CMD'}, ... ] }
5. `run <APP_CMD> <SRC_ADDR> <DEST_ADDR> [<TIME_SECS>]`
    * Executes the specified APP_CMD between the specified hosts.
    * Length of time in seconds can be specified (default: 10 secs)
    * See below for currently available commands
    * <SRC_ADDR> and <DEST_ADDR> can be either the IP address or the MAC address of a host.
6. `hosts?`
    * Returns a list of host IP addresses
    * ["10.0.0.1", ... ]
7. `killall`
    * Stops the execution of any applications running between hosts


Communications with the server are done using sockets.
A useful tool for carrying out these communications is `netcat` (or simply `nc`).

    `echo "<cmd>" | netcat <IP> <PORT_NUMBER>`

For example, the following command will cause a locally running script (listening on the default port) to exit.

    `echo quit | netcat localhost 50007`
    

###Available Application Commands
Application commands that are currently available:

1. `ping`
    * Send a single ping 		
2. `voip_simple`    
    * Uni-directional VoIP traffic using the G7.11 audio codec over RTP (Real-time Transport Protocol)
3. `voip_bidirectional`
    * Same as previous but bi-directional
4. `voip_adv`
    * Uni-directional VoIP traffic using the G7.11 audio codec over cRTP (Compressed Real-time Transport Protocol) with VAD (Voice Activity Detection)
5. `dns`
    * Run DNS (Domain Name System) traffic from one host to the other
6. `telnet`
    * Run a Telnet session between two hosts
7. `video`
    * Sends a 3Mbs UDP stream

Some of the above commands use [D-ITG](http://traffic.comics.unina.it/software/ITG/) (Distributed Internet Traffic Generator) to generate application layer traffic.

To add another application command, create a new function in `/mininet/applications.py` with your desired functionality. Update the return value of the `available()` function with a brief description or title and the name of the function.

##Server Topologies

Network topologies can be found in the folder:  
`/mininet/topologies/`  

The file 'custom.py' will be created and over-written by the web application in the case that a user defines a new topology through the user interface.

New topologies can be created and added by defining a new class file in the aforementioned folder. A header template is provided (`/mininet/topo_template.py`) and is an instance of the Mininet Python class ([mininet.net.Mininet](http://mininet.org/api/classmininet_1_1net_1_1Mininet.html)).
The full Mininet Python API can be found [here](http://mininet.org/api/annotated.html).