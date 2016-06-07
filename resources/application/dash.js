//update interval in ms
var updateInterval = 4500;
var flowAttributes;
var curr_switch_selection=0;



$(function(){ // on dom ready

    //This block handles the changing of tabs in the dashboard - overview tab opened first
    $('#Overview').siblings().hide();
    $('#Overview').show();
    $('.nav-tabs > li').click(function(){
        var target = $(this).attr('target');
        $(target).siblings().hide();
        $(target).show();
    });


    //Start the graph view
    topology.init();
    updateFlows();
    
    //pull information using ajax at regular intervals
    //request & update occurs unless already waiting for data to be returned 
    var waiting = false;
    window.setInterval(function(){
        if(!waiting && topology.update) {
            waiting=true;
            updateFlows();//get flow info to display on the flow tab
            topology.getUpdates();//update the graph 
            waiting=false;
        }
    },updateInterval);

    //update the overview tab at regular intervals
    window.setInterval(function(){
        updateOverview();        
    },updateInterval);

})

//pull overview information and display in overview tab
function updateOverview(){
    $.get(site_url+'/dashboard/info', function(data){ 
            if(data=="lost connection") {
                alert("Connection dropped to controller");
                window.location = site_url+'/setup';
            } 
            var d = JSON.parse(data);

            $('#overview-info').children().remove();
            var keys = Object.keys(d);
            for(var i=0; i< keys.length; i++)  $('#overview-info').append('<div class="list-group-item"><h4>'+keys[i]+'<span class="pull-right label label-default">'+d[keys[i]]+'</span></h4></div>');
    });
}


//Display flow information on the flows tab
var flows;
function updateFlows(){
    $.get(site_url+'/dashboard/flows', function(data){
        
        var json=JSON.parse(data);
        flows = json;
        var swicthIDs = Object.keys(json);
        swicthIDs.sort().reverse();
        
        var displayFirst = $('#switch-id-placeholder').children().length===0;
        
        for(var i=0; i<swicthIDs.length; i++){
            if($(document.getElementById('switch-id-'+swicthIDs[i])).length===0){//if not found
                $('#switch-id-placeholder').append('<h4 style="display:none" id="'+'switch-id-'+swicthIDs[i]+'">'+swicthIDs[i]+'</h4>');
                $('#switch-table-placeholder').append('<div style="display:none" id="'+'switch-table-'+swicthIDs[i]+'">'+flowTableShort(json[swicthIDs[i]])+'</div>');
            }else{
                $(document.getElementById('switch-table-'+swicthIDs[i])).html(flowTableShort(json[swicthIDs[i]]));
            }
        }
        if(curr_switch_selection===NaN)curr_switch_selection=0;
        showSwitch(curr_switch_selection);
    });
}

function clearFlows(){
    $.get(site_url+'/dashboard/flows/clear', function(data){alert(data);});
}

function showSwitch(i){

    var count = $('#switch-id-placeholder').children().length;
    
    //loop back around if i not positive
    if(i<0)  i = count - (-i%7);
    
    
    curr_switch_selection = i%count;
    $('#switch-id-placeholder').children().hide();
    $($('#switch-id-placeholder').children()[curr_switch_selection]).show();

    $('#switch-table-placeholder').children().hide();
    $($('#switch-table-placeholder').children()[curr_switch_selection]).show();
}

function flowTableShort(flowData){
    flowData = flowData.flows;
    var table = '<table class="table table-striped table-bordered table-condensed">';
    table += '<thead><tr><th>!</th><th>Packets</th><th>Match</th><th>Action</th></tr></thead><tbody>';
    for(var i=0; i<flowData.length; i++){
        table+= '<tr>';
        table+= '<td>'+flowData[i].priority+'</td>';
        table+= '<td>'+flowData[i].packetCount+'</td>';
        table+= '<td>'+subTable(flowData[i].match)+'</td>';
        if(typeof flowData[i].instructions['instruction_apply_actions'] !== "undefined") {
            // table+= '<td>'+subTable(flowData[i].instructions['instruction_apply_actions'])+'</td>';
            table+= '<td>'+subTable(flowData[i].instructions['instruction_apply_actions']['actions'])+'</td>';
            
        }else if(typeof flowData[i].instructions['none'] !== 'undefined') table+= '<td>'+flowData[i].instructions['none']+'</td>';
    }
    return table+'</tbody></table>';
}

function subTable(json){
    // if(typeof json!=='object') {
    //     return json ; 
    // }
    if(Array.isArray(json)){
        var ret = '<table class="table table-condensed subTable">';
        for(var i=0; i<json.length; i++) ret += '<tr><td>'+json[i]+'</td></tr>';
        ret += '</table>';
        return ret;
    }else if(typeof json == 'object'){
        var keys = Object.keys(json);
        var ret='<table class="table table-condensed subTable">';
        for(var i=0; i<keys.length; i++){
            ret += '<tr><td>'+keys[i]+'</td><td>'+subTable(json[keys[i]])+'</td></tr>';
        }
        return ret+'</table>';
    }else return json;
}