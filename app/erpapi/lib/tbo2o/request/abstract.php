<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
abstract class erpapi_tbo2o_request_abstract
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
        $this->__caller = kernel::single('erpapi_caller')
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
     **/
    public function callback($response, $callback_params){
        
        $rsp = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data = $response['data'];
        $res = $response['res'];
        
        $status = 'fail'; $msg = $err_msg.'('.$res.')';
        if ($rsp == 'succ') {
            $msg = '成功';
            $status = 'success';
        }
        
        //记录失败
        $obj_type = $callback_params['obj_type'];
        $obj_bn = $callback_params['obj_bn'];
        $method = $callback_params['method'];
        $log_id = $callback_params['log_id'];
        
        $failApiModel = app::get('erpapi')->model('api_fail');
        $failApiModel->publish_api_fail($method,$callback_params,$response);
        
        if ($log_id) {
            $logModel = app::get('ome')->model('api_log');
            $logModel->update_log($log_id, $msg, $status, null, null);
        }
        
        return array('rsp'=>$rsp,'res'=>'','msg'=>$msg,'msg_code'=>$msg_code,'data'=>$data);
        
    }
    
    //数组转为xml字符串
    protected function arrayToXml($params){
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= '<request>';
        $xml .= $this->arrayToXmlContent($params);
        $xml .= '</request>';
        return $xml;
    }
    
    private function arrayToXmlContent($params){
        $xml = "";
        foreach ($params as $key=>$val){
            if(is_array($val)){
                if(is_numeric($key)){
                    $xml.= $this->arrayToXmlContent($val);
                }else{
                    $xml.="<".$key.">".$this->arrayToXmlContent($val)."</".$key.">";
                }
            }else{
                $xml.="<".$key.">".$val."</".$key.">";
            }
        }
        return $xml;
    }
    
}