


 <div class="panel-group form" id="addFlowAccordian">
  <div class="panel panel-default">
    
    <div class="panel-heading" data-toggle="collapse" data-parent="#addFlowAccordian" href="#collapse1">
      <h4 class="panel-title">
        <a>Required</a>
      </h4>
    </div>
    
    <div id="collapse1" class="panel-collapse collapse in">
      <div class="panel-body">
        
        <div class="form-group">
          <label for="add_flow_name">(Unique) Flow Name:</label>
          <input class="form-control" id="add_flow_name">

          <label for="add_flow_dpid_select">Target Switch (DPID)</label>
          <select class="form-control" id="add_flow_dpid_select"></select> 
        </div>
        

      </div>
    </div>
  </div>
  



  <div class="panel panel-default">
    <div class="panel-heading" data-toggle="collapse" data-parent="#addFlowAccordian" href="#collapse2">
      <h4 class="panel-title">
        <a>Match Fields</a>
      </h4>
    </div>
    <div id="collapse2" class="panel-collapse collapse">
      <div class="panel-body" id="add_flow_match_form">
      
      </div>

    </div>
  </div>

  <div class="panel panel-default">
    <div class="panel-heading" data-toggle="collapse" data-parent="#addFlowAccordian" href="#collapse3">
      <h4 class="panel-title">
        <a>Action</a>
      </h4>
    </div>
    <div id="collapse3" class="panel-collapse collapse">
      <div class="panel-body">
      
        <div>
          <label>Output</label>
          <input id="add_flow_output_action" />
        </div>

        <div>
          <label>Queue</label>
          <input id="add_flow_queue" />
        </div>

      </div>
    </div>
  </div>


  <div class="panel panel-default">
    <div class="panel-heading" data-toggle="collapse" data-parent="#addFlowAccordian" href="#collapse4">
      <h4 class="panel-title">
        <a>Optional</a>
      </h4>
    </div>
    <div id="collapse4" class="panel-collapse collapse">
      <div class="panel-body">
        <div>
          <label>Priority</label>
          <input id="add_flow_priority" type="number"/>
        </div>
        <div>
          <label>Idle Timeout</label>
          <input id="add_flow_idle_timeout" type="number"/>
        </div>
        <div>
          <label>Hard Timeout</label>
          <input id="add_flow_hard_timeout" type="number"/>
        </div>
        <script type="text/javascript">
          $('#add_flow_idle_timeout').tooltip({"title":"Remove Flow Rule when not active for a certain length of time (secs)"});
          $('#add_flow_hard_timeout').tooltip({"title":"Remove Flow Rule after a certain length of time (secs)"});
          $('#add_flow_priority').tooltip({"title":"Check Priority (0-32767)"});
        </script>

      </div>
    </div>
  </div>


</div> 