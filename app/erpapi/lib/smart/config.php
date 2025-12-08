<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * smart接口配置类
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.22
 */
class erpapi_smart_config extends erpapi_config
{
    /**
     * 获取_query_params
     * @param mixed $method method
     * @param mixed $params 参数
     * @return mixed 返回结果
     */

    public function get_query_params($method, $params)
    {
        $query_params = array(
            'app_id' => 'ecos.ome',
            'method' => $method,
            'date' => date('Y-m-d H:i:s'),
            'format' => 'json',
            'v' => '1.1',
            'from_node_id' => base_shopnode::node_id('ome'),
            'to_node_id' => $this->__channelObj->smart['node_id'],
            'to_api_v' => $this->__channelObj->smart['api_version'],
            'node_type' => $this->__channelObj->smart['node_type'],
        );
        
        return $query_params;
    }
}