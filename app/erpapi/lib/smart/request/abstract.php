<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * smart请求抽象类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.23
 */
abstract class erpapi_smart_request_abstract
{
    //渠道
    protected $__channelObj;
    
    //结果
    protected $__resultObj;
    
    final public function init(erpapi_channel_abstract $channel, erpapi_config $config, erpapi_result $result)
    {
        $this->__channelObj = $channel;
        
        $this->__resultObj = $result;
        
        // 默认以JSON格式返回
        $this->__caller = kernel::single('erpapi_caller')->set_config($config)->set_result($result);
    }
    
    /**
     * 成功输出
     *
     * @param $msg
     * @param $msgcode
     * @param $data
     * @return array
     */
    final public function succ($msg='', $msgcode='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'msg_code'=>$msgcode, 'data'=>$data);
    }
    
    /**
     * 失败输出
     *
     * @param $msg
     * @param $msgcode
     * @param $data
     * @return array
     */
    final public function error($msg, $msgcode='', $data=null)
    {
        return array('rsp'=>'fail','msg'=>$msg,'err_msg'=>$msg,'msg_code'=>$msgcode,'data'=>$data);
    }
    
    /**
     * 生成唯一键
     *
     * @return string
     */
    final public function uniqid()
    {
        $microtime  = utils::microtime();
        $unique_key = str_replace('.','',strval($microtime));
        $randval    = uniqid('', true);
        $unique_key .= strval($randval);
        
        return md5($unique_key);
    }
    
    /**
     * 回调方法
     *
     * @param $response
     * @param $callback_params
     * @return array
     */
    public function callback($response, $callback_params)
    {
        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'];
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];
        
        $status = 'fail'; $msg = $err_msg.'('.$res.')';
        if ($rsp == 'succ') {
            $msg = '成功';
            $status = 'success';    
        }
        
        // 记录失败
        $obj_type = $callback_params['obj_type'];
        $obj_bn   = $callback_params['obj_bn'];
        $method   = $callback_params['method'];
        $log_id   = $callback_params['log_id'];
        
        $failApiModel = app::get('erpapi')->model('api_fail');
        $failApiModel->publish_api_fail($method,$callback_params,$response);
        
        if ($log_id) {
            $logModel = app::get('ome')->model('api_log');
            $logModel->update_log($log_id, $msg, $status, null, null);
        }
        
        return array('rsp'=>$rsp,'res'=>'', 'msg'=>$msg, 'msg_code'=>$msg_id, 'data'=>$data);
    }
}