var mininet = {
    applications:[],
    
    deployPredefinedNetwork: function(){
        var val = $('#topology-select').val();
        topology.update = false;
        window.location=site_url+'/mininet/deploy/'+val;
    },
    
    deployCustomNetwork: function(){
        var customLinks = [];
        var rows = $('#link-table-body > tr');
        rows.each(function(ele){
            var vals = $(this).children().children();
            customLinks.push({
                src:vals[0].value,
                dst:vals[1].value,
                bw:vals[2].value,
                dly:vals[3].value,
                pl:vals[4].value,
                jtr:vals[5].value
            });
        });
        
        var desc = {
            switches:$('#custom-switches').val(),
            hosts:$('#custom-hosts').val(),
            links:customLinks
        }
        $.post(site_url+'/mininet/deployCustom', desc);
    },

    runApplication: function(){
        var val = $('#application-select').val();
        for(var i=0; i<mininet.applications.length; i++){
            if(mininet.applications[i]['desc']===val) {
                val = mininet.applications[i]['cmd'];
                break;
            }
        }
        
        var src = $('#application-src').val();
        var dest = $('#application-dest').val();
        var time = $('#application-time').val();
        
        $.post(site_url+'/mininet/run', {'app':val, 'src':src, 'dest':dest, 'time':time});
    },
    
    stopApplication: function(){
        $.get(site_url+'/mininet/stopApplications');
    },

    stop: function (){
        window.location = site_url+'/mininet/stop';
    },

    updateStatus: function(){
        var statusData;
        //Display box informing user that mininet is running
        $.get(site_url+'/mininet/status', function(data){
            statusData=JSON.parse(data);
            
            statusData.status==='up'? 
            $('#mininet-status').show():
            $('#mininet-status').hide();

            //Update options for running application between nodes
            
            var currentVals = [];
            var opts = $('.app-location-select').children();
            for(var i=0; i<opts.length; i++)
                currentVals.push(opts[i].value);
            
            //add new values
            for(var i=0; statusData.hosts!=null && i<statusData.hosts.length; i++)
                if(currentVals.indexOf(statusData.hosts[i])===-1)$('.app-location-select').append('<option>'+statusData.hosts[i]+'</option>');
            
            mininet.applications=statusData.apps;
            //remove old values
            currentVals = [];
            var opts = $('#application-select').children();
            for(var i=0; i<opts.length; i++)
                currentVals.push(opts[i].value);
            
            //add new values
            for(var i=0; statusData.apps!=null && i<statusData.apps.length; i++)
                if(currentVals.indexOf(statusData.apps[i]['desc'])===-1)
                    $('#application-select').append('<option>'+statusData.apps[i]['desc']+'</option>');
            
            
        });
    }
};

listTopologies();

$(function(){ // on dom ready
    mininet.updateStatus();
    window.setInterval(function(){
        mininet.updateStatus();
    },5000);
});


function listTopologies(){
    $.get(site_url+'/mininet/topologies', function(data){
        var topos = JSON.parse(data);
        for(var i=0; i<topos.length; i++)
            $('#topology-select').append('<option>'+topos[i]+'</option>')
    })
}

function updateTable(){
    var count = $('#custom-links').val();                                                                                                                               
    var inTable = $('#link-table-body').children();
    
    count===0? $('#link-table').hide():$('#link-table').show();
    
    if(count < inTable.size()){
        var toRemove = inTable.size()-count;
        inTable.slice(-toRemove).remove();
    }
    else if(count > inTable.size()){
        var toAdd = count - inTable.size();
        for(var i=0; i<toAdd; i++){
            $('#link-table-body').append(linkRowTemplate());
        }
    }
    
    $('#link-table-body select').html(customTopoNodes());
}

function linkRowTemplate(){
    return '<tr><td><select></select></td>'+
        '<td><select></select></td>'+
        '<td><input type="number" min="0" value="10"></td>'+
        '<td><input type="number" min="0" value="0"></td>'+
        '<td><input type="number" min="0" value="0"></td>'+
        '<td><input type="number" min="0" value="0"></td>'+
    '</tr>';
}

function customTopoNodes(){
    var optionList = "";
    var switchCount = $('#custom-switches').val();
    var hostCount = $('#custom-hosts').val();
    
    for(var i=1; i<=switchCount; i++)
        optionList = optionList+"<option>s"+i+"</option>";
    
    for(var i=1; i<=hostCount; i++)
        optionList = optionList+"<option>h"+i+"</option>";
    
    return optionList;
    
}
