package net.floodlightcontroller.fyp;

import java.util.HashSet;
import java.util.Set;
import java.util.concurrent.ConcurrentLinkedQueue;

/**
 * Stores data with regards to link measurements
 */
class StatsInfo{
	
	/*
	 Last N measurements to use in calculations 
	 		-> bigger number = larger time frame taken into account = less chance of large fluctuations, but slow to respond to changes
			-> smaller number = smaller time frame taken into account = greater chance of sharp fluctuations, quicker to respond to changes
	 depends on InfoCollector.PKT_INJECTION_INTERVAL_MS
	 	if InfoCollector.PKT_INJECTION_INTERVAL_MS = 500, ~2 measurements generated per second
	 	MAX_SAVE = 60  ->  calculations apply to last ~30 seconds
	 */
	private static final int MAX_SAVE = 60; 
	
	// Use concurrent queue -> concurrent modification exception may occur otherwise (ex. if adding value while another method is being called) 
	//this arises as Floodlight usually creates many threads to process PACKET_IN messages etc..
	private ConcurrentLinkedQueue<Double> times = new ConcurrentLinkedQueue<>();
	
	//keep track of packets that we send in order to estimate packet loss
	private long packetsSent = 0;
	private Set<Long> awaitingPacketIDs = new HashSet<>();
	
	private double jitter=0;
	private double lastJitterVal =0;
	
	
	//add a packet that was sent
	void addPacket(long id){
		awaitingPacketIDs.add(id);
		packetsSent++;
	}
	
	//remove sent packet when we get it back
	void removePacket(long id){
		awaitingPacketIDs.remove(id);
	}
	
	double packetLoss(){
		double pl = (double)awaitingPacketIDs.size()/(double)packetsSent;
		return (Double.isNaN(pl))?0 : pl;
	}
	
	double packetsLost(){
		return awaitingPacketIDs.size();
	}
	
	/**
	 * Add a measurement value (transit time)
	 */
	void addValue(double avg_line_delay){
		//jitter calculation as done by rtp
		// simple formula  	J(i) = J(i-1) + ( |D(i-1,i)| - J(i-1) )/16 
		// where 			D(i,j) = (Rj - Sj) - (Ri - Si)  (i.e. the difference of delay in 2 packets) 	
		this.jitter += (Math.abs(avg_line_delay - lastJitterVal) - this.jitter)/16;

		times.offer(avg_line_delay);
		if(times.size()>MAX_SAVE) times.poll();
		this.lastJitterVal = avg_line_delay;
	}
	
	/**
	 * Returns the avg delay 
	 */
	double meanDelay(){
		double sum =0;	
		
		//iterate over a clone to prevent errors from concurrent modification
		for(double x: times) sum += x;
		return times.size()==0?0:sum/(double)times.size();
	}
	
	/**
	 * Packet Delay Variation
	 * sqrt of the sum of the squared deviation in delays / Standard deviation of delays
	 * (rather than just the mean so that large deviations have a greater impact on the return value)
	 */
	double deviation(){
		double avg = meanDelay();
		long sum = 0L;
		
		for(double x:times) {
			double diff = avg-x;
			sum += diff*diff;
		}
		return times.size()==0?0:Math.sqrt((double)sum/(double)times.size());
		
	}
	
	/**
	 * Returns the jitter value, calculated using RTP formula
	 */
	double jitter(){
		return this.jitter;
	}
	
	@Override
	public String toString() {
		String mn = String.format("%.2f ms", meanDelay()/(double)1000000);
		String jit = String.format("%.2f ms", jitter()/(double)1000000);
		String pdv = String.format("%.2f ms", deviation()/(double)1000000);
		String pl = String.format("%.2f%%", packetLoss());
		return ",delay:"+mn+" ,jit:"+jit+" ,pdv:"+pdv+" ,pl:"+pl;
	}
}