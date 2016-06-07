from mininet.topo import Topo

from mininet.net import Mininet

class multiRouteNoLoss(Topo):

    def build(self):	


    	#Have 2 hosts each attached to a switch 
		s1 = self.addSwitch('s1')
		s2 = self.addSwitch('s2')

		h1 = self.addHost('h1')
		h2 = self.addHost('h2')
		
		self.addLink(s1, h1, bw=10, delay='0.01ms', jitter='0.01ms')
		self.addLink(s2, h2, bw=10, delay='0.01ms', jitter='0.01ms')



		#Have intermediate switches -> different available routes 
		s3 = self.addSwitch('s3')
		s4 = self.addSwitch('s4')
		s5 = self.addSwitch('s5')
		s6 = self.addSwitch('s6')
		s7 = self.addSwitch('s7')

		#connect attachment point switches with just 1 intermediary switch (other paths have 2)
		#shortest path (low hops, high delay, high jitter, high loss)
		self.addLink(s1, s7, bw=10, delay='10ms', jitter='5ms')
		self.addLink(s2, s7, bw=10, delay='10ms', jitter='5ms')

		#to create alternative path with greater number of hops - add intermediate switch
		#no adverse settings on this link
		self.addLink(s1, s3, bw=10, delay='0.01ms', jitter='0.01ms')


		#from intermediate switch - add paths with different characteristics
		
		#least delay
		self.addLink(s3, s4, bw=10, delay='0.01ms', jitter='5ms')
		
		#smallest jitter
		self.addLink(s3, s5, bw=10, delay='5ms', jitter='0.01ms')

		#least loss
		self.addLink(s3, s6, bw=10, delay='5ms', jitter=5)


		#join up switches
		self.addLink(s2, s4, bw=10, delay='0.01ms', jitter='0.01ms')
		self.addLink(s2, s5, bw=10, delay='0.01ms', jitter='0.01ms')
		self.addLink(s2, s6, bw=10, delay='0.01ms', jitter='0.01ms')

