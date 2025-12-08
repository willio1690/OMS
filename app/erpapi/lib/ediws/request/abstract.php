<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class erpapi_ediws_request_abstract
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


    public function call($method,$params,$callback=array(),$title='',$time_out=10,$primary_bn='',$write_log=true,$gateway='',$logData=[]){

        $rs = $this->__caller->call($method,$params,$callback,$title,$time_out,$primary_bn,$write_log,$gateway,$logData);

        if($rs['responseCode']=='401'){//授权问题时重新请求
            $username = $this->__channelObj->edi['config']['ediwsuser'];
            $tokenKey = 'edi_'.$username;
            cachecore::store($tokenKey,'');
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
     * 失败输出
     *
     * @return void
     * @author 
     **/
    /**
     * error
     * @param mixed $msg msg
     * @param mixed $msgcode msgcode
     * @param mixed $data 数据
     * @return mixed 返回值
     */
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
?>