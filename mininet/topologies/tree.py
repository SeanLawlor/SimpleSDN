#!/usr/bin/python

from mininet.topo import Topo
from mininet.topolib import TreeTopo
from mininet.net import Mininet

class tree(TreeTopo):
    def build(self):
    	super(tree, self).build(depth=3) 
