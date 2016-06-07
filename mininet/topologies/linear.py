#!/usr/bin/python

from mininet.topo import Topo
from mininet.net import Mininet

class linear(Topo):
    def build(self):
        switch1 = self.addSwitch('s1')
        switch2 = self.addSwitch('s2')
        host1 = self.addHost('h1')
        host2 = self.addHost('h2')
        self.addLink(host1, switch1)
        self.addLink(host2, switch2)
        self.addLink(switch1, switch2)
