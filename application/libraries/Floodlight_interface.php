<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Floodlight_interface {
        
        private $getCommands = array(
        	'loaded_modules'=>array('url'=>'/wm/core/module/loaded/json'),
            'switch_roles'=>array(
                'url'=>'/wm/core/switch/all/role/json'
            ),
            'switch_role'=>array(
                'url'=>'/wm/core/switch/<switchId>/role/json'
            ),
            'universal_stat'=>array(
                'url'=>'/wm/core/switch/all/<statType>/json',
                'statTypes'=>array(
                    'aggregate', 'desc', 'flow', 'group', 'group-desc', 'group-features', 'meter', 'meter-config', 'meter-features', 'port',' port-desc', 'queue', 'table', 'features'
                )
            ),
            'switch_stat'=>array(
                'url'=>'/wm/core/switch/<switchId>/<statType>/json',
                'statTypes'=>array(
                    'aggregate', 'desc', 'flow', 'group', 'group-desc', 'group-features', 'meter', 'meter-config', 'meter-features', 'port',' port-desc', 'queue', 'table', 'features'
                )
            ),
            'switch_ids'=>array(
                'url'=>'/wm/core/controller/switches/json'
            ),
            'controller_summary'=>array(
                'url'=>'/wm/core/controller/summary/json'
            ),
            'module_counters'=>array( //"OFSwitchManager" for switch counters
                'url'=>'/wm/core/counter/<moduleName>/all/json'
            ),
            'switch_counters'=>array(
                'url'=>'/wm/core/counter/OFSwitchManager/all/json'
            ),
            'controller_API_health'=>array(
                'url'=>'/wm/core/health/json'
            ),
            'controller_tables'=>array(
                'url'=>'/wm/core/storage/tables/json'
            ),
            'controller_uptime'=>array(
                'url'=>'/wm/core/system/uptime/json'
            ),
            'inter-switch_links'=>array(
                'url'=>'/wm/topology/links/json'
            ),
            'external_links'=>array(
                'url'=>'/wm/topology/external-links/json'
            ),
            'route'=>array(
                'url'=>'/wm/topology/route/<src-dpid>/<src-port>/<dst-dpid>/<dst-port>/json'
            ),
            'all_devices'=>array(
                'url'=>'/wm/device/'
            ),
            'all_static_flows'=>array(
                'url'=>'/wm/staticflowpusher/list/all/json'
            ),
            'clear_all_static_flows'=>array(
                'url'=>'/wm/staticflowpusher/clear/all/json'
            ),
            'switch_static_flows'=>array(
                'url'=>'/wm/staticflowpusher/list/<switch>/json'
            ),
            'networks'=>array(//<tenant> currently ignored
                'url'=>'/networkService/v1.1/tenants/<tenant>/networks'
            ),
            'forwarding'=>array(
    			'url'=>'/fyp/forwarding/<option>',
                'option'=>array(
                    'jitter', 'none', 'delay', 'delay_variation', 'shortest_path', 'least_loss', 'status'
                )
            )
        );

        private $postCommands = array(
            'add_flow'=>array(
                'url'=>'/wm/staticflowpusher/json'
            )
        );

        private $deleteCommands = array(
            'remove_static_flow'=>array(
                'url'=>'/wm/staticflowpusher/json'
            )
        );


        /*
         * Returns a 'key' (Adresss, port, url) corresponding to a controller
         * Used to verify a connection - an exception is thrown otherwise
         * @param $address [optional] The address where the controller is located [will use the location specified in config by default]
         *        ex. '192.168.1.7' or 'my.location.web'
         * @param $port [optional] The port where the controller is running on [will use the port specified in config by default]
         * @throws Excpetion if a connection cannot be setup
         * @return controller 'key'
         */
        public function get_key($address=null, $port=null)
        {
            $CI =& get_instance();
            //Attempt connection to default location if not specified
            if(is_null($address)){
                $address = $CI->config->item('floodlight_default_location');    
            }
            else { //Clean address location (whitespace + trailing '/') if specified
                $address = trim($address);
                if(preg_match('#(.+)/$#', $address, $matches)){ $address=$matches[1]; }
            }
            
            //Attempt connection to default port if not specified
            if(is_null($port)){ 
                $port = $CI->config->item('floodlight_default_port');
            }
            
            //Set the key value
            $key = array('address'=>$address, 'port'=>$port, 'url'=>$address.':'.$port);
            log_message('info', 'Attempting connection to '.$key['url']);
            
            //check that controller is up 
            $probe_result = $this->probeLocation($key);
            if(!$probe_result){throw new Exception;}
            
            //return key on success
            log_message('info', 'Connection Success'.$key['url']);
            return $key;
        }
        
        /*
         * @param $key The location key to probe for a floodlight controller
         * @return boolean True is successful
         */
        public function probeLocation($key){
            //try to pull a command from the controller - (null on failure)
            $result = $this->getCommand($key, 'controller_API_health');
            log_message('info', 'Probe status:'.($result?'SUCCESS':'FAIL'));


            return !is_null($result);
        }
        
        
        /*
         * @return array command names are used as keys to their corresponding output
         */
        public function getAllCommands($key) {
            $commands_output = array();
            foreach ($this->getCommands as $cmd => $cmd_info) {
                //skip if url requires arguments
                $result = $this->getCommand($key, $cmd);
                if(!is_null($result)){ $commands_output[$cmd]=$result;}
            }
            return $commands_output;
        }
        
        /*
         * @param $cmd the name of the command to execute
         * @param $args array with the arguments to pass to the command
         *      ex. for '/wm/core/switch/<switchId>/<statType>/json', let $args = array('switchId'=>'..someID..', 'statType'=>'..someStat..')
         * @return array with the result of the command or NULL
         */
        public function getCommand($key, $cmd, $args=NULL){
            $url = $key['url'] . $this->getCommands[$cmd]['url'];
            
            if(preg_match_all('/<(.+?)>/', $url, $matches)){
                foreach($matches[1] as $match){
                    $url = str_replace('<'.$match.'>', $args[$match], $url);
                }
            }
            log_message('debug', 'start curl from '.$url);
            $result = shell_exec('curl -fsm 2 '.$url);
            log_message('debug', 'end curl from '.$url);
            return json_decode($result, TRUE);
        }

        /*
         * @param $cmd the name of the command to execute
         * @param data post data
         * @param $args array with the arguments to pass to the command
         *      ex. for '/wm/core/switch/<switchId>/<statType>/json', let $args = array('switchId'=>'..someID..', 'statType'=>'..someStat..')
         * @return array with the result of the command or NULL
         */
        public function postCommand($key, $cmd, $data, $args=NULL){
            $url = $key['url'] . $this->postCommands[$cmd]['url'];
            
            if(preg_match_all('/<(.+?)>/', $url, $matches)){
                foreach($matches[1] as $match){
                    $url = str_replace('<'.$match.'>', $args[$match], $url);
                }
            }
            log_message('debug', 'start curl post '.$url);
            $result = shell_exec('curl -fsm 2 '.$url.' -d '.json_encode($data));
            log_message('debug', 'end curl post '.$url);
            return json_decode($result, TRUE);
        }

        /*
         * @param $cmd the name of the command to execute
         * @param data delete data
         * @param $args array with the arguments to pass to the command
         *      ex. for '/wm/core/switch/<switchId>/<statType>/json', let $args = array('switchId'=>'..someID..', 'statType'=>'..someStat..')
         * @return array with the result of the command or NULL
         */
        public function deleteCommand($key, $cmd, $data, $args=NULL){
            $url = $key['url'] . $this->deleteCommands[$cmd]['url'];
            
            if(preg_match_all('/<(.+?)>/', $url, $matches)){
                foreach($matches[1] as $match){
                    $url = str_replace('<'.$match.'>', $args[$match], $url);
                }
            }
            log_message('debug', 'start curl delete '.$url);
            $result = shell_exec('curl -X DELETE -fsm 2 '.$url.' -d '.json_encode($data) );
            log_message('debug', 'end curl delete '.$url);
            return json_decode($result, TRUE);
        }
        
        
        public function getGraph($key) {
            $graph = array('nodes'=>array(), 'edges'=>array());
            $switches = $this->getCommand($key, 'switch_ids');
            $devices = $this->getCommand($key, 'all_devices');
            $links = $this->getCommand($key, 'inter-switch_links');
            
            //get flows in the network & add to the graph
            // $allFlows = $this->getCommand($key, 'universal_stat', array('statType'=>'flow'));
            // $allFlows = $this->addFlowNames($key, $allFlows);
            $allFlows = $this->getAllFlows($key);

            //find current link speeds
            $portDesc = $this->getCommand($key, 'universal_stat', array('statType'=>'port-desc'));


            if(!(
                is_array($switches) && 
                is_array($devices) && 
                is_array($links) &&
                is_array($portDesc) 
                )){
                return;
            }

            //add switch nodes
            foreach ($switches as $switch) {
                $data=array();
                $data['id']=$switch['switchDPID'];
                // $data['type']='switch';
                $graph['nodes'][]=array('data'=>$data, 
                    'classes'=>'switch'
                    );
            }
            
            //add inter-switch links
            foreach ($links as $link) {
                $graph['edges'][]= array(
                    'data'=>array(
                        'id'=>$link['src-switch'].'-'.$link['src-port'].'-'.$link['dst-switch'].'-'.$link['dst-port'],
                        'source'=>$link['src-switch'], 
                        'target'=>$link['dst-switch'],
                        'source-port'=>$link['src-port'],
                        'dst-port'=>$link['dst-port'],
                        // 'type'=>'link'
                    ),
                    'classes'=>'link'
                );
            }
            
            //add device nodes
            foreach ($devices as $device) {
                if(!isset($device['attachmentPoint'][0])){continue;}//skip if device is not attached
                $data=array();
                $data['id']=$device['mac'][0];
                $data['mac']=$device['mac'][0];
                if(isset($device['ipv4'][0])) $data['ip']=$device['ipv4'][0];
                // $data['type']='device';
                $graph['nodes'][]=array('data'=>$data, 'classes'=>'device');
                
                //add edge for each attachment point
                for($i=0; $i< count($device['attachmentPoint']); $i++){
                    $graph['edges'][]= array(
                        'data'=>array(
                            'id'=>$data['id'].'-'.$device['attachmentPoint'][$i]['switchDPID'].'-'.$device['attachmentPoint'][$i]['port'],
                            'source'=>$data['id'], 
                            'target'=>$device['attachmentPoint'][$i]['switchDPID'],
                            'dst-port'=>$device['attachmentPoint'][$i]['port'],
                            // 'type'=>'link'
                            ),
                        'classes'=>'link'
                    );
                } 
            }
            
       
            //iterate through each switch
            foreach ($portDesc as $switchDPID => $desc){
                //iterate through the descriptions of each port
                if(!isset($desc['portDesc'])) {continue;}
                foreach ($desc['portDesc'] as $portArr) {
                    
                    if($portArr['portNumber']=="local"){continue;}
                    //find edge with matching switch + port
                    foreach ($graph['edges'] as $edgeKey => $edge) {
                        if($edge['data']['target']==$switchDPID && $edge['data']['dst-port']==$portArr['portNumber']){
                            $graph['edges'][$edgeKey]['data']['lineSpeed']=$portArr['currSpeed']/1000 . 'Mb/s';
                            break;
                        }
                    };
                }
            }
            
            foreach ($allFlows as $switchDPID => $arr) {
                if(!isset($arr['flows'])){continue;}
                foreach ($arr['flows'] as $flow){
                    //skip flow if it sends the packet to controller for a decision
                    if( isset($flow['instructions']['instruction_apply_actions']) &&
                        $flow['instructions']['instruction_apply_actions']['actions']=="output=controller"){continue;}
                    else if(!isset($flow['instructions']['instruction_apply_actions']) ){
                        $no_action = true;    
                    }

                    if(isset($flow['match']['in_port'])) $inport=$flow['match']['in_port'];
                    else $inport='Any';

                    if( isset($flow['instructions']['instruction_apply_actions'])){
                        preg_match('/output=(\S+?)/', $flow['instructions']['instruction_apply_actions']['actions'], $matches);
                        if(isset($matches[1])) $outport = $matches[1];
                    }else $outport = 'None';
                    $graph = $this->addFlowsToGraph($graph, $switchDPID, $inport, $outport, $flow);
                }
            }
            
            return $graph;
        }
        
       
        private function addFlowsToGraph($graph, $switch, $portIn, $portOut, $flow) { 

            //finds each link to match up with the flow
            foreach ($graph['edges'] as $edge) {
                //|| $edge['data']['target']!=$switch || !isset($edge['data']['dst-port'])
                
                //skip edge if not a link
                if($edge['classes']!='link' ){continue;}

                //skip link if not attached to switch
                // if($edge['data']['target']!=$switch && $edge['data']['source']!=$switch) {continue;}

                //if link going "to" switch
                if($edge['data']['target']==$switch){
                    //skip if connection point is neither port in or out
                    if($edge['data']['dst-port']!=$portIn && $edge['data']['dst-port']!=$portOut){continue;}
                    
                    if($edge['data']['dst-port']==$portOut){
                        $graph['edges'][]= array(
                            'data'=>array(
                                'source'=>$switch, 
                                'target'=>$edge['data']['source'],
                                'flow'=>$flow
                                ),
                            'classes'=>'flow'
                        );
                    }
                }else if($edge['data']['source']==$switch){//link going "from"
                    //skip if connection point is neither port in or out
                    if($edge['data']['source-port']!=$portOut && $edge['data']['source-port']!=$portIn){continue;}
                    
                    if($edge['data']['source-port']==$portOut){
                        $graph['edges'][]= array(
                            'data'=>array(
                                'source'=>$switch, 
                                'target'=>$edge['data']['target'],
                                'flow'=>$flow
                                ),
                            'classes'=>'flow'
                        );
                    }
 

                }
                // if( $edge['data']['target']==$switch && $edge['data']['dst-port']==$portIn){
                //     $graph['edges'][]= array(
                //     'data'=>array(
                //         'source'=>$edge['data']['source'], 
                //         'target'=>$switch,
                //         // 'type'=>'flow',
                //         'flow'=>$flow
                //         ),
                //     'classes'=>'flow'
                //     );
                // }else if($edge['data']['dst-port']==$portOut){
                //     $graph['edges'][]= array(
                //     'data'=>array(
                //         'source'=>$switch,
                //         'target'=>$edge['data']['source'], 
                //         // 'type'=>'flow',
                //         'flow'=>$flow
                //         ),
                //     'classes'=>'flow'
                //     );
                // }
            }
            return $graph;
        }
        
        public function getOverview($key) {
            $arr = array();
            $arr['Controller Type'] = 'Floodlight';

            //There seems to be a bug in floodlight where a modules status (forwarding in particular) is incorrect 
            //$arr['Auto Forwarding<BUG>'] = array_key_exists('net.floodlightcontroller.forwarding.Forwarding', $this->getCommand($key, 'loaded_modules'))? 'Enabled':'Disabled';


            //uptime returned as ms
            $ms = $this->getCommand($key, 'controller_uptime')['systemUptimeMsec'];
            //convert ms to sec and convert to days/hours/mins

            get_instance()->load->helper('date');
            $arr['Controller Uptime']= timespan(time()-intval($ms/1000));
            
            
            $arr['# Switches'] = count($this->getCommand($key, 'switch_ids'));
            
            $arr['# Devices'] = 0;
            //ensure that the devices are currently attached to the network
            foreach ($this->getCommand($key, 'all_devices') as $device){
                if(count($device['attachmentPoint'])>0){
                    $arr['# Devices']++;
                }
            }
            
            $arr['# Static Flows'] = count($this->getCommand($key, 'all_static_flows'));
            return $arr;
        }

       /*
		* 	Returns a json representation of all the flows in the network
		*   Indexed by switchDPID
		* 	
        */
        public function getAllFlows($key){
            $allFlows = $this->getCommand($key, 'universal_stat', array('statType'=>'flow'));
            $allFlows = $this->addFlowNames($key, $allFlows);
            return $allFlows;
        }


        public function addFlowNames($key, $all_flows){
            if(!is_array($all_flows)) return $all_flows;
            
            //pull static flow names and pair them with full flow details
            $static_flows = $this->getCommand($key, 'all_static_flows');
            $cookies = array();

            foreach ($static_flows as $switch => $arr) {
                foreach($arr as $i => $flow){
                    foreach ($flow as $flow_name => $flow_details) {
                        log_message('info', print_r('flowname'.$flow_name, true));
                        $cookies[$flow_details['cookie']] = $flow_name;
                    }
                }
            }


            foreach ($all_flows as $switchDPID => $arr) {
                if(!isset($arr['flows'])){continue;}
                foreach ($arr['flows'] as $k => $flow){
                    $cookie = $flow['cookie'];
                    if(isset($cookies[$cookie])) {
                        
                        $all_flows[$switchDPID]['flows'][$k]['name'] = $cookies[$cookie]; 
                        log_message('info', 'cookie match '.print_r($all_flows[$switchDPID]['flows'][$k]['name'], true));
                        
                    }
                }
            }    
            return  $all_flows;
        }

        public function pushFlow($key, $data){
            return $this->postCommand($key, 'add_flow', json_encode($data));
        }        

        public function clearFlows($key){
            return $this->getCommand($key, 'clear_all_static_flows');
        }

        public function removeFlow($key, $flow){
            return $this->deleteCommand($key, 'remove_static_flow', json_encode($flow));
        }

        public function forwarding($key, $option){
        	return $this->getCommand($key, 'forwarding', array('option'=>$option));	
        }        
}