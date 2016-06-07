from mininet.topo import Topo

from mininet.net import Mininet

class multiRoute(Topo):

    def build(self):	


    	#Have 2 hosts each attached to a switch 
		s1 = self.addSwitch('s1')
		s2 = self.addSwitch('s2')

		h1 = self.addHost('h1')
		h2 = self.addHost('h2')
		
		self.addLink(s1, h1)
		self.addLink(s2, h2)



		#Have intermediate switches -> different available routes 
		s3 = self.addSwitch('s3')
		s4 = self.addSwitch('s4')
		s5 = self.addSwitch('s5')
		s6 = self.addSwitch('s6')
		s7 = self.addSwitch('s7')

		#directly connect attachment point switches 
		#shortest path (low hops, high delay, high jitter, high loss)
		self.addLink(s1, s7)
		self.addLink(s2, s7)

		#to create alternative path with greater number of hops - add intermediate switch
		#no adverse settings 
		self.addLink(s1, s3)


		#from intermediate switch - add paths with different characteristics
		
		#smallest delay
		self.addLink(s3, s4)
		
		#smallest jitter
		self.addLink(s3, s5)

		#least loss
		self.addLink(s3, s6)


		#join up switches
		self.addLink(s2, s4)
		self.addLink(s2, s5)
		self.addLink(s2, s6)

