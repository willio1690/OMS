<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class inventorydepth_view_helper{

    public function __construct(&$app){
        $this->app = $app;
    }
    
    public function function_desktop_header($params, &$smarty){
       
        return $smarty->fetch('admin/include/header.tpl',$this->app->app_id);
    }

    public function function_desktop_footer($params, &$smarty){
    }
}
