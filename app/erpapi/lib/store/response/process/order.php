<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_order
{
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){
        
        $params['method'] = 'ome.order.add';

       
        $rs = kernel::single('erpapi_router_response')->set_node_id($params['node_id'])->set_api_name('ome.order.add')->dispatch($params);
     
        return $rs;

    }

    /**
     * refundagree
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function refundagree($params){

        $rs = kernel::single('openapi_data_original_order')->refundagree($params);
     
        return $rs;

    }

    /**
     * returnagree
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function returnagree($params){

        $rs = kernel::single('openapi_data_original_order')->returnagree($params);
        return $rs;

    }

}

?>