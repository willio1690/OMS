<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_store_response_process_stock
{
    

    /**
     * listing
     * @param mixed $params 参数
     * @return mixed 返回值
     */
    public function listing($params){


        $rs = kernel::single('erpapi_front_response_process_o2o_material')->listing($params);


        return $rs;

    }



    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter){

        unset($filter['search']);
        $count = app::get('o2o')->model('branch_product')->count($filter);

        return array('rsp' => 'succ', 'data' => array('count' => $count));
    }
    
    
}

?>