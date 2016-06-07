<div class="panel panel-default panel-primary">
  <div class="panel-heading">Forwarding Strategy</div>
  <div class="panel-body">

    <table class="table table-condensed">
    <tbody>
        <tr><th>Forwarding Method</th><td id="forwarding_info_method"></td></tr>
        <tr><th>Auto Forwarding</th><td id="forwarding_info_auto"></td></tr>
    </tbody>
    </table>


    <div  style="padding-bottom: 5px">
        <select id="forwarding-select" style="width: 100%">
            <option value="shortest_path">Shortest Path</option>
            <option value="delay">Least Delay</option>
            <option value="least_loss">Packet Loss</option>
            <option value="jitter">Jitter</option>
            <option value="delay_variation">Delay Variation</option>
            <option value="none">Off</option>
        </select>
        <button type="button" class="btn btn-primary pull-right btn-success" onclick="set_forwarding()">
            Ok
        </button>
    </div>
    
  </div>
</div>



<div class="panel panel-default panel-primary">
  <div class="panel-heading">Queues</div>
  <div class="panel-body">


    <div class="switch-selector row">
        <div class="col-md-1" onclick="showQueueSwitch(++curr_q_switch_selection);"><h4 class="glyphicon glyphicon-menu-left"></h4></div>
        <div id="q-switch-id-placeholder" class="col-md-10 switch-id-placeholder">
        </div>
        <div class="col-md-1" onclick="showQueueSwitch(--curr_q_switch_selection);"><h4 class="glyphicon glyphicon-menu-right"></h4></div>
    </div>  
    <div id="q-table-placeholder"></div>

  </div>
</div>


<script type="text/javascript">
    //Display flow information on the queues tab
    var queues;
    var curr_q_switch_selection=0;
    function updateQueues(){
        $.get(site_url+'/dashboard/forwarding/queues', function(data){
            if(data === null) return;
            var json=JSON.parse(data);
            if(json === null) return;
            var swicthIDs = Object.keys(json);
            swicthIDs.sort().reverse();
            
            var displayFirst = $('#q-switch-id-placeholder').children().length===0;
            
            for(var i=0; i<swicthIDs.length; i++){
                if($(document.getElementById('q-switch-id-'+swicthIDs[i])).length===0){//if not found
                    $('#q-switch-id-placeholder').append('<h4 style="display:none" id="'+'q-switch-id-'+swicthIDs[i]+'">'+swicthIDs[i]+'</h4>');
                    $('#q-table-placeholder').append('<div style="display:none" id="'+'q-table-'+swicthIDs[i]+'">'+buildQueueTable(json[swicthIDs[i]])+'</div>');
                }else{
                    $(document.getElementById('q-table-'+swicthIDs[i])).html(buildQueueTable(json[swicthIDs[i]]));
                }
            }
            if(curr_q_switch_selection===NaN)curr_q_switch_selection=0;
            showQueueSwitch(curr_q_switch_selection);
        });
    }

    function buildQueueTable(data){
        var table = '<table class="table table-striped table-bordered table-condensed">';
        table += '<thead><tr><th>Port #</th><th>Queue Id</th><th>Min. Rate</th><th>Max. Rate</th></tr></thead><tbody>';
        for(var i=0; i<data.length; i++){
            table+= '<tr>';
            table+= '<td>'+data[i].port['portNumber']+'</td>';
            table+= '<td>'+data[i].queueId+'</td>';
            table+= '<td>'+data[i].minRate+'</td>';
            table+= '<td>'+data[i].minRate+'</td>';
        }
        return table+'</tbody></table>';
    }

    function showQueueSwitch(i){

        var count = $('#q-switch-id-placeholder').children().length;
        
        //loop back around if i not positive
        if(i<0)  i = count - (-i%7);
        
        
        curr_q_switch_selection = i%count;
        $('#q-switch-id-placeholder').children().hide();
        $($('#q-switch-id-placeholder').children()[curr_q_switch_selection]).show();

        $('#q-table-placeholder').children().hide();
        $($('#q-table-placeholder').children()[curr_q_switch_selection]).show();
    }

    function updateForwardingInfo(){
        $.get(site_url+'/dashboard/forwarding/status', function(data){ 
            if(data === null ) return;
            var json=JSON.parse(data);
            if(json===null) return;
            set_forwarding_info(json.forwarding_by, json.auto_forward);    
        });
    }
    function set_forwarding_info(method, auto){
        $('#forwarding_info_method').html(method);
        $('#forwarding_info_auto').html(auto);                
    }

    function set_forwarding(){
        var opt = $('#forwarding-select').val();
        $.get(site_url+'/dashboard/forwarding/'+opt, function(data){
            var json=JSON.parse(data);
            set_forwarding_info(json.forwarding_by, json.auto_forward);    
        })
    }

    window.setInterval(function(){
           updateForwardingInfo();
           updateQueues();
    },updateInterval);
</script>