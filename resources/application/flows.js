//pushes flow definitions to the controller using source and destination mac address
function install_flows_shortest_path(){
	var fw = cy.elements('.device, .switch, .link').floydWarshall();
	var hosts = cy.nodes('.device');

	for(var i=0; i<hosts.length; i++){
		for(var j=0; j<hosts.length; j++){
			if(i==j) continue;
			
			var path = fw.path(hosts[i], hosts[j]);
			//first element in array is host[i]
			//last element in array is host[j]
			//link edges are included between nodes
			for(var p=2; p<path.length-1; p+=2){
				console.log(p);
				if(path[p].hasClass('switch')){
				      var name= next_default_flow_name();
				      var dpid = path[p].id();
				      var matches = {
				      	'eth_dst':path[path.length-1].id(),
				      	'eth_src':path[0].id()
				      };
				      var action = "output="+(path[p+1].source()==path[p]?path[p+1].data('source-port'):path[p+1].data('dst-port'));
				      var definition={'name': name, 'switch':dpid , 'actions':action, 'matches':matches};
				      push_flow(definition, false);
				}
			}

		}	                                                        
	}	
}

//Allow ARP broadcast messages to be flooded through the network 
function flood_arp(){
  cy.nodes('.switch').each(function(i, ele){
      var dpid = ele.data('id');
      var name = next_default_flow_name();
      var matches = {'eth_dst':'FF:FF:FF:FF:FF:FF', 'eth_type':"0x0806"};
      var action = "output=flood";
      var definition={'name': name, 'switch':dpid , 'actions':action, 'matches':matches};
      push_flow(definition, false);
  });
}

var matchFields = {
  "in_port":{
    "val_type":"Number",
    "hasPreReq":false,
    "preset":false,
    "label":"Receiving Port"
  },
  "eth_type":{    
    "val_type":"Number",
    "hasPreReq":false,
    "preset":true,
    "values":{  //reference values: http://standards-oui.ieee.org/ethertype/eth.txt
      "IPv4":"0x0800",
      "ARP":"0x0806",
      "IPv6":"0x86DD",
      "FcOE":"0x8906",
      "LLDP":"0x88CC"
    },
    "label":"EtherType"
  },
  "eth_src":{
    "val_type":"MAC Address",
    "hasPreReq":false,
    "preset":false,
    "label":"Ethernet Source Address"
  },
  "eth_dst":{
    "val_type":"MAC Address",
    "hasPreReq":false,
    "preset":false,
    "label":"Ethernet Destination Address"
  },
  "eth_vlan_vid":{
    "val_type":"Number",
    "hasPreReq":false,
    "preset":false,
    "label":"VLAN number"
  },
  "ip_proto":{
    "val_type":"Number",
    "hasPreReq":true,
    "preReq":{
      "eth_type":["0x0800","0x86DD"]
    },
    "preset":true,
    "values":{
      "TCP":"0x06",
      "UDP":"0x11",
      "SCTP":"0x84",
      "ICMP":"0x01",
      "IGMP":"0x02",
      "RDP":"0x1B"
    },
    "label":"IP Protocol"
  },
  "ip_tos":{
    "val_type":"Number",
    "hasPreReq":true,
    "preReq":{
      "eth_type":["0x0800","0x86DD"]
    },
    "preset":false,
    "label":"IP TOS"
  },
  "ipv4_src":{
    "val_type":"IP",
    "hasPreReq":true,
    "preReq":{
      "eth_type":["0x0800"]
    },
    "preset":false,
    "label":"IPv4 Source"
  },
  "ipv4_dst":{
    "val_type":"IP",
    "hasPreReq":true,
    "preReq":{
      "eth_type":["0x0800"]
    },
    "preset":false,
    "label":"IPv4 Destination"
  },
  "tp_src":{
      "val_type":"Number",
      "hasPreReq":true,
      "preReq":{
        "ip_proto":["0x06","0x11", "0x84"]
      },
      "preset":false,
      "label":"TP Source"
  },
  "tp_dst":{
      "val_type":"Number",
      "hasPreReq":true,
      "preReq":{
        "ip_proto":["0x06","0x11", "0x84"]
      },
      "preset":false,
      "label":"TP Destination"
  },
  "udp_src":{
      "val_type":"Number",
      "hasPreReq":true,
      "preReq":{
        "ip_proto":["0x11"]
      },
      "preset":false,
      "label":"UDP Source Port"
  },
  "udp_dst":{
      "val_type":"Number",
      "hasPreReq":true,
      "preReq":{
        "ip_proto":["0x11"]
      },
      "preset":false,
      "label":"UDP Destination Port"
  },
  "tcp_src":{
      "val_type":"Number",
      "hasPreReq":true,
      "preReq":{
        "ip_proto":["0x06"]
      },
      "preset":false,
      "label":"TCP Source Port"
  },
  "tcp_dst":{
      "val_type":"Number",
      "hasPreReq":true,
      "preReq":{
        "ip_proto":["0x06"]
      },
      "preset":false,
      "label":"TCP Destination Port"
  },
};

//helper function for enabling/disabling match filter inputs
function enable_input(obj){
  var input = obj.siblings();
  input.trigger('change');
  obj.is(":checked")? input.removeAttr("disabled"):input.attr("disabled", true);
  obj.parent().attr('selected', obj.is(":checked"));
  process_matches_prereq();
}

//handles changes of matches form - updates selectable fields according to prerequisite completion 
function process_matches_prereq(){
  $('#add_flow_match_form').children().each(function(){
      var k = $(this).attr("match_key");
      //check if pre-requisite field is satisfied
      if(matchFields[k].hasPreReq){
        var req_key = Object.keys(matchFields[k].preReq)[0];
        
        var req = $(this).siblings('[match_key="'+req_key+'"][selected]');
        
        if(req.length <= 0 || matchFields[k].preReq[req_key].indexOf($(req[0]).attr('value'))==-1 ) {//  not satisfied
          $(this).attr('selected', false);
          $(this).children('input.checkbox-input-toggle').attr('checked', false);
          $(this).children('input.checkbox-input-toggle').attr('disabled', true);
        }else {
          $(this).children('input.checkbox-input-toggle').removeAttr('disabled');
        }        
      }
  });
}


//takes a target div and returns an associative array with the match values
function extractMatchesFromFilter(targetID){
  var vals = {};
  $('#'+targetID).children('.match_field[selected]').each(function(){
    vals[$(this).attr('match_key')] = $(this).attr('value');
  });

  return vals;
}

//used to generate names using a timestamp 
//avoids duplicate names if called quickly in succession
var last_stamp = 0;
function next_default_flow_name(){
  var curr = Date.now();
  if(curr<=last_stamp){
    curr = last_stamp+1;
  }
  return last_stamp = curr;
}

//Extracts the values from the add flow dialog/modal and pushes to the controller
function addNewFlow(){
    var name= $('#add_flow_name').val();
    var dpid = $('#add_flow_dpid_select').val();
    var matches = extractMatchesFromFilter('add_flow_match_form');
    var action = "output="+$('#add_flow_output_action').val();
    var idle = $('#add_flow_idle_timeout').val();
    var hard = $('#add_flow_hard_timeout').val();
    var priority = $('#add_flow_priority').val();

    if($('#add_flow_queue').val()!='') action = "set_queue=" + $('#add_flow_queue').val() +","+action;

    var definition={};

    if(name=="") next_default_flow_name();

    if(idle!='') definition['idle_timeout']=idle;
    if(hard!='') definition['hard_timeout']=hard;
    if(priority!='') definition['priority']=priority;         

    definition = $.extend(definition, {'name': name, 'switch':dpid , 'actions':action, 'matches':matches});

    push_flow(definition, true);
}

//pushes a flow rule/definition to the controller
function push_flow(definition, do_alert = false){
  keys = Object.keys(definition);
  for(var i=0; i<keys.length; i++){
    try{
      definition[keys[i]] = definition[keys[i]].replace(/0x0x/, "0x0"); //controller returning 0x0x instead of just 0x for some reason  
    } catch (e){ continue; }
  }
  console.log(definition);
  
  $.post( site_url+'/dashboard/flows/add', definition, function( data ) {if(do_alert) alert(data); else console.log(data);});     
}

//Removes a flow definition from the controller according to the flow name
function remove_flow(flow_name){
  $.post( site_url+'/dashboard/flows/remove', {'name':flow_name}, function( data ) {alert(data);});     
}


//Adds switches and match elements to the add flow dialog/modal
function populate_add_flow(data=undefined){
  if(data===undefined){
    $("#add_flow_dpid_select").html('');
    for(var i=0; i< topology.data.nodes.length; i++)                  
        if(cy.nodes()[i].hasClass("switch") && cy.nodes()[i].data('id')!="") $('#add_flow_dpid_select').append('<option>'+cy.nodes()[i].data('id')+'</option>');
    createMatchFilter("add_flow_match_form");
    $('#add_flow_name').val('');
    $('#add_flow_output_action').val('');
    $('#add_flow_queue').val('');
    $('#add_flow_idle_timeout').val('');
    $('#add_flow_hard_timeout').val('');
    $('#add_flow_priority').val('');

  }else {
    $("#add_flow_dpid_select").html('');
    for(var i=0; i< topology.data.nodes.length; i++)                  
      if(cy.nodes()[i].hasClass("switch") && cy.nodes()[i].data('id')!="") {
        var s = "";
        if(cy.nodes()[i].id()==data.source) s = "selected='selected'";
        $('#add_flow_dpid_select').append('<option '+s+'>'+cy.nodes()[i].data('id')+'</option>');
      }
    createMatchFilter("add_flow_match_form");
    populate_match_filter("add_flow_match_form", data.flow.match);
    $('#add_flow_name').val(data.flow.name);

    if(typeof data.flow.instructions.instruction_apply_actions.actions != undefined){
        var matches = /output=(\S+)/.exec(data.flow.instructions.instruction_apply_actions.actions);
        $('#add_flow_output_action').val(matches.length==2?matches[1]:'');

        matches = /set_queue=(\S+),.*/.exec(data.flow.instructions.instruction_apply_actions.actions);
        if(matches != null)$('#add_flow_queue').val(matches.length==2?matches[1]:'');
    }

    $('#add_flow_idle_timeout').val(data.flow.idleTimeoutSec);
    $('#add_flow_hard_timeout').val(data.flow.hardTimeoutSec);
    $('#add_flow_priority').val(data.flow.priority);
  }
    
}

//populates the target div with match fields
function createMatchFilter(elemID){
   $("#"+elemID).html('');
    var keys = Object.keys(matchFields);
    for(var i=0; i< keys.length; i++){
        var data = matchFields[keys[i]];
        var disabled = data.hasPreReq? "disabled":"";
        var input;
        if(data.preset){
            input = '<select onchange="update_value($(this))" disabled>';
            var vals = Object.keys(data.values);
            for(var j=0;j<vals.length; j++)
              input += '<option value="'+data.values[vals[j]]+'">'+vals[j]+'</option>';
            input += '</select>';
        }else input = '<input type="text" placeholder="'+data.val_type+'"  oninput="update_value($(this))" disabled/>';


        $("#"+elemID).append('<div class="match_field" match_key="'+keys[i]+'" value=""><input type="checkbox" class="checkbox-input-toggle" onclick="enable_input($(this))" '+disabled+'/><label>'+data.label+'</label>'+input+'</div>');
    }
}

function populate_match_filter(elemID, data){
    var parts = $('#'+elemID+' > div.match_field');
    console.log(data);
    var match_fields = Object.keys(data);
    parts.each(function(i, ele){
        // console.log(match_fields);
        // console.log($(ele).attr('match_key'));
        
        // if(!($(ele).attr('match_key') in match_fields)) return;
        if($.inArray($(ele).attr('match_key'), match_fields)==-1)return;        
        
        var inputs = $(this).children('input');
        $(inputs[0]).prop('checked', true);
        enable_input($(inputs[0]));

        if(typeof inputs[1] != 'undefined'){
          $(inputs[1]).val(data[$(ele).attr('match_key')]);
          $(inputs[1]).trigger('input');
        } else {
          inputs = $(this).children('select');
          var opts = $(inputs[0]).children('option');
          var val, altVal;
          val = altVal =  data[$(ele).attr('match_key')];
          altVal = altVal.replace(/0x0x/, "0x0"); //adjust ethertype values
          // console.log(val);
          for(var i=0; i<opts.length; i++){
            if(opts[i].value == val || opts[i].value == altVal){
             $(opts[i]).prop('selected', true);
            }
          }

          $(inputs[0]).trigger('change');
        }
    });
}

//updates the match filter element
function update_value(obj){
    obj.parent().attr('value', obj.val());
    process_matches_prereq();
}

function edit_flow(flow_name){  
  var target;
  cy.edges('.flow').each(function(i, ele){
    if(ele.data('flow').name==flow_name) target = ele;
  });

  populate_add_flow(target.data());
  $('.nav-tabs li:eq(2)').click();
  $('#addRuleButton').click();
}
