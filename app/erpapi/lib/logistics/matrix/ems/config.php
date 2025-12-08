<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 应用级参数定义
 */
class erpapi_logistics_matrix_ems_config extends erpapi_logistics_matrix_config{
    protected $_to_node_id = '1815770338';
    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function get_query_params($method, $params){
        $emsAccount = explode('|||',$this->__channelObj->channel['shop_id']);
        $query_params = array(
            'sysAccount' => $emsAccount[0], //客户号
            'passWord' => $emsAccount[1], //客户密码
            'to_node_id' => $this->_to_node_id,
            'node_type' => 'ems',
        );
        $pqp = parent::get_query_params($method, $params);
        $query_params = array_merge($pqp, $query_params);
        return $query_params;
    }
}