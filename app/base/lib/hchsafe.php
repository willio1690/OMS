<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 淘宝御城河
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class base_hchsafe
{

    /**
     * 登录日志
     *
     * @return void
     * @author 
     **/

    public function login_log($params)
    {
        kernel::single('erpapi_router_request')->set('hchsafe','taobao')->hchsafe_login($params);
        
        kernel::single('erpapi_router_request')->set('hchsafe','360buy')->hchsafe_login($params);
        kernel::single('erpapi_router_request')->set('hchsafe','luban')->hchsafe_login($params);
    }

    /**
     * 风控风险分析
     *
     * @return void
     * @author 
     **/
    public function compute_risk($params,&$msg)
    {
        if (defined('DEV_ENV')) {
            return true;
        }
        
        // IDAAS
        $rs = ['rsp' => 'succ'];
        $idaas_enable = app::get('ome')->getConf('pam.passport.idaas.enable');
        if (defined('IDAAS_LOGIN') && true == constant("IDAAS_LOGIN") && $idaas_enable == 'true') {
            $rs = kernel::single('erpapi_router_request')->set('idaas', 'aliyun')->account_login(array (
                'login_name'     => $params['uname'],
                'login_password' => $params['password_string'],
                'member_id'      => $params['member_id'],
            ));
        }
        if($rs['rsp'] == 'succ'){
            $rs = kernel::single('erpapi_router_request')->set('hchsafe','360buy')->hchsafe_computerisk();
        }

        if ($rs['rsp'] == 'fail') $msg = $rs['msg'];

        return $rs['rsp'] == 'succ' ? true : false;
    }


    /**
     * 订单访问(打印)
     *
     * @return void
     * @author 
     **/
    public function order_log($params)
    {
       
        kernel::single('erpapi_router_request')->set('hchsafe','taobao')->hchsafe_orderdata($params);
        kernel::single('erpapi_router_request')->set('hchsafe','360buy')->hchsafe_orderdata($params);
        kernel::single('erpapi_router_request')->set('hchsafe','luban')->hchsafe_orderdata($params);
    }


    /**
     * 订单访问
     *
     * @return void
     * @author 
     **/
    public function sql_log($params)
    {
        kernel::single('erpapi_router_request')->set('hchsafe','taobao')->hchsafe_sql($params);
    }

    public function order_push_log($params)
    {
        kernel::single('erpapi_router_request')->set('hchsafe','taobao')->hchsafe_orderpush($params);
        kernel::single('erpapi_router_request')->set('hchsafe','360buy')->hchsafe_orderpush($params);
        kernel::single('erpapi_router_request')->set('hchsafe','luban')->hchsafe_orderpush($params);
    }

    public function isVerifyPassed($params) {
        return kernel::single('erpapi_router_request')->set('hchsafe','taobao')->hchsafe_isVerifyPassed($params);
    }
}
