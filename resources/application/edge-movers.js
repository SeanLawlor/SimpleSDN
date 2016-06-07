/**
  This script is a workaround in order to allow a user to move edges freely.
  Cytoscape.js does not allow edges to be moved freely, they must be attached to nodes.
  Edges must have a source and destination edge at all times.

  Only Flows that have been expanded are allowed to be redirected -> easier to interact with.
*/


//Mouse based redirection of flows done by turning on 'flow edit' mode, 
//  turn off to return to normal network visualisation
//Done due to the constraints of Cytoscape.js & the implementation of this workaround
var flow_edit = false;
function toggleFlowEdit(){
  flow_edit? editOff() : editOn();
}

//Helper function to get distance -> used to calculate the closest node when an edge is released
function distance(a, b){  //euclidian distance
  return Math.sqrt(
        (b.x-a.x)*(b.x-a.x) + 
        (b.y-a.y)*(b.y-a.y)
    );
}


//turn off 'edit' mode
function editOn(){
  if(flow_edit) return;
  else flow_edit=true;  

  //make sure that the topology can't be updated while this mode is on
  if(topology.update) topology.toggleLock();

  //lock the state of switches and hosts so they can'y be moved
  cy.nodes('.switch, .device').lock();

  //get all expanded edges and record the positions of the head of the flows 
  var edges = cy.edges('.flow.expanded');
  var positions = [];
  for(var i=0; i<edges.length; i++){
    positions.push({x:edges[i].rscratch('arrowEndX'), y:edges[i].rscratch('arrowEndY')});
  }

  //remove these edges from the graph 
  edges.remove();
  
  for(var i=0; i<edges.length; i++){
    //insert a small 'mover' node to the positions our flows pointed to 
    // (these are our to-be handle for moving edges between nodes) 
    var n = cy.add(
      { 'group':'nodes', 
        'position':positions[i], 
        'style':{
          'shape':'ellipse',
          'width':5,
          'height':5,
          'background-color':'red'
        }
      }
    );
    
    n.data('target_node', edges[i].target());

    //these nodes can be moved -> a 'free' event is fired when a node is released from mouse
    //This function is run when such an event occurs
    n.on('free', function(evt){
      
      //get the position of the released node and calculate the closest device in the network that it can connect to
      var pos = evt.cyTarget.position();
      var targets = evt.cyTarget.connectedEdges().source().openNeighborhood('.switch, .device');
      
      //find the closest device that flow can connect to
      var smallest_index = 0;
      var smallest_distance = distance(pos, targets[0].position());
      for(var j=0; j<targets.length; j++){
        var d = distance(pos, targets[j].position());
        if(d<smallest_distance){
          smallest_distance = d;
          smallest_index = j;
        }
      }
      
      // snap/move the node to a position on the closest device   (we don't want the edge going to nothing!)
      var tgt = targets[smallest_index];
      var shape = cy.renderer().getNodeShape(tgt);
      var pt = cy.renderer().nodeShapes[shape].intersectLine(tgt.position('x'), tgt.position('y'), tgt.outerWidth(), tgt.outerHeight(), pos.x, pos.y, 0);
      if(pt.length!==2) pt = cy.renderer().nodeShapes[shape].intersectLine(
          tgt.position('x'), 
          tgt.position('y'), 
          tgt.outerWidth(), 
          tgt.outerHeight(), 
          evt.cyTarget.connectedEdges().source().position('x'), 
          evt.cyTarget.connectedEdges().source().position('y'),
          0
      );
      //Set to calculated co-ords
      evt.cyTarget.position({x:pt[0], y:pt[1]}); 
      
      //update target node of the handle
      evt.cyTarget.data('target_node', tgt);

      //find the link between the 2 network devices and find the new output port for the flow
      var e = evt.cyTarget.connectedEdges();
      var link = e.source().edgesWith(tgt).filter('.link');
      var out_port;
      if(e.data('source')==link.data('source')){
        out_port= link.data('source-port');
      }else if(e.data('source')==link.data('target')){
        out_port= link.data('dst-port');
      }
      
      //push new flow with updated output port 
      var definition = $.extend({}, e.data('flow').match, {'name':((typeof e.data('name') === 'undefined')?next_default_flow_name():e.data('name')), 'switch':e.data('source'), 'actions':"output="+out_port});
      push_flow(definition);
    });

    //Add data to new edge and attach to it's handle, then add to graph
    var data = edges[i].data();
    data['target'] = n.id();
    var newEdge = {
      group:"edges",
      classes:"flow expanded",
      data: data
    }
    cy.add(newEdge);
  }  
}



//turn off 'edit' mode
function editOff(){
    if(!flow_edit) return;
    else flow_edit=false;
    
    //Get expanded flows (only these are allowed to be redirected)
    var edges = cy.edges('.flow.expanded');
    
    var mover_nodes = [];
    edges.remove();
    for(var i=0; i<edges.length; i++){
      var mvr_node = edges[i].target();
      var new_tgt = mvr_node.data('target_node');

      var data = edges[i].data();

      data['target'] = new_tgt.id();
            
      var new_edge = {
        group:"edges",
        classes:"flow expanded",
        data: data
      }
      cy.add(new_edge);
      mvr_node.remove();
    }

    cy.nodes('.switch, .device').unlock();

}

