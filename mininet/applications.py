#Returns a list of the available commands with a title/description
#Update return val when adding new function/command 
def available():
 return {
 	'applications':[
 		{'desc':'Single ping', 'cmd':'ping'},
 		{'desc':'Ping All Pairs', 'cmd':'pingall'},
 		{'desc':'Voip - RTP, G7.11', 'cmd':'voip_simple'},
 		{'desc':'Voip - CRTP, G7.11, VAD', 'cmd':'voip_adv'},
 		{'desc':'Bi-directional Voip - RTP, G7.11', 'cmd':'voip_bidirectional'},
 		{'desc':'DNS', 'cmd':'dns'},
 		{'desc':'Telnet', 'cmd':'telnet'},
 		{'desc':'Video 3Mbps', 'cmd':'video'}
	]
 }

################### applications ###################

def ping(net, src, dest, time=None):
	nodes = getNodes(net, [src, dest])
	
	allowMultipleProcesses(nodes)
	nodes[0].sendCmd('ping -c 1 '+nodes[1].IP()+' &> /dev/null &');
	allowMultipleProcesses(nodes)

def pingall(net, src, dest, time=None):
	allowMultipleProcesses(net.hosts)

	hosts = net.hosts
	i = 0
	while i < len(hosts):
		j = i+1
		while j <len(hosts):			
			hosts[i].sendCmd('ping -c 1 '+hosts[j].IP()+' &> /dev/null &');
			allowMultipleProcesses(net.hosts)
			j+=1
		i+=1


def video(net, src, dest, time):
	nodes = getNodes(net, [src, dest])
	
	allowMultipleProcesses(nodes)
	#Imitates video traffic by sending a 3Mbs UDP stream with TOS 184 (High priority) 
	nodes[0].sendCmd('iperf -c '+nodes[1].IP()+' -u -b 3000000 -S 184 -t '+ time +' &')
	nodes[1].sendCmd('iperf -s &')
	allowMultipleProcesses(nodes)
	
 

def voip_simple(net, src, dest, time):
	nodes = getNodes(net, [src, dest])	
	#Default codec = G.711
	ITG(nodes, time,'VoIP')	


def voip_adv(net, src, dest, time):
	nodes = getNodes(net, [src, dest])
	#Default codec = G.711 with RTP header compression and Voice activity detection enabled
	ITG(nodes, time, ' VoIP -h CRTP -VAD')
	
def voip_bidirectional(net, src, dest, time):
	nodes = getNodes(net, [src, dest])
	#Default codec = G.711 with RTP header compression and Voice activity detection enabled
	ITG(nodes, time, ' VoIP -h CRTP -VAD')
	ITG(nodes[::-1], time, ' VoIP -h CRTP -VAD')	


def dns(net, src, dest, time):
	nodes = getNodes(net, [src, dest])
	ITG(nodes, time,'dns')

def telnet(net, src, dest, time):
	nodes = getNodes(net, [src, dest])
	ITG(nodes, time, 'Telnet')


################### Utility functions ###################

#runs ITG arg 'sendCmd' between the first 2 nodes in 'nodes' for 'secs' seconds
#First node is the sender, second is the receiver 
def ITG(nodes, secs, sendCmd):
	print("starting ITG traffic")
	allowMultipleProcesses(nodes)
	nodes[1].sendCmd('./itg/ITGRecv &')
	nodes[0].sendCmd("./itg/ITGSend -a "+nodes[1].IP()+' -t '+secs+'000 '+sendCmd+ " &")
	allowMultipleProcesses(nodes)

#returns each node object from 'net' that has an IP or MAC address in 'arr' 
def getNodes(net, arr):
	ret = []
	for name in arr:
		ret.append( filter(lambda x: x.IP()==name or x.MAC()==name, net.hosts)[0])
	return ret


"""
 ** Mininet workaround **

 There are 2 options for executing a command on a mininet host:	
 	cmd() - execute command but wait for output before returning
 	sendCmd() - execute command but return without waiting for completion

 It's preferable to be able to execute multiple commands in parallel

 A Node in mininet has it's waiting attribute set to 'true' when it starts running a process, and false when the process finishes.
 When this attribute is 'true', further processes are prevented from running. 
 This allows the output of a process to be collected and returned.
 
 As such, to allow multiple processes to be executed at the same time on each host, 
 we can set this attribute to false.
"""
def allowMultipleProcesses(nodes):
	for node in nodes:
		node.waiting=False
