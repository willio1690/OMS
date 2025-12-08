<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_setup_config{
    private $sample_file;

    function __construct(){
        if(file_exists(ROOT_DIR.'/config/config.php')){
            $this->set_sample_file(ROOT_DIR.'/config/config.php');
        }else{
            $this->set_sample_file(ROOT_DIR.'/app/base/examples/config.php');
        }
    }

    function set_sample_file($file){
        $this->sample_file = $file;
    }

    function write($config){
        $this->sample_file = realpath($this->sample_file);
        kernel::log('Using sample :'.$this->sample_file);

        $envMap = $this->loadEnvMap($this->sample_file);
        // 写入 .env（仅写入 $config 中出现的 key，对应的 env 名 + 传入值）
        $envPairs = array();
        foreach($config as $k => $v){
            $key = strtoupper($k);
            if (!isset($envMap[$key]) || empty($envMap[$key]['env'])) {
                continue;
            }
            $val = $v;
            if (!empty($envMap[$key]['bool'])) {
                $val = (bool)$val;
            }
            $envPairs[$envMap[$key]['env']] = $val;
        }
        $this->writeEnvFile($envPairs, ROOT_DIR.'/config/.env');

        // 拷贝样例文件到 config/config.php，保持示例逻辑（env 优先）
        kernel::log('Writing config file... ok.');
        if(file_put_contents(ROOT_DIR.'/config/config.php', file_get_contents($this->sample_file))){
            $this->write_compat();
            return true;
        }else{
            return false;
        }
    }

    private function loadEnvMap($file)
    {
        // 在隔离作用域中引入，获取 $envMap
        $loader = function($f){
            $envMap = array();
            include $f;
            return isset($envMap) ? $envMap : array();
        };
        $envMap = $loader($file);

        return $envMap;
    }

    private function writeEnvFile(array $envPairs, $target)
    {
        if (empty($envPairs)) {
            return;
        }
        $lines = array();
        foreach ($envPairs as $envName => $val) {
            if ($val === null) continue;
            $lines[] = $envName.'='.$this->envExport($val);
        }
        if (!empty($lines)) {
            kernel::log('Writing env file... ok.');
            file_put_contents($target, implode("\n", $lines)."\n");
        }
    }

    private function envExport($val)
    {
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }
        return (string)$val;
    }

    static function deploy_info(){
        $deploy = file_get_contents(ROOT_DIR.'/config/deploy.xml');
        return kernel::single('base_xml')->xml2array($deploy,'base_deploy');
    }

    function write_compat() 
    {
        $file = ROOT_DIR.'/config/config.php';
        if(file_exists($file)){
            kernel::log('Writing config compat... ok.');
            $sample = preg_replace('/('.preg_quote('/**************** compat functions begin ****************/', '/').')(.*)('.preg_quote('/**************** compat functions end ****************/', '/').')/isU', "\\1" .  "\r\n" . join("\r\n", $this->check_compat()) . "\r\n" . '\\3', file_get_contents($file));
            return file_put_contents($file, $sample);
        }else{
            kernel::log('Writing config compat... failure.');
            return false;
        }
    }//End Function

    function check_compat() 
    {
        $ret = array("#此处程序自动生成，请勿修改\n");
        $ret = array_merge($ret, (array)$this->check_json());   //todo:检查json
        //todo:今后可以加入其它兼容
        return $ret;
    }//End Function

    function check_json() 
    {
        if(!function_exists('json_encode')){
            $ret[] = file_get_contents(dirname(__FILE__) . '/compat/json_encode.txt');
        }
        if(!function_exists('json_decode')){
            $ret[] = file_get_contents(dirname(__FILE__) . '/compat/json_decode.txt');
        }
        return $ret;
    }//End Function
}
