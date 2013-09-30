<?php

class ConfigBase {
    
    public $config;
    public $data;
    
    public function getConfigs(){
        return $this->config;
    }
    
    public function setConfigs(){
        foreach($this->config as $conf){
            call_user_func_array('set_config',$conf);
        }
    }
}
?>