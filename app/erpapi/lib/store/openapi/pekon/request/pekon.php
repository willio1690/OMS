<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 商品同步pos
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_openapi_pekon_request_pekon extends erpapi_store_request_abstract
{

   
    /**
     * pekon_token
     * @return mixed 返回值
     */

    public function pekon_token(){
        $tokenKey = 'pekon_pos_token';
        base_kvstore::instance('erpapi/penkon/token')->fetch($tokenKey, $tokenVal);
       
        if($tokenVal){
            $data = json_decode($tokenVal, true);
            
            //检查有效期
            $expire_time = $data['expire_time'];
            if($expire_time > time()){
                return $data['access_token'];
            }
        }
        
        base_kvstore::instance('erpapi/penkon/token')->store($tokenKey, '', 1);
        $res = $this->__caller->call('get_token', array(), null, 'get token', 30);
        if($res['rsp'] == 'succ'){
            $data = $res['data'];
            $token = $data['token'];
            $expires_in = intval($data['expires_in']);
            
            $cachedata = array(
                'access_token' => $token,
                'expire_time' => time()-3600 + $expires_in,
            );
            base_kvstore::instance('erpapi/penkon/token')->store($tokenKey, json_encode($cachedata), $expires_in -600);
            return $token;
        }
    }
}
