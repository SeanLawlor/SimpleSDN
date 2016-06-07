#!/usr/bin/python

from mininet.cli import CLI
from mininet.log import setLogLevel
from mininet.net import Mininet
from mininet.topo import Topo
from mininet.node import RemoteController
from mininet.link import TCLink
from mininet.node import *
from OVSBaseQosSwitch import *

import socket, sys, getopt, re, importlib, os, json, thread, signal
import applications


HOST = ''
PORT = 50007
RUN_TIME_DEFAULT = 10
my_mn = None


def init(topo, start_terminals=False, use_queues=False):    
    #create and start mininet network

    if use_queues:  #Use modified OVSHtbQosSwitch with queues, normal OVsSwitch otherwise
        print "using other switch"
        net  =  Mininet(topo=topo, controller=RemoteController, link=TCLink , switch=OVSHtbQosSwitch)
    else:
        net  =  Mininet(topo=topo, controller=RemoteController, link=TCLink)

    c = RemoteController( 'c0')
    net.addController(c)
    my_mn = net;

    net.start()
    if start_terminals:
        net.startTerms()

    if use_queues:
	    for sw in net.switches:
             print("sw:"+sw.name);
             for intf in sw.intfList():
                if intf.name != "lo":
                    print("intf"+intf.name);
                    os.system("ovs-vsctl -- set port "+intf.name+" qos=@newqos -- --id=@newqos create qos type=linux-htb \
					queues=0=@q0,1=@q1 -- --id=@q0 create queue other-config:min-rate=2000000 \
					-- --id=@q1 create queue other-config:min-rate=5000000 \
					other-config:max-rate=5000000\
					");


    #used for initial device discovery
    # net.pingAll()
    
    return net


#(Closes any open ports & shuts down the mininet network)
def graceful_exit(signal, frame):
    net.stop()
    print('*exit*')
    os._exit(0)


def run(net):
    execRegex = re.compile('run\s+(\S+)\s+(\S+)\s+(\S+)(\s+\d+)?')

    # would like to send commands to mininet from an external program  
    # set up a simple server to listen on PORT
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

    # allow this script to re-use the default port 
    #   if quickly re-running this script, and TCP TIME_WAIT is present on the port, a crash occurs without the following setting
    s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    
    s.bind((HOST, PORT))
    s.listen(5)

    run = True
    while run:
        #Accept an incoming connection
        try:
            connection, address = s.accept()
        except:
            net.stop()
            break

        data = connection.recv(256)
        input = str(data).strip()
          
        if input == "up?":
            connection.sendall("yes")
            
        elif input == "quit" or input == "exit" or input == "stop":
            net.stop()
            run = False
            print('Exiting')

        elif input == "applications?":
            connection.sendall(json.dumps(applications.available()))
            
        elif input == "killall":
        	print('Received request to stop all traffic')
        	for node in net.hosts:
        		node.waiting = False
        		node.sendCmd('kill $(ps | awk \'{if(!match($0, "bash")) print $1;}\')');

        elif input == "hosts?":
        	retVal = []
        	for node in net.hosts:
        		retVal.append(node.IP())
        	connection.sendall(json.dumps(retVal))

        elif execRegex.match(input)!=None:
            match = execRegex.match(input);
            app = match.group(1)
            src = match.group(2)
            dest = match.group(3)
            time = match.group(4)
            if(time==None):
                time = RUN_TIME_DEFAULT
                
            # Dynamically load function corresponding to specified command and execute in a new thread
            if app in map( lambda x: x['cmd'] , applications.available()['applications']):
                print('running app'+app)
                thread.start_new_thread(getattr(applications, app), (net, src, dest, str(time)))
                # getattr(applications, app)(net, src, dest, str(time))

        connection.close()

    s.close()

	

if __name__ == '__main__':
    setLogLevel( 'info' )
    os.system("mn -c")
    opts, args = getopt.getopt(sys.argv[1:],"t:p:xq")
    start_terminals = False;
    use_queues = False;
    for opt,arg in opts:
        if opt=='-t':
	       toponame = arg
        if opt=='-p':
            PORT = int(arg)
        if opt=='-x':
            start_terminals = True;
        if opt=='-q':
        	use_queues = True;
    try:
        topo = getattr(importlib.import_module('topologies.'+toponame), toponame)
    except NameError:
        print('topology name not defined ( mn.py -t topo_name)')
        os._exit(-1)    

    #Setup SIGINT (^C - keyboard interrupt) and SIGTERM (termination) signal handling for graceful exit
    signal.signal(signal.SIGTERM, graceful_exit)

    #Setup topology
    net = init(topo(), start_terminals, use_queues)
    
    #And away we go	
    run(net)

    os._exit(0)


