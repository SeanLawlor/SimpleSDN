<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mininet_interface {
        var $CI;
        
        public function __construct() {
            $this->CI =& get_instance();
        }
        
        /*
         * @return list of the available predefined topologies
         */
        public function getTopologies()
        {
            $files = scandir('./mininet/topologies');
            $list = array();
            foreach ($files as $file) {
                if(preg_match('/^([a-z]+)\.py$/i', $file, $matches)){
                    $list[]= $matches[1];
                }
            }
            return $list;
        }
        
        
        /*
         * @return list of the available applications that can be run on the network
         */
        public function getApplications()
        {
            return json_decode($this->sendCommand('applications?'), TRUE)['applications'];
        }
        
        
        public function runApplication($app, $src, $dest, $time){
            $this->sendCommand('run '.$app.' '.$src .' '.$dest.' '.$time);
        }
        
        /*
         * @param $topo The topology to be deployed
         */
        public function deploy($topo){
            $this->quit(); //stop any instances that are already running
            //shell_exec('sudo mn -c');//clean up any residual data
            
            log_message('debug', 'Attempting to start Mininet');
            //mininet needs to be run as root
            //by adding the python script to the sudoers file it can be run without requiring a password
            $q = $this->CI->config->item('mininet_use_queues')? ' -q ':'';
            $cmd = 'sudo ' 
                    . $this->CI->config->item('mininet_run')
                    . ' -t '. $topo
                    . $q
                    . ' -p '
                    . $this->CI->config->item('mininet_port')
                    . ' >mininet_log.txt 2>mininet_log.txt  &';
            shell_exec($cmd);
            sleep(5);
        }
        
        public function deployCustom($topo){
            //Create custom file
            log_message('debug', 'Creating custom topology');
            $template = file('./mininet/topo_template.py');
            
            for($i=1; $i<=$topo['switches']; $i++){
                $name = 's'.$i;
                $template[]= "\t\t".$name." = self.addSwitch('".$name."')";                
            }
            
            for($i=1; $i<=$topo['hosts']; $i++){
                $name = 'h'.$i;
                $template[]= "\t\t".$name." = self.addHost('".$name."')";                
            }
            
            foreach($topo['links'] as $link){
                $template[]= "\t\t"."self.addLink(".$link['src'].", ".$link['dst'].", bw=".$link['bw'].", delay='".$link['dly']."ms', jitter='".$link['jtr']."ms', loss=".$link['pl'].")";
            }

            $file = fopen('./mininet/topologies/custom.py', 'w');
            foreach ($template as $line) {
                fwrite($file, $line."\n");
            }
            fclose($file);
            $this->deploy('custom');
        }
        
        
        /*
         * Detects whether mininet is already running
         * @return boolean
         */
        public function isRunning() {
            return preg_match('/yes/', $this->sendCommand('up?'))==1;
        }


        /*
         *  Stop running mininet instance
         */
        public function quit(){
            $this->sendCommand('quit'); //tell the current instance to stop
        }
        
        /*
         * Sends a command to mininet script
         */
        public function sendCommand($cmd) {
            log_message('info', "Sending mininet command:".$cmd);
            $cmd = "echo \"".$cmd."\" | netcat -w 2 localhost ".$this->CI->config->item('mininet_port');
            return shell_exec($cmd);
        }
        
        /*
         * Returns a list of host ip's
         */
        public function getHosts(){
            return json_decode($this->sendCommand("hosts?"));
        }
        
        public function killApplications() {
            $this->sendCommand("killall");
        }
}
