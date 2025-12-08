<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_hchsafe_request_hchsafe extends erpapi_hchsafe_request_abstract{

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        $this->__mq_config = $GLOBALS['_MQ_HCHSAFE_CONFIG'];

        $this->__remote_addr = base_request::get_remote_addr(); 
        if (!$this->__remote_addr) $this->__remote_addr = kernel::single('base_component_request')->get_server('SERVER_ADDR');
        if (!$this->__remote_addr) $this->__remote_addr = '127.0.0.1';

        $this->__ati = kernel::single('base_component_request')->get_cookie('_ati');

        $this->__host = base_request::get_host();

        $this->__url = strtolower(base_request::get_schema()) . '://' . base_request::get_host() . base_request::get_request_uri();

        $this->__prefixUname = base_shopnode::node_id('ome') . ':';

        $this->__account = kernel::single('desktop_user')->get_login_name();

        $this->__ip = kernel::single('base_component_request')->get_remote_ip(true);
    }

    /**
     * 是否已配置MQ
     *
     * @return void
     * @author 
     **/
    protected function __is_config_mq()
    {
        $hcsafe_conf = app::get('ome')->getConf('ome.hcsafe.config');

        if ($hcsafe_conf=='true' || $hcsafe_conf==''){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 登录日志
     *
     * @return void
     * @author 
     **/
    public function login($sdf)
    {
        return $this->succ();
    }

    /**
     * 订单访问数据
     *
     * @return void
     * @author 
     **/
    public function orderdata($sdf)
    {
        return $this->succ();
    }

    /**
     * SQL
     *
     * @return void
     * @author 
     **/
    public function sql($sdf)
    {
        return $this->succ();
    }

    /**
     * 订单推送第三方
     *
     * @return void
     * @author 
     **/
    public function orderpush($sdf)
    {
        return $this->succ();
    }
}