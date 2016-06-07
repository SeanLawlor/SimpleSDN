package net.floodlightcontroller.fyp;

import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.ObjectInputStream;
import java.io.ObjectOutputStream;
import java.util.HashMap;
import java.util.Iterator;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;
import java.util.Map.Entry;
import java.util.Set;

import net.floodlightcontroller.core.IOFSwitch;
import net.floodlightcontroller.core.internal.IOFSwitchService;
import net.floodlightcontroller.multipathrouting.IMultiPathRoutingService;
import net.floodlightcontroller.packet.Data;
import net.floodlightcontroller.packet.Ethernet;
import net.floodlightcontroller.packet.IPacket;
import net.floodlightcontroller.packet.IPv4;
import net.floodlightcontroller.packet.PacketParsingException;
import net.floodlightcontroller.routing.Link;
import net.floodlightcontroller.topology.ITopologyService;
import net.floodlightcontroller.util.FlowModUtils;

import org.projectfloodlight.openflow.protocol.OFEchoReply;
import org.projectfloodlight.openflow.protocol.OFEchoRequest;
import org.projectfloodlight.openflow.protocol.OFFlowAdd;
import org.projectfloodlight.openflow.protocol.OFFlowModify;
import org.projectfloodlight.openflow.protocol.OFMessage;
import org.projectfloodlight.openflow.protocol.OFPacketIn;
import org.projectfloodlight.openflow.protocol.OFPacketOut;
import org.projectfloodlight.openflow.protocol.OFType;
import org.projectfloodlight.openflow.protocol.action.OFAction;
import org.projectfloodlight.openflow.protocol.match.Match;
import org.projectfloodlight.openflow.protocol.match.MatchField;
import org.projectfloodlight.openflow.types.DatapathId;
import org.projectfloodlight.openflow.types.EthType;
import org.projectfloodlight.openflow.types.IPv4Address;
import org.projectfloodlight.openflow.types.IpProtocol;
import org.projectfloodlight.openflow.types.MacAddress;
import org.projectfloodlight.openflow.types.OFPort;

/*
 *	Sends packets across the network in order to estimate network characteristics
 *	Updates the costs of links for use in routing decisions
 */
public class InfoCollector implements Runnable{
	
	private static MacAddress PKT_MAC = MacAddress.of(0);
	private static IPv4Address PKT_IP = IPv4Address.of("0.0.0.0");
	private static int PKT_INJECTION_INTERVAL_MS = 500;
	
	private ITopologyService topologyService;
	private IOFSwitchService switchService;
	private IMultiPathRoutingService multiPathService;
	
	//times taken for a switch to respond to an echo request
	Map<DatapathId, StatsInfo> switchTimes = new HashMap<>();
	
	//estimated times of a link
	Map<Line, StatsInfo> linkTimes = new HashMap<>();
	
	//queues found on a particular switch
	Map<DatapathId, Set<Queue>> queues = new HashMap<>();
	
	forwardingMethod forwardingPriority = forwardingMethod.SHORTEST_PATH;
	
	/*
	 * If poll_switch is true, switches will be sent an echo request in order to estimate controller->switch metrics
	 * If false, link times will be taken as the round-trip time to go from controller->switch->link->switch->controller
	 */
	boolean poll_switch = true;
	
	
	public InfoCollector(IOFSwitchService switchService, ITopologyService topologyService,IMultiPathRoutingService multiPathService) {
		this.topologyService = topologyService;
		this.switchService = switchService;
		this.multiPathService = multiPathService;
		instance = this;
	}
	
	/*
	 * Creates a byte array from supplied Info
	 */
	static byte[] encodeInfo(Info info){
		ByteArrayOutputStream bos = new ByteArrayOutputStream();
		try {
			new ObjectOutputStream(bos).writeObject(info);
		} catch (IOException e) {																																														
			e.printStackTrace();
		}
		return bos.toByteArray();
	}
	
	/*
	 * Attempts to create an Info object from supplied byte array 
	 */
	static Info decodeInfo(byte[] bytes) throws ClassNotFoundException, IOException{
		ByteArrayInputStream bis = new ByteArrayInputStream(bytes);
		return (Info) new ObjectInputStream(bis).readObject();
	}
	
	

	@Override
	public void run() {
		
		while(true){
			
			//get switches each time in case there are updates
			Set<DatapathId> switches = switchService.getAllSwitchDpids();
			
			//add rules so that injected packets are sent back
			for(DatapathId x:switches) installSendToController(x);
				
			
			if(poll_switch) for(DatapathId x:switches) {
				if(!this.switchTimes.containsKey(x)) this.switchTimes.put(x, new StatsInfo());//init if first run
				
				IOFSwitch sw = switchService.getActiveSwitch(x);
				Info info = new Info(x);
				switchTimes.get(x).addPacket(info.id);//keep track of packets that we send (to identify packet loss)
				
				sendEcho(sw);
			}
			
			Map<DatapathId, Set<Link>> all = topologyService.getAllLinks();
			
			for(Set<Link> s:all.values()){
				Iterator<Link> it = s.iterator();
				while(it.hasNext()) {
					Link l = it.next();
					Line line = new Line(l.getDst(), l.getSrc());
					if(getStatsInfo(line)==null) linkTimes.put(line, new StatsInfo());
					
					sendPacket(switchService.getSwitch(l.getSrc()), switchService.getSwitch(l.getDst()));
				}
			}
			
			updateCosts();
			
			
			try {	//sleep before another round
				Thread.sleep(PKT_INJECTION_INTERVAL_MS);
			} catch (InterruptedException e) {
				e.printStackTrace();
			}
		}
	}

	private void updateCosts() {
		for(Entry<Line, StatsInfo> l:linkTimes.entrySet()){
			double cost;
			switch(this.forwardingPriority){
			case JITTER:
				cost = l.getValue().jitter();
				//get jitter for switches
				if(poll_switch){
					double js1 = switchTimes.get(l.getKey().sw.get(0)).jitter();
					double js2 = switchTimes.get(l.getKey().sw.get(1)).jitter();
					
					//try and adjust by taking away the average jitter of the switches
					cost = cost - (js1+js2)/2;
				}
				
				cost = Math.abs(cost); //closer to zero the better
				cost *= cost;	//squaring the jitter increases the cost for high-jitter links (tends to give slightly more consistent routing according to jitter)
				break;
			case DELAY_VARIATION:
				cost = l.getValue().deviation();
				break;
			case DELAY:
				cost = l.getValue().meanDelay();

				break;
			case RELIABILITY:	
				cost = 1.0 - l.getValue().packetLoss(); //smaller packet loss the better (0->1)
				break;
			case SHORTEST_PATH:
			default:
				cost = 1.0;
				break;
			}
//			System.out.println(l);
			multiPathService.modifyLinkCost(l.getKey().sw.get(0), l.getKey().sw.get(1), cost);
		}
	}

	/**
	 *	Adds flow rules to switch so that injected packets are sent back to controller
	 */
	private void installSendToController(DatapathId sw) {
		Match m = switchService.getSwitch(sw).getOFFactory().buildMatch()
		.setExact(MatchField.ETH_TYPE, EthType.IPv4)
		.setExact(MatchField.ETH_DST, PKT_MAC)
		.setExact(MatchField.IP_PROTO, IpProtocol.UDP)
		.setExact(MatchField.IPV4_SRC, PKT_IP)
		.setExact(MatchField.IPV4_DST, PKT_IP)
		.build();

		List<OFAction> a = new LinkedList<>();
		a.add(  switchService.getSwitch(sw).getOFFactory().actions()
				.buildOutput()
				.setPort(OFPort.CONTROLLER)
				.build());
		
		OFFlowModify msg = switchService.getSwitch(sw).getOFFactory()
			.buildFlowModify()
			.setMatch(m)
			.setPriority(Integer.MAX_VALUE)
			.setActions(a)
			.build();
		
		OFFlowAdd tosend = FlowModUtils.toFlowAdd(msg);

		switchService.getSwitch(sw).write(tosend);
	}

	/**
	 * Handles packet_in containing a packet that we injected
	 */
	public void processPacket(DatapathId datapathId, OFMessage m) {
		long currTime = System.nanoTime();
		if(!isTargetPacket(m))return;

		byte[] d = ((OFPacketIn)m).getData();
		Ethernet packet = (Ethernet) new Ethernet().deserialize(d, 0, ((OFPacketIn)m).getTotalLen());

		Info inf = null;
		try {
			IPv4 i = (IPv4) new IPv4().deserialize(packet.getPayload().serialize(), 0, packet.getPayload().serialize().length);
			Data dat = (Data) i.getPayload();
			inf = (net.floodlightcontroller.fyp.Info) new ObjectInputStream(new ByteArrayInputStream(dat.getData())).readObject();
		} catch (PacketParsingException | ClassNotFoundException | IOException e) {
			e.printStackTrace();
		}
		
		long rtt_ns = currTime - inf.timestamp;
		
		double avg_line_rtt;
		double avg_line_delay;
		if(poll_switch){
			double sw1_rtt = switchTimes.get(DatapathId.of(inf.sw1)).meanDelay();
			double sw2_rtt = switchTimes.get(DatapathId.of(inf.sw2)).meanDelay();
			avg_line_rtt = (rtt_ns*2) - (sw1_rtt+sw2_rtt); 
			avg_line_delay = avg_line_rtt/2;
		}else{
			avg_line_delay = rtt_ns/2;
		}

		getStatsInfo(linkFromInfo(inf)).removePacket(inf.id);
		getStatsInfo(linkFromInfo(inf)).addValue(avg_line_delay);
	}
	
	/**
	 * Extracts our line from the specified info
	 */
	private Line linkFromInfo(Info inf) {
		DatapathId dp1 = DatapathId.of(inf.sw1);
		DatapathId dp2 = DatapathId.of(inf.sw2);
		return new Line(dp1, dp2);
	}
	
	/**
	 * Gets the related Stats for specified link
	 */
	StatsInfo getStatsInfo(Line l){
		for(Entry<Line, StatsInfo> e:linkTimes.entrySet())
			if(e.getKey().equals(l)) return e.getValue();
		return null;
	}

	/**
	 * Checks whether the packet_in contains a packet that was sent by us
	 */
	public static boolean isTargetPacket(OFMessage m){
		//attempts to cast message payload to target class - returns false on failure
		try{
			byte[] d = ((OFPacketIn)m).getData();
			
			//try get l2 packet
			Ethernet packet = (Ethernet) new Ethernet().deserialize(d, 0, ((OFPacketIn)m).getTotalLen());
			if(packet==null) throw new Exception();
			
			//try get l3 packet
			IPv4 i = (IPv4) new IPv4().deserialize(packet.getPayload().serialize(), 0, packet.getPayload().serialize().length);
			Data dat = (Data) i.getPayload();
			
			//try parse the data/bytes to our expected class
			@SuppressWarnings("unused")
			Info inf = (net.floodlightcontroller.fyp.Info) new ObjectInputStream(new ByteArrayInputStream(dat.getData())).readObject();
			return true;
		}catch(Exception e){
			return false;
		}
	}
	
	/**
	 * Sends a packet from sw1 to sw2, which is then sent back to the controller
	 */
	void sendPacket(IOFSwitch sw1, IOFSwitch sw2){

		//Create level 2 (Ethernet) packet
		Ethernet l2 = new Ethernet();
		l2.setSourceMACAddress(PKT_MAC);
		l2.setDestinationMACAddress(PKT_MAC);
		l2.setEtherType(EthType.IPv4);
		l2.setPad(true);
		
		//Create level 3 (IPv4) packet
		IPv4 l3 = new IPv4();
		l3.setSourceAddress(PKT_IP);
		l3.setDestinationAddress(PKT_IP);
		l3.setTtl((byte) 64);
		l3.setProtocol(IpProtocol.NONE);
		
		//Create info to be sent
		Info info = new Info(sw1.getId(), sw2.getId());
		
		l3.setPayload(new Data(encodeInfo(info)));
		
		IPacket toSend = l2.setPayload(l3);

		//find the output port to switch2
		Map<DatapathId, Set<Link>> all = topologyService.getAllLinks();
		Iterator<Link> it = all.get(sw1.getId()).iterator();
		OFPort port = null;
		while(it.hasNext()){
			Link link = it.next();
			if(link.getDst().equals(sw2.getId())) {
				port = link.getSrcPort();
				break;
			}
		}
		if(port==null)return;

		LinkedList<OFAction> actions = new LinkedList<OFAction>(); 
		
		//0xffffffff is used to specify that the packet_out is not related to a received packet_in
		actions.add(sw1.getOFFactory().actions().output(port, 0xffffffff)); 
		
		OFPacketOut po = sw1
				.getOFFactory()
				.buildPacketOut()
				.setXid(Long.MAX_VALUE)
				.setData(toSend.serialize()).setActions(actions)
			    .build();

		sw1.write(po);
	}
	
	/**
	 * Checks whether the echo_reply was sent by us / contains required info
	 */
	public static boolean isTargetEcho(OFMessage msg) {
		if(msg.getType()!=OFType.ECHO_REPLY) return false;
		
		OFEchoReply reply = (OFEchoReply) msg;
		try {
			decodeInfo(reply.getData());
		} catch (ClassNotFoundException | IOException e) {
			return false;
		}
		return true;
	}

	/**
	 * Sends echo to target switch
	 */
	void sendEcho(IOFSwitch sw){
		Info info = new Info(sw.getId());
		OFEchoRequest echo = sw.getOFFactory().echoRequest(encodeInfo(info));
		switchTimes.get(sw.getId()).addPacket(info.id);
		sw.write(echo);
	}
	
	/**
	 * Handles echo_reply to one of our requests
	 */
	public void processEchoResponse(DatapathId datapathId, OFMessage msg) {
		long currTime = System.nanoTime();
		if(!isTargetEcho(msg)) return;
		
		OFEchoReply reply = (OFEchoReply) msg;
		Info info;
		try {
			info = decodeInfo(reply.getData());
		} catch (ClassNotFoundException | IOException e) {
			return;
		}
		long time = currTime - info.timestamp;
		
		switchTimes.get(datapathId).removePacket(info.id);//remove from our list of sent messages
		switchTimes.get(datapathId).addValue(time);
	}
	
	
	static InfoCollector instance;
	public static InfoCollector getInstance() throws Exception{
		if(instance==null) throw new Exception("FYP module - Collector not yet initialised");
		else return instance;
	}
}
