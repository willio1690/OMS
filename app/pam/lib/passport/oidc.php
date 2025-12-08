<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class pam_passport_oidc implements pam_interface_passport
{
    /**
     * 任务登陆标识
     * 
     * @var string
     * */
    public $loginType = 'SSO';

    /**
     * undocumented function
     * 
     * @return void
     * @author 
     * */
    public function __construct()
    {
        
    }

    public function get_name()
    {
        return 'Account Sign In';
    }

    public function get_login_form($auth, $appid, $view, $ext_pagedata = array())
    {
        $oidcInfo = app::get('ome')->getConf('pam.passport.oidc.info');
        $oidctype = $oidcInfo['oidctype'] ? strtoupper($oidcInfo['oidctype']) : '第三方';

        $str = '';
        $rs = kernel::single('erpapi_router_request')->set('account','account')->user_loginUrl([]);
        if($rs['rsp'] == 'succ') {
            $str = '<a href="'.$rs['data'].'">'.$oidctype.'信任登陆</a>';
        }
        return $str;
    }

    public function login($auth, &$usrdata)
    {

    }

        /**
     * callback
     * @return mixed 返回值
     */
    public function callback() {
        $sdf = ['code'=>$_GET['code']];

        $_GOTO = kernel::base_url(1).'/index.php?ctl=passport';

        if(empty($_GET['code'])) {
            die('<script>location="'.$_GOTO.'";</script>');
        }

        kernel::single('base_session')->start();
        $rs = kernel::single('erpapi_router_request')->set('account','account')->user_login($sdf);
        if($rs['rsp'] == 'succ') {
            die('<script>location="'.kernel::get_host_url().'";</script>');
        }

        header('Content-Type:text/html; charset=utf-8');
        die('登录失败:' . $rs['msg'].'<script>setTimeout(function(){location='.$_GOTO.';},3000);</script>');
    }

    /**
     * loginout
     * @param mixed $auth auth
     * @param mixed $backurl backurl
     * @return mixed 返回值
     */
    public function loginout($auth, $backurl = "index.php")
    {
        unset($_SESSION['account'][$auth->type]);
        unset($_SESSION['last_error']);
        $rs = kernel::single('erpapi_router_request')->set('account','account')->user_loginOutUrl([]);
        if($rs['rsp'] == 'succ') {
            kernel::single('base_session')->destory();

            die('<script>location="'.$rs['data'].'";</script>');
        }
    }

    /**
     * 获取_data
     * @return mixed 返回结果
     */
    public function get_data()
    {
    }

    /**
     * 获取_id
     * @return mixed 返回结果
     */
    public function get_id()
    {
    }

    /**
     * 获取_expired
     * @return mixed 返回结果
     */
    public function get_expired()
    {
    }

    /**
     * 获取_config
     * @return mixed 返回结果
     */
    public function get_config()
    {
        $oidc = app::get('ome')->getConf('pam.passport.oidc.enable');
        if($oidc != 'true') {
            return [];
        }
        $ret = [];
        $ret['shopadmin_passport_status']['value'] = 'true';
        return $ret;
    }

}
