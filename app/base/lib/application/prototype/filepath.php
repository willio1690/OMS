<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_application_prototype_filepath extends base_application_prototype_content{

    var $current;
    var $path;
    private $_mtime = 0;
    protected $_hadFileName = array();

    function init_iterator(){
        $this->_hadFileName = array();
        if(is_dir($this->target_app->app_dir.'/'.$this->path)){
            $appendIterator = new AppendIterator();
            if(defined('CUSTOM_CORE_DIR') && is_dir(CUSTOM_CORE_DIR.'/'.$this->target_app->app_id.'/'.$this->path)){
                 $this->_mtime = filemtime(CUSTOM_CORE_DIR.'/'.$this->target_app->app_id.'/'.$this->path);
                 $cdi = new DirectoryIterator(CUSTOM_CORE_DIR.'/'.$this->target_app->app_id.'/'.$this->path);
                 $appendIterator->append($cdi);
            }else{
                 $this->_mtime = filemtime($this->target_app->app_dir.'/'.$this->path);
            }
            $di = new DirectoryIterator($this->target_app->app_dir.'/'.$this->path);
            $appendIterator->append($di);
            return $appendIterator;
        }else{
            return new ArrayIterator(array());
        }
    }

    /**
     * 获取Pathname
     * @return mixed 返回结果
     */
    public function getPathname(){
        return $this->iterator()->getPathname();
    }

    /**
     * current
     * @return mixed 返回值
     */
    public function current() {
        $this->key = $this->iterator()->getFilename();
        return $this;
    }

    function prototype_filter(){
        $filename = $this->iterator()->getFilename();
        if(in_array($filename, $this->_hadFileName)) {
            return false;
        }
        if($filename[0]=='.'){
            return false;
        }else{
            $this->_hadFileName[] = $filename;
            return $this->filter();
        }
    }
    
    function last_modified($app_id){
        $info_arr = array();
        foreach($this->detect($app_id) as $item){
            //$modified = max($modified,filemtime($this->getPathname()));
            //todo: md5
            $filename = $this->getPathname();
            if(is_dir($filename)){
                foreach(utils::tree($filename) AS $k=>$v){
					if (is_dir($v)) continue;
                    $info_arr[$v] = md5_file($v);
                }
            }else{
                $info_arr[$filename] = md5_file($filename);
            }
        }
        ksort($info_arr);
        return md5(serialize($info_arr));
    }



}
