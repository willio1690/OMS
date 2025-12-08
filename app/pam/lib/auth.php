<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class pam_auth
{

    private $account;
    static $instance = array();

    /**
     * 是否验证码
     * 
     * @var string
     * */
    private $enable_vcode = true;

        /**
     * __construct
     * @param mixed $type type
     * @return mixed 返回值
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    public static function instance($type)
    {
        if (!isset(self::$instance[$type])) {
            self::$instance[$type] = new pam_auth($type);
        }
        return self::$instance[$type];
    }

    /**
     * 设置_appid
     * @param mixed $appid ID
     * @return mixed 返回操作结果
     */
    public function set_appid($appid)
    {
        $this->appid = $appid;
    }

    /**
     * 设置_enable_vcode
     * @param mixed $enable_vcode enable_vcode
     * @return mixed 返回操作结果
     */
    public function set_enable_vcode($enable_vcode)
    {
        $this->enable_vcode = $enable_vcode;
    }

    /**
     * account
     * @return mixed 返回值
     */
    public function account()
    {
        if (!$this->account) {
            $this->account = new pam_account($this->type);
        }
        return $this->account;
    }

    /**
     * 获取_name
     * @param mixed $module module
     * @return mixed 返回结果
     */
    public function get_name($module)
    {
        return app::get('pam')->getConf('module.name.' . $module);
    }

    /**
     * is_module_valid
     * @param mixed $module module
     * @param mixed $app_id ID
     * @return mixed 返回值
     */
    public function is_module_valid($module, $app_id = 'b2c')
    {
        $obj    = kernel::single($module);
        $config = $obj->get_config();
        $type   = $app_id === 'desktop' ? 'shopadmin' : 'site';
        return $config[$type . '_passport_status']['value'] == 'true' ? true : false;
    }

    /**
     * 获取_callback_url
     * @param mixed $module module
     * @return mixed 返回结果
     */
    public function get_callback_url($module)
    {
        return kernel::openapi_url('openapi.pam_callback', 'login', array('module' => $module, 'type' => $this->type, 'appid' => $this->appid, 'redirect' => $this->redirect_url));
    }

    /**
     * 设置_redirect_url
     * @param mixed $url url
     * @return mixed 返回操作结果
     */
    public function set_redirect_url($url)
    {
        $this->redirect_url = $url;
    }

    /**
     * is_enable_vcode
     * @return mixed 返回值
     */
    public function is_enable_vcode()
    {
        if (!class_exists($this->appid . '_service_vcode')) {
            return false;
        }

        if ($this->enable_vcode === false) {
            return false;
        }

        $vcode = kernel::single($this->appid . '_service_vcode');
        return $vcode->status();
        // return false;
    }

}
