<a href="#" class="list-group-item setup-option" data-toggle="modal" data-target="#remote-modal">
    <div class="row">
        <div class="col-md-10">
            <h2>Remote Connection</h2>
            Connect to floodlight on a remote machine
        </div>
        <div class="col-md-2">
            <h1><div class="pull-right glyphicon glyphicon-menu-right"></h1>
        </div>
    </div>
</a>

<div id="remote-modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"></span></button>
            <h4 class="modal-title">Remote Connection Setup</h4>
          </div>
          <div class="modal-body">
              
              <form id="connection-form" class="form-inline">
                <div class="form-group">
                  <label>Location</label>
                  <input id="connection-address" type="text" class="form-control" placeholder="address" >
                </div>
                <div class="form-group">
                  <label>:</label>
                  <input id="connection-port" type="text" class="form-control" placeholder="port">
                </div>
              </form onsubmit="sendConnectionRequest()"> 
              <div id="connection-status"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="sendConnectionRequest()">Connect</button>
          </div>
        </div>
    </div>
</div>

<script>
function sendConnectionRequest() {
  console.log($('#connection-address').val());
  console.log($('#connection-port').val());
  
    $.post('/setup/remoteconnection/', 
        {
          address:$('#connection-address').val(), 
          port:$('#connection-port').val()
        },
        function(data){
          if(data.connection_status){
            window.location.href="/dashboard"
          }else{
            $('#connection-status').html('<p class="bg-danger">Failed to connect to '+$('#connection-address').val()+':'+$('#connection-port').val()+'</p>');
          }
        },
        "json"
    );
}
</script>