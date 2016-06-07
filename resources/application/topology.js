var topology = {
    data: window.graph_data,
    update:true,
    getUpdates:function(){
        if(!topology.update) return; //No need to issue request if update is false
        
        $.get(site_url+'/dashboard/graph', 
          function(data){
            //topology.update may have changed in the time between receiving the data and issuing the request
            //If the request took a long time (~ more than a couple hundred ms) may negatively affect user experience
            if(!topology.update) return;    
            try{
                topology.data = JSON.parse(data); 
            }catch(err){
              alert(data);
            }
            topology.updateGraph();
          });
    },
    init: function(){
        $('#topology-pane').cytoscape({
            
            //Styling for the graph
            style: cytoscape.stylesheet()
              // .selector('node[type="switch"]')
              .selector('node.switch')
                .style({
                  'content': 'data(id)',
                  'shape':'roundrectangle',
                  'background-color': 'powderblue',
              })
              // .selector('node[type="device"]')
              .selector('node.device')
                .style({
                  'content': function(ele){return ele.data('ip')+"\n"+ele.data('mac');},
                  'shape':'rectangle',
                  'background-color': 'whitesmoke',
              })
              .selector('node')
                .style({
                  'text-wrap': 'wrap',
                  'text-valign': 'center',
                  'text-halign': 'center',
                  'width':'200px',
                  'height':'40px',
                  'border-width':'1px',
                  'border-color':'silver'
              })
              // .selector('edge[type="flow"]')
              .selector('edge.flow')
                .style({
                  'line-color':'green',
                  'target-arrow-shape':'triangle-backcurve',
                  'target-arrow-color':'green',
                  'z-index':'-1',
                  'opacity':'0.85'
              })
              // .selector('edge[type="link"]')
              .selector('edge.link')
                .style({
                  'content':'data(lineSpeed)',
                  'line-color':'lightslategrey',
                  'color':'lightslategrey',
                  'edge-text-rotation':'autorotate'
              })
              .selector('.highlight')
                .style({
                  'line-color':'orange',
                  'target-arrow-color':'orange',
                  'target-arrow-shape':'triangle-backcurve'
              })
              .selector('edge')
                .style({
                  'text-background-color':'white',
                  'text-background-opacity':0.95,
                  'control-point-step-size': 0,
                  'curve-style': 'bezier'
              }) .selector('.expanded')
                .style({
                  'control-point-step-size': 50,
              }).selector('.collapse')
                .style({
                  'control-point-step-size': 0,
              }),

            //fix zoom levels to avoid user "losing" graph
            minZoom: .3,
            maxZoom: 9,
            wheelSensitivity: .2,

            //Force directed layout (COmpound Spring Embedder )
            layout:{
                name:'cose',
                fit:false,      //don't try fit to screen - to accommodate large networks
                refresh:5000,   //low redraw rate to increase performance
                componentSpacing:400,

                //High level of repulsion with a guided edge length, to get a good spread of nodes
                nodeRepulsion: 400000,  
                nodeOverlap:10,
                idealEdgeLength: 125,
                gravity: 10,
                
                //Large number of iterations with low cool-off rate to get better layout
      	  			numIter: 10000,   
      	  			initialTemp : 1000,
        				coolingFactor  : 0.999,
        				minTemp : 0.01,
            },

            //graph details from server
            elements: topology.data,

            ready: function(){
              	window.cy = this;
                window.cy.edges().addClass("collapse");
              	
                //code to handle right-click expand/collapse on nodes
                window.cy.on('cxttapstart', function(evt){
      		    		
                  //get node id's for nodes attached to target edge
                  var src_id = evt.cyTarget.data('source');
      		    		var tar_id = evt.cyTarget.data('target');

                  //get all edges & node objects between these 2 nodes
                  // var targetEdges = cy.edges('[type="flow"][source="'+src_id+'"][target="'+tar_id+'"], [type="flow"][target="'+src_id+'"][source="'+tar_id+'"]');
                  var targetEdges = cy.edges('.flow[source="'+src_id+'"][target="'+tar_id+'"], .flow[target="'+src_id+'"][source="'+tar_id+'"]');
                  var targetNodes = targetEdges.connectedNodes();


                  /*
                    Nodes keep an array of other nodes that edges should be expanded to, 
                    so when edges are added to the graph they can be expanded or collapsed.
                    Classes "expanded" and "collapse" have styling that handle this (control point step size)
                  */
                  
                  //add empty array if data not yet
                  if(typeof targetNodes[0].data("expandEdgesTo") ==="undefined") targetNodes[0].data("expandEdgesTo", []);
                  if(typeof targetNodes[1].data("expandEdgesTo") ==="undefined") targetNodes[1].data("expandEdgesTo", []);
                  

                  //collapse edges if expanded
                  if(evt.cyTarget.hasClass("expanded")){
      		    			targetEdges.removeClass("expanded");
                    targetEdges.addClass("collapse");
                    
                    //remove nodes from each other's "expandEdgesTo" list
                    var n1 = targetNodes[0].data("expandEdgesTo");
                    var n2 = targetNodes[1].data("expandEdgesTo");

                    n1.splice(n1.indexOf(targetNodes[1]), 1);
                    n2.splice(n2.indexOf(targetNodes[0]), 1);
                    
      						}
                  //otherwise expand edges
                  else{
                    targetEdges.removeClass("collapse");
        						targetEdges.addClass("expanded");

                    //Add each node to the other's "expandEdgesTo"
                    var n1 = targetNodes[0].data("expandEdgesTo");
                    var n2 = targetNodes[1].data("expandEdgesTo");
                    if (n1.indexOf(targetNodes[1])== -1) n1.push(targetNodes[1]);
                    if (n2.indexOf(targetNodes[0])== -1) n2.push(targetNodes[0]);
        					}
    		    	});
              topology.setup_popovers();

              //reposition view port -- otherwise graph may be off screen
              window.cy.center();
              window.cy.fit();
            }
        })
    },
    
    stop: function(){ //remove graph
        $('#topology-pane').children().remove();
    },

    //makes changes to nodes/edges & redraws graph
    updateGraph:function(){ 
        
        //make batch changes for better performance (prevents multiple redraws/re-calculations while changes are being made)
        cy.startBatch();
        
        var node_count=cy.nodes().size();
        cy.edges().remove(); //remove edges to prevent duplicates (only nodes have ID's included)
        cy.add(topology.data);
        
        if(cy.nodes().size()>node_count) topology.refresh();  //redraw if new nodes added
        cy.nodes("[[degree<=0]]").remove()  //remove nodes that don't have any edges (hosts may remain for a period even if a link goes down)

        //expand or collapse edges as required
        for(var i=0; i<cy.nodes().length; i++){
          var targetNode = cy.nodes()[i];
          if(typeof targetNode.data("expandEdgesTo")==="undefined") targetNode.data("expandEdgesTo", []);
          for(var j=0; j< targetNode.data("expandEdgesTo").length;j++){
            cy.edges().removeClass("collapse");
            targetNode.edgesTo(targetNode.data("expandEdgesTo")[j]).addClass("expanded");
          }
        }
          
        topology.setup_popovers();
        cy.endBatch(); //Trigger batch redraw
    },

    toggleLock:function(){
        topology.update = !topology.update;

        //updates lock button
        if(topology.update) $('#topology-locker > span').removeClass('glyphicon-transfer').addClass('glyphicon-lock')
        else $('#topology-locker > span').removeClass('glyphicon-lock').addClass('glyphicon-transfer')
    },

    refresh:function (){
        topology.stop();
        topology.init();
    },

    setup_popovers:function(){
      
      var elements = cy.elements();
      elements.difference(cy.edges('.collapse'));

      cy.on('tap', function(evt){
      
        $('#graph-info-popover').html(generatePopoverContent(evt.cyTarget));
        
        cy.edges('.flow').removeClass('highlight');
        if(evt.cyTarget.hasClass('flow')) highlight_matching_flows(evt.cyTarget);
        else cy.edges('.flow').removeClass('highlight');
      });
    }

}

//highlights flows with the same match fields to the target flow
function highlight_matching_flows(flow){
  var matches = flow.data('flow')['match'];
  var flows = cy.edges('.flow');
  
  flow.addClass('highlight');
  
  for(var i=0; i<flows.length; i++){
    var curr_matches = flows[i].data('flow')['match'];
    
    var k1 = Object.keys(matches).sort();
    var k2 = Object.keys(curr_matches).sort();

    //ignore in_port match field as this would change per switch for a flow
    if(k1.indexOf('in_port') != -1) k1.splice(k1.indexOf('in_port'), 1);
    if(k2.indexOf('in_port') != -1) k2.splice(k2.indexOf('in_port'), 1);

    //check if matches have exact same keys - continue to next if fail
    if(k1.length != k2.length  || !k1.every(function(ele, index){ return k2[index]==ele;})) continue;

    //check if match values for each field are equal, highlight if success
    if(k1.every(function(k, i){ return curr_matches[k]===matches[k];}))flows[i].addClass('highlight'); 
  }
}

//generates the popovers that appear in the bottom right when an element is selected
function generatePopoverContent(obj){
  if(obj===cy) {	// remove popover if blank-space/background is clicked
    $('#graph-info-popover').html('');
    return;
  }

  var data;

  // associative-array/object created which is used to dynamically add tabs to the popover (new tabs or fields can be created by simply extending the array)
  if(obj.hasClass('device'))        data = generatePopoverData_dev(obj);
  else if(obj.hasClass('switch'))   data = generatePopoverData_switch(obj);
  else if(obj.hasClass('link'))     data = generatePopoverData_link(obj);
  else if(obj.hasClass('flow'))     data = generatePopoverData_flow(obj);
  else return;
  // the returned object is indexed by [Tab Name][field]:field_contents
  
  var tabs = Object.keys(data);
  var html='';

  //create the popover html for each tab
  html += '<div id="popover-content" class="tab-content panel panel-default">';
  
  for(var i=0; i<tabs.length; i++){
    html += '<table id="popover-content'+i+'" class="'+(i==0?'active':'')+' tab-pane table table-condensed table-striped"><tbody>';
    var keys = Object.keys(data[tabs[i]]);
    for(var j=0; j<keys.length; j++){
        html+= '<tr>';
        html+= '<th>'+keys[j]+'</th>';
        html+= '<td>'+data[tabs[i]][keys[j]]+'</td>';            
        html+= '</tr>';   
    }
    html += '</tbody></table>'
  }
  
  html += '</div>';
  html += '<ul class="nav nav-pills nav-justified" id="popover-nav">';
  for(var i=0; i<tabs.length; i++){
    html += '<li role="presentation" '+(i==0?'class="active"':'')+'><a data-toggle="pill" href="#popover-content'+i+'">'+tabs[i]+'</a></li>'
  }
  html += '</ul>'

  //insert the generated HTML into the popover placeholder
  $('#graph-info-popover').html(html);
}

// popover data for hosts 
function generatePopoverData_dev(node){
  var ret ={};
  ret['Overview'] = {};
  ret['Overview']['Graph ID'] = node.data('id');
  ret['Overview']['IP'] = node.data('ip');
  ret['Overview']['MAC'] = node.data('mac');
  return ret;
}

// popover data for switches
function generatePopoverData_switch(node){
  var ret ={};
  ret['Overview'] = {};
  ret['Overview']['Graph ID'] = node.data('id');
  ret['Overview']['DPID'] = node.data('id'); 
  return ret;
}

// popover data for links
function generatePopoverData_link(edge){
  var ret ={};
  ret['Overview'] = {};
  ret['Overview']['Graph ID'] = edge.data('id');
  ret['Overview']['Source'] = edge.data('source');
  ret['Overview']['Source Port'] = edge.data('source-port');

  ret['Overview']['Dest.'] = edge.data('target');
  ret['Overview']['Dest. Port'] = edge.data('dst-port');

  ret['Overview']['Line Speed'] = edge.data('lineSpeed');
  
   
  return ret; 
}

// popover data for flows
function generatePopoverData_flow(edge){
  var ret ={};
  ret['Overview'] = {};
  ret['Overview']['Graph ID'] = edge.data('id');
  ret['Overview']['Name'] = edge.data('flow')['name'];
  ret['Overview']['Source'] = edge.data('source');
  ret['Overview']['Dest.'] = edge.data('target');
  
  //Ports not defined for flows 
  //pull port info from link
  // var link = cy.edges('[type="link"][source="'+edge.data('source')+'"][target="'+edge.data('target')+'"]');
  var link = cy.edges('.link[source="'+edge.data('source')+'"][target="'+edge.data('target')+'"]');

  //flow might be between host & switch or switch & switch -> we can get src&dest ports of flow in the latter case
  if(link.length > 0){
    ret['Overview']['Source Port'] = link[0].data('source-port');
    ret['Overview']['Dest. Port'] = link[0].data('dst-port');
  }else {
    // var link = cy.edges('[type="link"][target="'+edge.data('source')+'"][source="'+edge.data('target')+'"]');
    var link = cy.edges('.link[target="'+edge.data('source')+'"][source="'+edge.data('target')+'"]');
    ret['Overview']['Source Port'] = link[0].data('dst-port');    
    ret['Overview']['Dest. Port'] = link[0].data('source-port');
  }

  if(typeof edge.data('flow').instructions.instruction_apply_actions.actions != undefined){
        matches = /set_queue=(\S+),.*/.exec(edge.data('flow').instructions.instruction_apply_actions.actions);
        if(matches != null)  ret['Overview']['Queue'] = matches[1];
  }
  
  
  ret['Stats'] = {};
  ret['Stats']['Packets'] = edge.data('flow').packetCount;
  
  ret['Match Details'] = {};
  var matchDetails = edge.data('flow').match;
  var matchKeys = Object.keys(matchDetails);
  for(var i=0; i<matchKeys.length; i++){
    ret['Match Details'][matchKeys[i]]=matchDetails[matchKeys[i]];
  }

  //create buttons that allow a flow to be added/edited/removed
  if(typeof edge.data('flow')['name'] != 'undefined'){ //allow add if no-name/not static
    ret['Edit'] = {};
    ret['Edit']['Delete'] = '<button type="button" class="btn btn-default" aria-label="Left Align" onclick="remove_flow(\''+edge.data('flow')['name']+'\')")><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>';
    ret['Edit']['Edit'] =   '<button type="button" class="btn btn-default" aria-label="Left Align" onclick="edit_flow(\''+edge.data('flow')['name']+'\')")><span class="glyphicon glyphicon-wrench" aria-hidden="true"></span></button>';
  }else{	//allow flow to be edited & removed if named/static
    ret['Edit'] = {};
    var match = edge.data('flow')['match'];
    var instructions = edge.data('flow')['instructions'];
    var name = next_default_flow_name();
    var dpid = edge.data('source');
    popoverFlow = {'name':name, 'switch':dpid};
    popoverFlow = $.extend(popoverFlow, match, instructions);
    ret['Edit']['Add'] = '<button type="button" class="btn btn-default" aria-label="Left Align" onclick="addPopoverFlow()")><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></button>';
  }

  return ret;
}

var popoverFlow;
function addPopoverFlow(){
  push_flow(popoverFlow, true);
}