<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_ediws_config extends erpapi_config
{
   
    
   
    /**
     * gen_sign
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function gen_sign($params){
        return ;
    }

    /**
     * 获取_url
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $realtime realtime
     * @return mixed 返回结果
     */
    public function get_url($method, $params, $realtime) {

        $url = 'https://ediws.jd.com/';

        if($method=='edi.request.accountpayable.getlist'){
            $url = $url.'services/accountPayableService/queryAccountPayableInfo';
        }elseif($method=='edi.request.accountorders.getlist'){
            $url = $url.'services/accountPayableService/accountDetailRealTimeSalesSettlement';
        }elseif($method=='edi.request.accountsettlement.getlist'){
            $url = $url.'services/settlement/querySettlementInfo';

        }elseif($method=='edi.request.shippackage.getlist'){

            $url = $url.'services/sparePartInventory/shipPackageInfos';
        }elseif($method=='edi.request.shippackage.detail'){

            $url = $url.'services/sparePartInventory/fashion/shipPackageDetail';
        }elseif($method=='edi.request.reship.query'){
            $url = $url.'services/ro/masterRoQuery';
        }elseif($method=='edi.request.refundinfo.getlist'){
            $url = $url.'services/sparePartInventory/refundInfos';
        }elseif($method=='edi.request.refundinfo.detail'){
            $url = $url.'services/sparePartInventory/fashion/refundInfoDetail';
        }
       
        return $url;
    }

    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get_query_params($method, $params)
    {
        $token    = $this->__getToken();

        $headers = array(
            'Content-Type' => 'application/json',
         
            'Authorization' =>  'Bearer ' . $token,

        );


        if(in_array($method,array('edi.request.accountsettlement.getlist','edi.request.reship.query','edi.request.refundinfo.detail','edi.request.shippackage.detail'))){
            $headers['X-API-VERSION'] = '2.0';
           
        }
        $params['headers'] = $headers;
      
        return $params;
    }
    

    /**
     * __getToken
     * @return mixed 返回值
     */
    public function __getToken()
    {
        
        $username = $this->__channelObj->edi['config']['ediwsuser'];

        $pwd = $this->__channelObj->edi['config']['ediwspwd'];
        $tokenKey = 'edi_'.$username;
        $token = cachecore::fetch($tokenKey);

        if($token){
            return $token;
        }

        $pwd = urlencode(base64_encode(sha1($pwd,true)));
        
         $query_params = array(
            'username'          => $username,
            'password'          => $pwd,

         );
            
        $url = 'https://ediws.jd.com/services/auth/user?';

        $headers = array(
            'Content-Type' => "application/json; charset=UTF-8",
           
        );
        $httpLib = kernel::single('base_httpclient');
        foreach ($query_params as $k => $v){
            $arg[] = $k.'='.$v;
        }
        $url .= implode('&',$arg);
       
        $params = array();

        $res = $httpLib->get($url, $headers);
        
        if(!$res){
            throw new Exception("token请求错误");
        }

        $res = json_decode($res,1);

        if(!$res || $res['result']!='Success' || !$res['token']){
            $errMsg = sprintf('token请求错误,错误代码:%s,错误详情:%s', $res['code'], $res['message']);
            throw new Exception($errMsg);
        }
        
        $token = $res['token'];
        cachecore::store($tokenKey, $token, $res['data']['ttl']-600);
        return $token;
    }

    /**
     * format
     * @param mixed $query_params 参数
     * @return mixed 返回值
     */
    public function format($query_params){
        unset($query_params['method']);
        
        //过滤同步日志单据号
        unset($query_params['original_bn'],$query_params['sign']);
        
        return json_encode($query_params);
    }
    
}