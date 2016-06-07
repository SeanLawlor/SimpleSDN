package net.floodlightcontroller.fyp;

import org.projectfloodlight.openflow.types.DatapathId;
import org.projectfloodlight.openflow.types.OFPort;

public class Queue{
	
	DatapathId switchId;
	public OFPort port;
	public long queueId;
	public long minRate;
	public long maxRate;
	
	public Queue(DatapathId switchId, OFPort port, long queue, long minRate, long maxRate){
		this.switchId = switchId;
		this.port = port;
		this.queueId = queue;
		this.minRate = minRate;
		this.maxRate = maxRate;
	}
}
