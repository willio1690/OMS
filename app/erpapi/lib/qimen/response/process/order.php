<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_qimen_response_process_order
{
    /**
     * qimen正向同步
     *
     * @param array $params 参数
     * @return array
     */
    public function add($params)
    {
        // node_id
        $node_id = $params['node_id'];
        
        // method
        $method = $params['method'];
        
        // response
        $result = kernel::single('erpapi_router_response')->set_node_id($node_id)->set_api_name($method)->dispatch($params);
        
        return $result;
    }
    
    /**
     * qimen逆向同步
     *
     * @param array $params 参数
     * @return array
     */
    public function update($params)
    {
        // node_id
        $node_id = $params['node_id'];
        
        // method
        $method = $params['method'];
        
        // response
        $result = kernel::single('erpapi_router_response')->set_node_id($node_id)->set_api_name($method)->dispatch($params);
        
        return $result;
    }
}
