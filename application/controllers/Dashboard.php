<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    
    public function index(){
        try{
            if(!isset($_SESSION['interface_key'])){ //attempt to initialise default interface key 
                $this->session->set_userdata('interface_key', $this->floodlight_interface->get_key());//throws exception in failure
            }else if(isset($_SESSION['interface_key']) && !$this->floodlight_interface->probeLocation($_SESSION['interface_key'])){ // checks if controller interface is still active
                    unset($_SESSION['interface_key']);
                    redirect('/setup');        
            }

        }catch (Exception $e){
            log_message('debug', 'Unable to set up default connection to controller');
            redirect('/setup');
        }

        if(!isset($_SESSION['interface_key'])) redirect('/setup');

        log_message('debug', 'Getting graph from floodlight');
        $graph = $this->floodlight_interface->getGraph($_SESSION['interface_key']);
        
        log_message('debug', 'Getting overview from floodlight');
        $overview = $this->floodlight_interface->getOverview($_SESSION['interface_key']);

        $this->load->view('templates/header');
        $this->load->view('dashboard/main_dash', array(
            'graph'=>$graph, 
            'overview'=>$overview
        ));
        $this->load->view('templates/footer');
    }

    function graph(){
        echo json_encode($this->floodlight_interface->getGraph($_SESSION['interface_key']));
    }

    function flows($arg=null){
        if(is_null($arg)) echo json_encode($this->floodlight_interface->getAllFlows($_SESSION['interface_key']));
        else if ($arg=='add') {
            $postArray = $this->input->post();
            echo json_encode($this->floodlight_interface->pushFlow($_SESSION['interface_key'], $postArray));
        }else if ($arg=='clear') {
            echo json_encode($this->floodlight_interface->clearFlows($_SESSION['interface_key']));
        }else if ($arg=='remove') {
            echo json_encode($this->floodlight_interface->removeFlow($_SESSION['interface_key'], $this->input->post()));
        }
    }

    function forwarding($arg){
        echo json_encode($this->floodlight_interface->forwarding($_SESSION['interface_key'], $arg));
    }

    private function check(){
        return (isset($_SESSION['interface_key']) && !$this->floodlight_interface->probeLocation($_SESSION['interface_key']));
    }

    function info(){
        if($this->check()) {echo "lost connection";}
        else {echo json_encode($this->floodlight_interface->getOverview($_SESSION['interface_key']));}
    }
}
