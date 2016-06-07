package net.floodlightcontroller.fyp;

import java.util.LinkedList;
import java.util.List;
import org.projectfloodlight.openflow.types.DatapathId;

public class Line {
	List<DatapathId> sw;
	Line(DatapathId sw1,DatapathId sw2){
		this.sw =new LinkedList<>();
		sw.add(sw1);
		sw.add(sw2);
	}
	
	@Override
	public boolean equals(Object obj) {
		if(obj.getClass()!=Line.class) return false;
		Line l = (Line)obj;
		
		return (sw.get(0).equals(l.sw.get(0)) && sw.get(1).equals(l.sw.get(1))) || 
			   (sw.get(0).equals(l.sw.get(1)) && sw.get(1).equals(l.sw.get(0)));
	}
	
	@Override
	public String toString() {
		return sw.get(0)+"-"+sw.get(1);
	}
}