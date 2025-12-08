<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-12-15
 * @describe 物流单配置
 */
class erpapi_logistics_config extends erpapi_config{

    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function get_query_params($method, $params) {
        $query_params = array(
            'app_id' => 'ecos.ome',
            'method' => $method,
            'date' => date('Y-m-d H:i:s'),
            'format' => 'json',
            'certi_id' => base_certificate::certi_id(),
            'v' => 1,
            'from_node_id' => base_shopnode::node_id('ome'),
        );

        return $query_params;
    }

    /**
     * 获取_to_node_id
     * @return mixed 返回结果
     */
    public function get_to_node_id()
    {
        return $this->_to_node_id;
    }
}