<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_router implements base_interface_router{

    function __construct($app){
        $this->app = $app;
    }

    function gen_url($params=array(),$full=false){
        $params = utils::http_build_query($params);
        if($params){
            return $this->app->base_url($full).'index.php?'.$params;
        }else{
            return $this->app->base_url($full);
        }
    }

    function dispatch($query){
        $_GET['ctl'] = $_GET['ctl']?$_GET['ctl']:'default';
        $_GET['act'] = $_GET['act']?$_GET['act']:'index';
        $_GET['app'] = $_GET['app']?$_GET['app']:'desktop';
        $query_args = $_GET['p'];

        $controller = app::get($_GET['app'])->controller($_GET['ctl']);
        $arrMethods = get_class_methods($controller);
        if (in_array($_GET['act'], $arrMethods))
            call_user_func_array(array(&$controller,$_GET['act']),(array)$query_args);
        else
            call_user_func_array(array(&$controller,'index'),(array)$query_args);
    }
    
    /**
     * 生成菜单唯一ID finder_vid
     * @Author: xueding
     * @Vsersion: 2022/9/8 上午10:38
     * @param $path
     * @return false|string
     */
    public static function getFinderVid($path)
    {
        // if (stripos($path,'?')) {
        //     $path = mb_substr($path,stripos($path,'?')+1);
        // }

        $path = stripos($path,'?') ? parse_url($path, PHP_URL_QUERY) : $path;

        parse_str($path, $pathArr);
        
        // if (isset($pathArr['view'])) {
        //     unset($pathArr['view']);
        // }

        unset($pathArr['finder_vid'],$pathArr['finder_id'],$pathArr['_finder'],$pathArr['view']);

        
        ksort($pathArr);
        
        $newUrl = http_build_query($pathArr);

        return substr(md5($newUrl), 5, 6);
    }

}
