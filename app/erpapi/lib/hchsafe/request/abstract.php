<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class erpapi_hchsafe_request_abstract
{
    /**
     * 渠道
     *
     * @var string
     **/
    protected $__channelObj;

    protected $__resultObj;

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
     * 成功输出
     *
     * @return array
     * @author 
     **/
    final public function succ($msg='', $msgcode='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'msg_code'=>$msgcode, 'data'=>$data);
    }

    /**
     * 失败输出
     *
     * @return array
     * @author 
     **/
    final public function error($msg, $msgcode='', $data=null)
    {
        return array('rsp'=>'fail','msg'=>$msg,'err_msg'=>$msg,'msg_code'=>$msgcode,'data'=>$data);
    }

    /**
     * 生成唯一键
     *
     * @return string
     * @author 
     **/
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
     **/
    public function callback($response, $callback_params){
        return $response;
    }
}