<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 导出数据存储本地文件类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */
class taskmgr_cache_filesystem extends taskmgr_cache_abstract implements taskmgr_cache_interface{

    static private $_cacheObj = null;

    private $_header = '<?php exit();?>';

    private $_path = 0;

    private $_header_length = 0;

    function __construct($path)
    {
        $this->_path = is_object($path) ? 'object' : $path;
        $this->_header_length = strlen($this->_header);
    }

    /**
     * fetch
     * @param mixed $key key
     * @param mixed $result result
     * @return mixed 返回值
     */

    public function fetch($key, &$result) 
    {
        $file = $this->get_save_file($key);
        if(file_exists($file)){
            $data = unserialize(substr(file_get_contents($file),$this->_header_length));
            if($data['ttl'] > 0 && ($data['dateline']+$data['ttl']) < time()){
                return false;
            }
            $result = $data['value'];
            return true;
        }
        return false;
    }

    /**
     * store
     * @param mixed $key key
     * @param mixed $value value
     * @param mixed $ttl ttl
     * @return mixed 返回值
     */
    public function store($key, $value, $ttl=0) 
    {
        $this->check_dir();
        $data = array();
        $data['value'] = $value;
        $data['ttl'] = $ttl;
        $data['dateline'] = time();
        $org_file = $this->get_save_file($key);
        $tmp_file = $org_file . '.' . str_replace(' ', '.', microtime()) . '.' . mt_rand();
        if(file_put_contents($tmp_file, $this->_header.serialize($data))){
            if(copy($tmp_file, $org_file)){
                @chmod($org_file,0666);
                @unlink($tmp_file);
                return true;
            }
        }
        return false;
    }
    
    /**
     * 删除
     * @param mixed $key key
     * @return mixed 返回值
     */
    public function delete($key) 
    {
        $file = $this->get_save_file($key);
        if(file_exists($file)){
            return @unlink($file);
        }
        return false;
    }

    /**
     * increment
     * @param mixed $key key
     * @param mixed $value value
     * @return mixed 返回值
     */
    public function increment($key, $value=1)
    {
        $file = $this->get_save_file($key);

        $rhandle = fopen($file,'rb+');
        flock($rhandle, LOCK_EX);

        $old_cnts = fread($rhandle, filesize($file));
        $data = unserialize(substr($old_cnts,$this->_header_length));
        if($data){
            $data['value'] = $data['value'] + $value;

            ftruncate($rhandle, 0);
            rewind($rhandle);
            fwrite($rhandle, $this->_header.serialize($data));
        }

        flock($rhandle, LOCK_UN);
        fclose($rhandle);

        return true;
    }

    private function check_dir() 
    {
        if(!is_dir(DATA_DIR.'/export/cache/'.$this->_path)){
            utils::mkdir_p(DATA_DIR.'/export/cache/'.$this->_path);
        }
    }

    private function get_save_file($key)
    {
        $key = $this->create_key($key);
        return DATA_DIR.'/export/cache/'.$this->_path.'/'.$key.'.php';
    }
}
