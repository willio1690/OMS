<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_view_helper{

    function __construct(&$app){
        $this->app = $app;
    }
    
    /**
     * function_desktop_header
     * @param mixed $params 参数
     * @param mixed $smarty smarty
     * @return mixed 返回值
     */
    public function function_desktop_header($params, &$smarty){
        return $smarty->fetch('admin/include/header.tpl',$this->app->app_id);
    }

    /**
     * function_desktop_footer
     * @param mixed $params 参数
     * @param mixed $smarty smarty
     * @return mixed 返回值
     */
    public function function_desktop_footer($params, &$smarty){
        $accountsafy = true;

        if ($_SESSION['needChangePassword'] && !$_SESSION['login_trust']){
            $accountsafy = false;
        }

        if (!kernel::single('desktop_user')->get_mobile() && !$_SESSION['login_trust']) {
            $accountsafy = false;
        }
        $smarty->pagedata['accountsafy'] = $accountsafy;

        return $smarty->fetch('admin/include/footer.tpl',$this->app->app_id);
    }

}
