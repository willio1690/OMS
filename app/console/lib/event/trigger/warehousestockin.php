<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/*
 * 转仓入库
 * 20180528 by wangjianjun
 */
class console_event_trigger_warehousestockin extends console_event_trigger_stockinabstract{

    //获取数据
    function getStockInParam($param){
        $iostockObj = kernel::single('console_iostockdata');
        $iso_id = $param['iso_id'];
        $data = $iostockObj->get_warehouse_iostockData($iso_id);
        $data['io_type'] = 'WAREHOUSE';
        return $data;
    }

}
?>