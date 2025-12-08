<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_yilianyun_request_abstract
{
    protected $default_channel = 'yilianyun';

    /**
     * 渠道
     *
     * @var string
     **/
    protected $__channelObj;

    protected $__resultObj;

    /**
     * 初始化
     * @param erpapi_channel_abstract $channel channel
     * @param erpapi_config $config 配置
     * @param erpapi_result $result result
     * @return mixed 返回值
     */
    final public function init(erpapi_channel_abstract $channel, erpapi_config $config, erpapi_result $result)
    {
        $this->__channelObj = $channel;

        $this->__resultObj = $result;

        // 默认以JSON格式返回
        $this->__caller = kernel::single('erpapi_caller', array('uniqid' => uniqid('yilianyun')))
            ->set_config($config)
            ->set_result($result);
    }

    /**
     * succ
     * @param mixed $msg msg
     * @param mixed $msgcode msgcode
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function succ($msg = '', $msgcode = '', $data = null)
    {
        return array('rsp' => 'succ', 'msg' => $msg, 'code' => $msgcode, 'data' => $data);
    }

    /**
     * error
     * @param mixed $msg msg
     * @param mixed $msgcode msgcode
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function error($msg, $msgcode = '', $data = null)
    {
        return array('rsp' => 'fail', 'msg' => $msg, 'err_msg' => $msg, 'code' => $msgcode, 'data' => $data);
    }

    /**
     * 回调
     * @param $response Array
     * @param $callback_params Array
     * @return array
     **/
    public function callback($response, $callback_params)
    {
        return $response;
    }
}