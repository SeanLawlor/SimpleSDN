package net.floodlightcontroller.fyp;

import java.util.HashMap;
import java.util.Map;
import java.util.Map.Entry;
import java.util.Set;

import org.projectfloodlight.openflow.types.DatapathId;
import org.restlet.resource.Get;
import org.restlet.resource.ServerResource;

public class FypRestServer extends ServerResource {

	@Get("json")
	public Map setForwardingOption(){
		String param = (String) getRequestAttributes().get("option");
		Map<String, String> ret = new HashMap<>();
		
		InfoCollector sc;
		
		try{
			sc = InfoCollector.getInstance();;
		}catch(Exception e){
			ret.put("status", "error");
			ret.put("error_message", "failed to load stats collector instance");
			return ret;
		}
		
		switch (param) {
		case "jitter":
			Fyp.autoForward = true;
			sc.forwardingPriority = forwardingMethod.JITTER;
			break;
		case "delay":
			Fyp.autoForward = true;
			sc.forwardingPriority = forwardingMethod.DELAY;
			break;
		case "shortest_path":
			Fyp.autoForward = true;
			sc.forwardingPriority = forwardingMethod.SHORTEST_PATH;
			break;
		case "delay_variation":
			Fyp.autoForward = true;
			sc.forwardingPriority = forwardingMethod.DELAY_VARIATION;
			break;
		case "least_loss":
			Fyp.autoForward = true;
			sc.forwardingPriority = forwardingMethod.RELIABILITY;
			break;
		case "none":
			Fyp.autoForward = false;
			break;
		case "status":
			ret.put("auto_forward", Fyp.autoForward?"enabled":"disabled");
			ret.put("forwarding_by", sc.forwardingPriority.name());
			break;
		case "queues":
			Map<String, Object> qs = new HashMap<>();
			for(Entry<DatapathId, Set<Queue>> e:sc.queues.entrySet())
				qs.put(e.getKey().toString(), e.getValue().toArray());
			return qs;
		default:
			ret.put("status", "error");
			ret.put("error_message", "unknown parameter \""+param+"\"");
			return ret;
		}
		
		ret.put("auto_forward", Fyp.autoForward?"enabled":"disabled");
		ret.put("forwarding_by", sc.forwardingPriority.name());
		ret.put("status", "success");

		return ret;
	}
	
}
