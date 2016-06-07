<div class="panel panel-default panel-primary">
  <div class="panel-heading">Edit Flows</div>
  <div class="panel-body">
    <div class="btn-group-vertical">
      <button id="addRuleButton" type="button" class="btn btn-info" data-toggle="modal" data-target="#addFlowModal" onclick="init_add_rule()">Add Switch Rule</button>
      <script type="text/javascript">
          var hasBeenInit = false;
          function init_add_rule(){
              if(hasBeenInit) return;
              $('#output_action').tooltip({"title":"drop/flood/all/Port#"});
              $('#add_flow_name').tooltip({"title":"If left blank, timestamp will be used"});
              populate_add_flow();//run once when page loads
              hasBeenInit = true;
          }
      </script>
      <button type="button" class="btn btn-info" onclick="clearFlows()">Clear All Added Flows</button>
    </div>  
    <div class="btn-group-vertical">
      <button type="button" class="btn btn-info" onclick="install_flows_shortest_path()">Install Shortest Paths</button>
      <button type="button" class="btn btn-info" onclick="flood_arp(); ">Allow ARP broadcast</button>
      <script type="text/javascript">
          function toggle_btn_info_warning(e){
              e.toggleClass('btn-info');
              e.toggleClass('btn-warning');
          }
      </script>
    </div>
    <div class="btn-group-vertical">
      <button type="button" class="btn btn-info" onclick="toggleFlowEdit(); toggle_btn_info_warning($(this));">Move Flow</button>
    </div>  
    
    <div class="modal fade" id="addFlowModal" role="dialog">
      <div class="modal-dialog">
      
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
            <h4 class="modal-title">Add Flow</h4>
          </div>
          <div class="modal-body">
            <?php $this->view('dashboard/add_flow');  ?>         
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" onclick="populate_add_flow()">Refresh</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" onclick="addNewFlow()">Submit</button>
          </div>
        </div>
        
      </div>
    </div>
  </div>

</div>

<div class="panel panel-default panel-primary">
  <div class="panel-heading">Flows Overview</div>
  <div class="panel-body">
    <div id="switch-selector" class="switch-selector row">
        <div class="col-md-1" onclick="showSwitch(++curr_switch_selection);"><h4 class="glyphicon glyphicon-menu-left"></h4></div>
        <div id="switch-id-placeholder" class="col-md-10 switch-id-placeholder">
        </div>
        <div class="col-md-1" onclick="showSwitch(--curr_switch_selection);"><h4 class="glyphicon glyphicon-menu-right"></h4></div>
    </div>  
    <div id="switch-table-placeholder"></div>
  </div>
</div>