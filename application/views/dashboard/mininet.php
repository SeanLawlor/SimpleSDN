<script src="<?php echo base_url('/resources/application/mininet.js')?>"></script>
<div id="mininet-status" class="alert alert-danger" role="alert" style="display:none">Mininet is Running
    <button class="pull-right btn btn-danger glyphicon glyphicon-off" onClick="mininet.stop()" style="padding-top:2px;padding-bottom:2px;"></button>
</div>

<div class="panel panel-default panel-primary">
  <div class="panel-heading">Deploy Predefined topology</div>
  <div class="panel-body">
    <div  style="padding-bottom: 5px">
        <select id="topology-select" style="width: 100%">
        </select>
    </div>
      <button id="deploy-mininet" type="button" class="btn btn-primary pull-right btn-success" onclick="mininet.deployPredefinedNetwork()">Deploy</button>
      
  </div>
</div>


<div class="panel panel-default panel-primary">
  <div class="panel-heading">Deploy Custom topology</div>
  <div class="panel-body">
    <form style="padding-bottom: 5px; width: 100%;">
        <div style="padding-bottom: 7px;">
            <label>Switches</label>
            <input type="number" class="pull-right" id="custom-switches" value="1" min="0" oninput="updateTable()">
        </div>
        <div style="padding-bottom: 7px; padding-top: 5px;">
            <label>Hosts</label>
            <input type="number" class="pull-right" id="custom-hosts" value="2" min="0" oninput="updateTable()">
        </div>
        <div style="padding-bottom: 7px;  padding-top: 5px;">
            <label>Links</label>
            <input type="number" class="pull-right" id="custom-links" value="0" min="0" oninput="updateTable()">
            <button class="btn btn-default btn-xs pull-right" type="button" data-toggle="modal" data-target="#link-properties"><span class="glyphicon glyphicon-cog"></span></button>
        </div>
   
    </form>
    <button type="button" class="btn btn-primary btn-success pull-right" onclick="mininet.deployCustomNetwork()">Deploy</button>
      
  </div>
</div>

<div class="panel panel-default panel-primary">
  <div class="panel-heading">Run Application</div>
  <div class="panel-body">
    <form style="padding-bottom: 12px; width: 100%">
        <div class="form-group">
            <label>Application</label>
            <select id="application-select" class="pull-right" style="width: 60%">
            </select>
        </div> 
        <div class="form-group">
            <label>Source</label>
            <select id="application-src" class="app-location-select pull-right"  style="width: 60%">
            </select>
        </div>
        <div class="form-group">
        <label>Destination</label>
            <select id="application-dest" class="app-location-select pull-right"   style="width: 60%">        
            </select>
        </div>
        <div > 
            <label>Runtime</label>
            <input type="number" class="pull-right" id="application-time" value="10">
        </div>
    </form>
    <button type="button" class="btn btn-primary btn-success pull-right" onclick="mininet.runApplication()">Run</button>
    <button type="button" class="btn btn-primary btn-danger" onclick="mininet.stopApplication()">Stop All</button>
    
    </div>
  
</div>

<div id="link-properties" class="modal fade">
  <div class="modal-dialog" style="display:table">
    <div class="modal-content" style="padding-left: 5px; padding-right: 5px;">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        <h4 class="modal-title">Link Properties</h4>
      </div>
            <table id="link-table" class="modal-body table">
            <thead>
                <tr>
                    <th>Src</th>
                    <th>Dst</th>
                    <th>Bandwidth (Mbs)</th>
                    <th>Delay (ms)</th>
                    <th>Packet Loss (%)</th>
                    <th>Jitter (ms)</th>
                </tr>
            </thead>
            <tbody id="link-table-body">
                
            </tbody>
        </table>

      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Ok</button>
      </div>
    </div>
  </div>
</div>