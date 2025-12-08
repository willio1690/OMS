<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_invoice_request_abstract
{
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
        $callerObj = new erpapi_caller();
        $this->__caller = $callerObj
                            ->set_config($config)
                            ->set_channel($channel)
                            ->set_result($result);
    }

    /**
     * succ
     * @param mixed $msg msg
     * @param mixed $msgcode msgcode
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function succ($msg='', $msgcode='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'msg_code'=>$msgcode, 'data'=>$data);
    }

    /**
     * error
     * @param mixed $msg msg
     * @param mixed $msgcode msgcode
     * @param mixed $data 数据
     * @return mixed 返回值
     */
    final public function error($msg, $msgcode, $data=null)
    {
        return array('rsp'=>'fail','msg'=>$msg,'err_msg'=>$msg,'msg_code'=>$msgcode,'data'=>$data);
    }

    /**
     * uniqid
     * @return mixed 返回值
     */
    final public function uniqid(){
        $microtime  = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval    = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    /**
     * 回调
     * @param $response Array
     * @param $callback_params Array
     * @return array
     * */
    public function callback($response, $callback_params){
        return $response;
    }

    protected $callModeMapping = [];

    /**
     * 获取请求方法的调用模式
     * @param $methodName
     * @return boolean true = 异步, false=同步
     */
    protected function _getCallMode($methodName)
    {
        // 默认同步
        if(!isset($this->callModeMapping[$methodName])){
            return false;
        }

        return $this->callModeMapping[$methodName];
    }

    /**
     * @param string $methodName
     * @param bool $callMode
     * @return void
     */
    protected function _setCallMode(string $methodName, bool $callMode)
    {
        $this->callModeMapping[$methodName] = $callMode;
    }
}