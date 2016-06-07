package net.floodlightcontroller.multipathrouting.types;

import org.projectfloodlight.openflow.types.DatapathId;
import org.projectfloodlight.openflow.types.OFPort;

public class LinkWithCost{
    protected DatapathId src;
    protected OFPort srcPort;
    protected DatapathId dst;
    protected OFPort dstPort;
    protected double cost;

    public LinkWithCost(DatapathId datapathId,OFPort ofPort,DatapathId datapathId2,OFPort ofPort2,double cost2){
        this.src = datapathId;
        this.srcPort = ofPort;
        this.dst = datapathId2;
        this.dstPort = ofPort2;
        this.cost = cost2;
    }
    
    public DatapathId getSrcDpid(){
        return src;
    }

    public DatapathId getDstDpid(){
        return dst;
    }
    public OFPort getSrcPort(){
        return srcPort;
    }
    public OFPort getDstPort(){
        return dstPort;
    }
    public double getCost(){
        return cost;
    }

    public void setCost(double cost){
        this.cost = cost;
    }
    
    public int hashCode() {
        final int prime = 56;
        int result = 1;
        result = prime * result + (int) (dst.getLong() ^ (dst.getLong() >>> 32));
        result = prime * result + dstPort.getPortNumber();
        result = prime * result + (int) (src.getLong() ^ (src.getLong() >>> 32));
        result = prime * result + srcPort.getPortNumber();
        result = prime * result + (int)cost;
        return result;
    }


    public boolean equals(Object obj) {
        if (this == obj)
            return true;
        if (obj == null)
            return false;
        if (getClass() != obj.getClass())
            return false;
        LinkWithCost other = (LinkWithCost)obj;
        if (dst != other.dst)
            return false;
        if (dstPort != other.dstPort)
            return false;
        if (src != other.src)
            return false;
        if (srcPort != other.srcPort)
            return false;
        if (cost  != other.cost)
            return false;
        return true;
    }


    public LinkWithCost getInverse(){
        return new LinkWithCost(dst,dstPort,src,srcPort,cost);
    }


}
