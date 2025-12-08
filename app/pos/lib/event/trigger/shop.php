<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class pos_event_trigger_shop
{
    /**
     * 
     * 店铺同步
     * @param 
     */
    public function add($store_id)
    {

        
        $storeMdl = app::get('o2o')->model('store');

        $stores = $storeMdl->dump(array('store_id'=>$store_id),'*');
        $area = $stores['area'] ? explode(':',$stores['area']) : '';
        $params = array(
            'store_bn'      =>  $stores['store_bn'],
            'name'          =>  $stores['name'],
            'addr'          =>  $stores['addr'],
            'mobile'        =>  $stores['mobile'],
            'create_time'   =>  $stores['create_time'],
            'store_sort'    =>  $stores['store_sort'],
            'area'          =>  $area ? $area[1] : '',
        );

        $channel_type = 'store';
        $channel_id = $store_id;

        $result = kernel::single('erpapi_router_request')->set($channel_type,$channel_id)->shop_add($params);
        if($result['rsp'] == 'succ'){
            $updateData = array(
                'sync_status'=>'1',

            );
            $rs = [true,'成功'];
        }else{
            $updateData = array(
                'sync_status'=>'2',
                'sync_msg'   => $result['msg'],
            );
            $rs = [false,'失败'];
        }
        $storeMdl->update($updateData,array('store_id'=>$store_id));
        return $rs;
    }
    

    /**
     * pekon_token
     * @return mixed 返回值
     */
    public function pekon_token(){
        $tokenKey = 'pekon_pos_token';
        base_kvstore::instance('erpapi')->fetch($tokenKey, $tokenVal);
        if($tokenVal){
            $data = json_decode($tokenVal, true);
            
            //检查有效期
            $expire_time = $data['expire_time'];
            if($expire_time > time()){
                return $data['access_token'];
            }
        }
        
        base_kvstore::instance('erpapi')->store($tokenKey, '', 1);

        $query_params = [
            'username'          => defined('PEKON_USER') ? constant('PEKON_USER') : '',
            'password'          => defined('PEKON_PWD') ? constant('PEKON_PWD') : '',
            'tenant'            => defined('PEKON_TENANT') ? constant('PEKON_TENANT') : '',
            'noOrg'             =>  'Yes',
            
        ];
        $servers = $this->getServer();

        $url = $servers['config']['api_url'].'/login';
        $headers = [
                'Content-Type' => 'application/json',
            ];
        $core_http = kernel::single('base_httpclient');
        $query_params = json_encode($query_params);
        $res = $core_http->set_timeout(10)->post($url, $query_params, $headers);
        if($res){
            $res = json_decode($res,true);

            if($res['code'] == '10000'){
                $data = $res['data'];

                $token = $data['token'];
                $expires_in = intval($data['expires_in']);
                
                $cachedata = array(
                    'access_token' => $token,
                    'expire_time' => time() + $expires_in,
                );
                base_kvstore::instance('erpapi')->store($tokenKey, json_encode($cachedata), $expires_in -600);
                return $token;
            }
        }
        
    }

    /**
     * 获取Server
     * @return mixed 返回结果
     */
    public function getServer(){
        $serverObj = app::get('o2o')->model('server');
        $servers = $serverObj->dump(array('node_type'=>'pekon'),'config,server_id');
        $config = unserialize($servers['config']);
        $servers['config'] = $config;
        return $servers;

    }
  
}
