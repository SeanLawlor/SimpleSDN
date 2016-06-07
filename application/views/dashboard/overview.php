<div id="overview-info" class="list-group">
        <?php foreach($overview as $key => $value){
        print_r('<div class="list-group-item"><h4>'.$key.'<span class="pull-right label label-default">'.$value.'</span></h4></div>');
    }   ?>
</div>