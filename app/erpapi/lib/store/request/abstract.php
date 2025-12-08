<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店接口请求抽象类
 *
 * @author 
 * @version 0.1
 *
 */
abstract class erpapi_store_request_abstract
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
        $this->__caller = kernel::single('erpapi_caller')
                            ->set_config($config)
                            ->set_channel($channel)
                            ->set_result($result);


    }


    public function call($method,$params,$callback=array(),$title='',$time_out=10,$primary_bn='',$write_log=true,$gateway='',$logData=[]){

        $rs = $this->__caller->call($method,$params,$callback,$title,$time_out,$primary_bn,$write_log,$gateway,$logData);

        if($rs['rsp'] == 'fail' && $rs['msg'] == '用户未登录'){
            //pekon
            $tokenKey = 'pekon_pos_token';
            base_kvstore::instance('erpapi/penkon/token')->store($tokenKey, '', 1);
            $rs = $this->__caller->call($method,$params,$callback,$title,$time_out,$primary_bn,$write_log,$gateway,$logData);
            
        }
        return $rs;

    }

    /**
     * 成功输出
     *
     * @return void
     * @author 
     **/
    final public function succ($msg='', $msgcode='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'msg_code'=>$msgcode, 'data'=>$data);
    }

    /**
     * 失败输出
     *
     * @return void
     * @author 
     **/
    final public function error($msg, $msgcode='', $data=null)
    {
        return array('rsp'=>'fail','msg'=>$msg,'err_msg'=>$msg,'msg_code'=>$msgcode,'data'=>$data);
    }

    /**
     * 生成唯一键
     *
     * @return void
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
     *
     * @return void
     * @author 
     **/
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

        return array('rsp'=>$rsp,'res'=>'', 'msg'=>$msg, 'msg_code'=>$msg_code, 'data'=>$data);
    }
    
    //数组转为xml字符串
    protected function arrayToXml($params){
        $xml = '<?xml version="1.0" encoding="utf-8" ?>';
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