<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Debug extends CI_Controller {
    
    public function index(){
        if( is_null($_SESSION['interface_key'])  ||  !$this->config->item('allow_debug')){ 
            log_message('debug', 'Cannot display debug info');
            redirect('/');
        }
        $key=$_SESSION['interface_key'];
        $this->load->view('debug_page', array('commands'=> $this->floodlight_interface->getAllCommands($key)));
    }
}