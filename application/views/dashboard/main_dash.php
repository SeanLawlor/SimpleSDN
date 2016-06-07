<link rel="stylesheet" href="<?php echo base_url('/resources/application/dash.css')?>"></link>
<script> var graph_data = <?php echo json_encode($graph);?>;</script>
<script> var site_url = "<?php echo site_url();?>";</script>
<script src="<?php echo base_url('/resources/application/topology.js')?>"></script>
<script src="<?php echo base_url('/resources/application/mininet.js')?>"></script>
<script src="<?php echo base_url('/resources/application/dash.js')?>"></script>
<script src="<?php echo base_url('/resources/application/flows.js')?>"></script>
<script src="<?php echo base_url('/resources/application/edge-movers.js')?>"></script>

<div class="row row-full-width">
    <div class="col-md-5 " id="control-pane">
        <div>
        <div style="padding-bottom: 2px">
            <ul class="nav nav-tabs">
                <li role="presentation" class="active" data-toggle="tab" target="#Overview"><a>Overview</a></li>
                <?php 
                    if($this->config->item('allow_mininet')) {
                        echo '<li role="presentation" data-toggle="tab" target="#Mininet"><a>Mininet</a></li>';
                    }
                ?>
                <li role="presentation" data-toggle="tab" target="#Flows"><a>Flows</a></li>
                <li role="presentation" data-toggle="tab" target="#Forwarding"><a>Forwarding</a></li>
            </ul>
        </div>
        </div>
        <div>
            <div id="Flows"><?php $this->view('dashboard/flows'); ?></div>
            <div id="Overview"><?php $this->view('dashboard/overview'); ?></div>
            <div id="Mininet"><?php $this->view('dashboard/mininet'); ?></div>
            <div id="Forwarding"><?php $this->view('dashboard/forwarding'); ?></div>

        </div>
    </div>

    <div class="col-md-7 " id="topology-pane"></div>
    <div style="position: absolute;top:5px;right:5px;">
        <button id="topology-locker" type="button" class="btn btn-default" onclick="topology.toggleLock()">
            <span class="glyphicon glyphicon-lock" aria-hidden="true"></span>
        </button>
        <button type="button" class="btn btn-default" onclick="topology.refresh()">
            <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
        </button>
    </div>
    <div id="graph-info-popover">
        
    </div>
</div>
