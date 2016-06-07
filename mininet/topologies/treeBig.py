#!/usr/bin/python

from mininet.topo import Topo
from mininet.topolib import TreeTopo
from mininet.net import Mininet

class treeBig(TreeTopo):
    def build(self):
    	super(treeBig, self).build(depth=3, fanout=3) 
