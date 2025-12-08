<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 抽象类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
abstract class erpapi_dealer_request_abstract
{
    /**
     * 渠道
     *
     * @var erpapi_channel_abstract
     **/
    protected $__channelObj;
    protected $__resultObj;
    protected $__caller;

    final public function init(erpapi_channel_abstract $channel, erpapi_config $config, erpapi_result $result)
    {
        $this->__channelObj = $channel;
        
        $this->__resultObj = $result;

        // 默认以JSON格式返回
        $callerObj = new erpapi_caller();
        $this->__caller = $callerObj
                            ->set_config($config)
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
     * 生成唯一键
     *
     * @return void
     * @author 
     **/
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
     * @return Array
     **/


    public function callback($response, $callback_params)
    {
        $rsp             = $response['rsp'];
        //新增发货失败处理
        $errorCode = kernel::single('erpapi_errcode')->getErrcode('shop');//错误码
        $failApiModel = app::get('erpapi')->model('api_fail');
        if($rsp == 'fail' && $response['msg_code'] && array_keys($errorCode) && in_array($response['msg_code'],array_keys($errorCode))){

            if(!$callback_params['obj_type']){
                $callback_params['obj_type'] = $errorCode[$response['msg_code']]['obj_type'];

            }
            $failApiModel->publish_api_fail($callback_params['method'],$callback_params,$response);
        }
        if($rsp == 'succ' || $rsp == 'success' || in_array($response['res'],array('W90010'))){//因成功时需要删除失败列表里记录

            if(in_array($response['res'],array('W90010'))){
                $response['rsp'] = 'succ';
            }
            $failApiModel->publish_api_fail($callback_params['method'],$callback_params,$response);
        }
        return $response;
    }
}