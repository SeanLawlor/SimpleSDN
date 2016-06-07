#!/usr/bin/python

from mininet.topo import Topo
from mininet.net import Mininet

class minimal(Topo):
    def build(self):
        switch = self.addSwitch('s1')
        host1 = self.addHost('h1')
        host2 = self.addHost('h2')
        self.addLink(host1, switch)
        self.addLink(host2, switch)
