<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_pinjun_config extends erpapi_logistics_matrix_config{

    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get_query_params($method, $params){
        $account = explode('|||',$this->__channelObj->channel['shop_id']);
        

        $node = kernel::single('erpapi_channel_bind')->getNode('pinjun');

        $query_params   = array(
            'pinjun_api_key'    => $account[0],
            'pinjun_api_secret' => $account[1],
            'to_node_id'        => $node['to_node'],
            'node_type'         => 'pinjun',
            'pay_type'          => $account[2],
            'settlement_account'=> $account[3],
        );
        $pqp = parent::get_query_params($method, $params);
        $query_params = array_merge($pqp, $query_params);
 
        return $query_params;
    }
}
