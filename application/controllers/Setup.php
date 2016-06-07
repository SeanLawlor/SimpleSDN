<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setup extends CI_Controller {
    
    private static $MAX_PROBES = 30;
    
    public function index(){
        $this->load->view('templates/header');
        $this->load->view('setup-page/setup');
        $this->load->view('templates/footer');
    }
    
    public function findfloodlight() {
        //search for a running floodlight instance
        $output = shell_exec('ps -ax | grep floodlight');
        
        //extract PID
        preg_match('/^\s*(\d+)\s/', $output, $matches);
        
        //Search for ports that PID is using
        $output = shell_exec('netstat -pln | grep '.$matches[1]);
       
        preg_match_all('/\:(\d+)\s+/', $output, $matches);
        foreach ($matches[1] as $possible_port) {
            try {        
                $fl_key = $this->floodlight_interface->get_key(NULL, $possible_port);
                break;
            } catch (Exception $exc) {
                log_message('debug', 'Failed to connect to port '.$possible_port);
            }
        }
        if(isset($fl_key)){
            $this->session->set_userdata('interface_key', $fl_key);
        }
        redirect('/dashboard');            
    }
    
    public function remoteconnection() {
        $postArray = $this->input->post();
        log_message('debug', 'remote request recieved to '.print_r($_POST, TRUE));
        try {        
            $fl_key = $this->floodlight_interface->get_key($postArray['address'], $postArray['port']);
            $this->session->set_userdata('interface_key', $fl_key);
            $status = TRUE;
        } catch (Exception $exc) {
            log_message('debug', 'Failed to connect to '.$postArray['address'].':'. $postArray['port']);
            $status = FALSE;
        }
        echo(json_encode(array('connection_status'=>$status)));
    }
    
    public function startfloodlight() {
        $jar = $this->config->item('floodlight_jar_location');
        $cf = $this->config->item('floodlight_cf_location');
        $cmd = "java -jar ".$jar." -cf ".$cf;
        shell_exec($cmd." >/dev/null 2>/dev/null &");
        print_r('Starting floodlight');
        
        for($probe_count=0; $probe_count<Setup::$MAX_PROBES; $probe_count++){
            try {
                $fl_key = $this->floodlight_interface->get_key();
                $this->session->set_userdata('interface_key', $fl_key);
                break;
            } catch (Exception $ex) {
                sleep(2);
            }
        }
        redirect('/');
    }
        
}
