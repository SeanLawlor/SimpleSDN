<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mininet extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('Mininet_interface');
    }

    function deploy($topo=NULL){
        if(!is_null($topo)){
            log_message('info', "Attempting to deploy ".$topo);
            $this->mininet_interface->deploy($topo);
            print_r('redirecting');            
        }        
        redirect('/');
    }
    
    function deployCustom(){
        $postArray = $this->input->post();
       // if(!isset($postArray['switches'], $postArray['hosts'],$postArray['links'])) echo 'error: Invalid input';
        $this->mininet_interface->deployCustom($postArray);
        echo 'success';
    }
    
    
    function stop(){
        $this->mininet_interface->quit();
        print_r('redirecting');
        redirect('/');
    }
    
    function status(){
        $ret = json_encode(array('status'=>$this->mininet_interface->isRunning()?'up':'down',
                                'hosts'=>$this->mininet_interface->getHosts(),
                                'apps'=>$this->mininet_interface->getApplications()
            ));
        print_r($ret);
    }
    
    function run(){
        $postArray = $this->input->post();
        if(!isset($postArray['app'], $postArray['src'],$postArray['dest'])) return;
        $this->mininet_interface->runApplication($postArray['app'], $postArray['src'],$postArray['dest'], $postArray['time']);
    }
    
    function stopApplications() {
        $this->mininet_interface->killApplications();
        echo 'stopping';
    }
    
    function topologies() {
        echo json_encode($this->mininet_interface->getTopologies());
    }
}
