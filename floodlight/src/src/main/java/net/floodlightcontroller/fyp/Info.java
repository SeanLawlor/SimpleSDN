package net.floodlightcontroller.fyp;

import java.io.Serializable;

import org.projectfloodlight.openflow.types.DatapathId;

/**
  * Utility class to store timestamp, target switch(es) and packet ID.
  * Serialized and put into packets which are injected into the network. 
  * De-serialized from packets we get back
  */
public class Info implements Serializable{
	private static final long serialVersionUID = -3111382013492450367L;//generated class UID for serialization
	
    private static long id_count=0;
	byte testType;
	long timestamp, sw1, sw2, id;

    //For probing a single switches response time	
	Info(DatapathId sw1){
		testType = 0;
		id=next_id();
		this.sw1 = sw1.getLong();
		timestamp = System.nanoTime();
	}

    //For probing link between 2 switches
	Info(DatapathId sw1, DatapathId sw2){
		testType = 1;
		id=next_id();
		this.sw1 = sw1.getLong();
		this.sw2 = sw2.getLong();
		timestamp = System.nanoTime();
	}
	
	long next_id(){
		return ++id_count;
	}
}
