<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * sunjing@shopex.cn
 * @describe 应用级参数定义
 */
class erpapi_logistics_matrix_unionpay_config extends erpapi_logistics_matrix_config{
    
    protected $_to_node_id = '1705101437';
    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function get_query_params($method, $params){
        $account = explode('|||',$this->__channelObj->channel['shop_id']);

        $query_params = array(

            'member_id' =>  $account[0], #
            'member_pwd'  =>  $account[1],    #
            'month_code'    =>  $account[3],    #
            'pay_type'      =>  $account[2],    #,
            'node_type'     => 'ums',
            'to_node_id'    => $this->_to_node_id,
        );
        $pqp = parent::get_query_params($method, $params);
        $query_params = array_merge($pqp, $query_params);
        return $query_params;
    }
}