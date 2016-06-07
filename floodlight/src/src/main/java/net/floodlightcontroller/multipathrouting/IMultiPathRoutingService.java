package net.floodlightcontroller.multipathrouting;

import net.floodlightcontroller.routing.Route;

import org.projectfloodlight.openflow.types.DatapathId;
import org.projectfloodlight.openflow.types.OFPort;

import net.floodlightcontroller.core.module.IFloodlightService;

public interface IMultiPathRoutingService extends IFloodlightService  {
    public void modifyLinkCost(DatapathId srcDpid,DatapathId dstDpid,double cost);
    public Route getRoute(DatapathId srcDpid,OFPort srcPort,DatapathId dstDpid,OFPort dstPort);
}
