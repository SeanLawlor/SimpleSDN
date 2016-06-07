from mininet.topo import Topo
from mininet.net import Mininet

"""
	Note: 	if creating your own topology,
			a controller ('c0') will be added automatically. 
			To avoid conflicts, avoid naming elements 'c0'.

	Please see http://mininet.org/api/annotated.html for the full reference API
	Ex.:	
			s1 = self.addSwitch('s1')
			h1 = self.addHost('h1')
			self.addLink(s1, h1)
"""

#mininet.net.Mininet Class
class custom(Topo):
    def build(self):	
    	#define topology here.
		#The topology will be automatically started.
