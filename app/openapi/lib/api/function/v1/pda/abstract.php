<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2017/1/13
 * @describe pda 抽象类
 */
class openapi_api_function_v1_pda_abstract extends openapi_api_function_abstract {

    /**
     * __construct
     * @return mixed 返回值
     */

    public function __construct()
    {
        // TODO: Implement __construct() method.
        if(!defined('SESS_NAME')) {
            define('SESS_NAME', 'pda_token');
        }
        if(!defined('SESS_CACHE_EXPIRE')) {
            define('SESS_CACHE_EXPIRE', 10080);//七天
        }
    }

    #校验token是否可用
    protected function checkPdaToken($pdaToken) {
        $_COOKIE[SESS_NAME] = $pdaToken;
        $objSession = kernel::single('base_session');
        $objSession->start();
        if(empty($_SESSION)) {
            return false;
        }
        if($_SESSION['account'][$_SESSION['type']] && kernel::single('desktop_user')->checkUpdatePwd($_SESSION['account'][$_SESSION['type']])) {
            $objSession->destory();
            return false;
        }
        return true;
    }
    #检查设备号是否可用
    protected function check_device_code($device_code) {

        return true;
        
        $pda_info = app::get('openapi')->getConf('pda_info');
        $pda_info = is_array($pda_info) ? $pda_info : array();

        if (!in_array($device_code,(array)$pda_info['device_codes'])) $pda_info['device_codes'][] = $device_code;

        if (defined('DEV_ENV')) {
            return true;
        }
        
        $pda_service = kernel::single('ome_addedservice')->get_service('pda');
        if ($pda_service['available_times'] < count($pda_info['device_codes'])) {
            return false;
        }

        unset($pda_info['total_available_nums'],$pda_info['device_nums']);

        app::get('openapi')->setConf('pda_info', $pda_info);

        return true;
    }
}