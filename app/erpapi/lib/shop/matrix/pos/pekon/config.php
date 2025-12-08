<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_pos_pekon_config extends erpapi_shop_matrix_pos_config
{
    /**
     * gen_sign
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function gen_sign($params){
        //暂时往前端打
        $node_type = $this->__channelObj->channel['node_type'];
        $servers = $this->getServer($node_type);

        if($params['task']) {
            unset($params['task']);
        }
        if($params['callback_url']) {
            unset($params['callback_url']);
        }
        $private_key = $servers['config']['private_key'];

        return strtoupper(md5(strtoupper(md5(self::assemble($params))).$private_key));
    }
    

    /**
     * 获取_url
     * @param mixed $method method
     * @param mixed $params 参数
     * @param mixed $realtime realtime
     * @return mixed 返回结果
     */
    public function get_url($method, $params, $realtime){
        $node_type = $this->__channelObj->channel['node_type'];
        $servers = $this->getServer($node_type);
        $url = $servers['config']['api_url'];
        $url.='/gateway/token/v1/request';
        return $url;
    }

    private function getServer($node_type){

        $serverObj = app::get('o2o')->model('server');
        $store = $serverObj->dump(array('node_type'=>$node_type),'config');

        if (!$store) {
            return false;
        }
        $store['config'] = @unserialize($store['config']);

        return $store;
    }

    

    /**
     * format
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function format($params)
    {
        unset($params['callback_url']);unset($params['sign']);
        return json_encode($params, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get_query_params($method, $params)
    {
       
        $shop_id =  $this->__channelObj->channel['shop_id'];
        $stores = app::get('o2o')->model('store')->db_dump(array('shop_id'=>$shop_id),'store_id');
        $store_id = $stores['store_id'];
        $token = kernel::single('erpapi_router_request')->set('store',$store_id)->pekon_token();
       
        //$token = kernel::single('pos_event_trigger_shop')->pekon_token();

        $query_params = [
            'api'           => $method,
            'orgClientCode' => PEKON_ORGCLIENTCODE,

            'headers'       => [
                'Content-Type' => 'application/json',
                'ssoSessionId' => $token, 
            ],
        ];
        return $query_params;
    }
}