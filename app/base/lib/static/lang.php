<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class lang  
{
    static private $_langPack = array();

    /*
     * 初始化语言文件包
     * @var object $app
     * @access public
     * @return mixed
     */

    static public function init_pack($app) 
    {
        $current_lang = kernel::get_lang();
        $lang_resource = $app->lang_resource ?: [];
        if(isset($lang_resource[$current_lang]) && is_array($lang_resource[$current_lang]) && in_array('config.php', $lang_resource[$current_lang])){
            self::$_langPack[$app->app_id] = (array)@include($app->lang_dir . '/' . $current_lang . '/config.php');
        }elseif(isset($lang_resource['zh-cn']) && is_array($lang_resource['zh-cn']) && in_array('config.php', $lang_resource['zh-cn'])){
            self::$_langPack[$app->app_id] = (array)@include($app->lang_dir . '/zh-cn/config.php');
        }else{
            //trigger_error('language pack is lost in '.$this->app_id, E_USER_ERROR);
            self::$_langPack[$app->app_id] = array();
        }
    }//End Function

    /*
     * 取得语言文件信息
     * @var object $app
     * @var string $res
     * @var string $key
     * @access public
     * @return mixed
     */
    /**
     * 获取_info
     * @param mixed $app_id ID
     * @param mixed $res res
     * @param mixed $key key
     * @return mixed 返回结果
     */
    static public function get_info($app_id, $res=null, $key=null) 
    {
        if(!isset(self::$_langPack[$app_id])){
            self::init_pack(app::get($app_id));
        }//验证存在

        if (is_null($res)){
            return self::$_langPack[$app_id];
        }

        if (is_null($key)){
            return self::$_langPack[$app_id][$res];
        }

        if (isset(self::$_langPack[$app_id][$res]) && is_array(self::$_langPack[$app_id][$res])){
            return self::$_langPack[$app_id][$res][$key];
        }

        return [];
    }//End Function

    /**
     * 设置_res
     * @param mixed $app_id ID
     * @param mixed $res res
     * @return mixed 返回操作结果
     */
    static public function set_res($app_id, $res) 
    {
        $app_res = (array)self::get_res($app_id);
        $app_res = array_merge($app_res, (array)$res);
        return base_kvstore::instance('lang/'.$app_id)->store('res', $app_res);
    }//End Function

    /**
     * 获取_res
     * @param mixed $app_id ID
     * @return mixed 返回结果
     */
    static public function get_res($app_id) 
    {
        if(base_kvstore::instance('lang/'.$app_id)->fetch('res', $app_res)){
            return $app_res;
        }else{
            return array();
        }
    }//End Function

    /**
     * del_res
     * @param mixed $app_id ID
     * @return mixed 返回值
     */
    static public function del_res($app_id) 
    {
        return base_kvstore::instance('lang/'.$app_id)->store('res', array());
    }//End Function

}//End Class