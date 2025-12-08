<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_logistics_matrix_jdgxd_config extends erpapi_logistics_matrix_config
{
    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function get_query_params($method, $params)
    {
        $jdgxd        = explode('|||', $this->__channelObj->channel['shop_id']);
        $shop         = app::get('ome')->model('shop')->dump(array('shop_id' => $jdgxd[0]), 'node_type,node_id');
        $query_params = array(
            'cp_code'    => $this->__channelObj->channel['logistics_code'],
            'to_node_id' => $shop['node_id'],
            'node_type'  => $shop['node_type'],
        );
        
        $pqp          = parent::get_query_params($method, $params);
        $query_params = array_merge($pqp, $query_params);
        return $query_params;
    }
}