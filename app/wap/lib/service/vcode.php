<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wap_service_vcode{
    
    function __construct($app){
        $this->app = $app;
    }
    function status(){
        if($_SESSION['error_count'][$this->app->app_id] >= 3) return true;
        return app::get('desktop')->getConf('shopadminVcode') == 'true' ? true : false;
    }
}
?>