<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_refund
{
    /**
     * 添加
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function add($params){

        $params['method'] = 'ome.refund.add';
        $rs = kernel::single('erpapi_router_response')->set_node_id($params['node_id'])->set_api_name('ome.refund.add')->dispatch($params);

        return $rs;



    }

    
}

?>