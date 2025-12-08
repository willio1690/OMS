<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class erpapi_front_response_abstract
{
    public $__channelObj;

    public $__apilog;

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app)
    {
        kernel::single('base_session')->start();

        // 检查是否登陆状态
        $auth    = pam_auth::instance(pam_account::get_account_type('desktop'));
        $account = $auth->account();

        if ($_REQUEST['method'] != 'front.user.login' && !$account->is_valid()) {
            throw new Exception("Invalid Session");
        }
    }

    /**
     * __destruct
     * @return mixed 返回值
     */
    public function __destruct()
    {
        kernel::single('base_session')->close();
    }

    /**
     * 初始化
     * @param erpapi_channel_abstract $channel channel
     * @return mixed 返回值
     */
    public function init(erpapi_channel_abstract $channel)
    {
        $this->__channelObj = $channel;

        return $this;
    }

    /**
     * 去首尾空格
     *
     * @param Array
     * @return Array
     * @author
     **/
    public static function trim(&$arr)
    {
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                self::trim($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }
        }
    }

    /**
     * 过滤空
     *
     * @return void
     * @author
     **/
    public function filter_null($var)
    {
        return !is_null($var) && $var !== '';
    }

    /**
     * 比较数组值
     *
     * @return void
     * @author
     **/
    public function comp_array_value($a, $b)
    {
        if ($a == $b) {
            return 0;
        }

        return $a > $b ? 1 : -1;
    }
}
