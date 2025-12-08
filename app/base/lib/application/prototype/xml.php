<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class base_application_prototype_xml extends base_application_prototype_content{

    var $current;
    var $xml;
    var $xsd;
    var $path;
    static $__appinfo;

    /**
     * 初始化_iterator
     * @return mixed 返回值
     */
    public function init_iterator() {
        if(defined('CUSTOM_CORE_DIR') && file_exists(CUSTOM_CORE_DIR.'/'.$this->target_app->app_id.'/'.$this->xml)){
            $ident = $this->target_app->app_id.'-'.$this->xml;
            if(!isset(self::$__appinfo[$ident])){
                $custom_file_content = file_get_contents(CUSTOM_CORE_DIR.'/'.$this->target_app->app_id.'/'.$this->xml);
                self::$__appinfo[$ident] = kernel::single('base_xml')->xml2array($custom_file_content,$this->xsd);
            }            
            eval('$array = &self::$__appinfo[$ident]["'.str_replace('/','"]["',$this->path).'"];');
        }elseif(file_exists($this->target_app->app_dir.'/'.$this->xml)){
            $ident = $this->target_app->app_id.'-'.$this->xml;
            if(!isset(self::$__appinfo[$ident])){
                $file_content = file_get_contents($this->target_app->app_dir.'/'.$this->xml);
                self::$__appinfo[$ident] = kernel::single('base_xml')->xml2array($file_content,$this->xsd);
            }
            
            eval('$array = &self::$__appinfo[$ident]["'.str_replace('/','"]["',$this->path).'"];');
        }else{
            $array = array();
        }
        return new ArrayIterator((array)$array);
    }
    
    function last_modified($app_id){
        if(defined('CUSTOM_CORE_DIR') && file_exists(CUSTOM_CORE_DIR.'/'.app::get($app_id)->app_id.'/'.$this->xml)){
            $file = CUSTOM_CORE_DIR.'/'.app::get($app_id)->app_id.'/'.$this->xml;
        }else{
            $file = app::get($app_id)->app_dir.'/'.$this->xml;
        }
        
        if(file_exists($file)){
            //return filemtime($file);
            //todo: md5
            return md5_file($file);
        }else{
            return false;
        }
    }

}
