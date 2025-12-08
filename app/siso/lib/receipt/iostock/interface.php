<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

interface siso_receipt_iostock_interface {

    /**
     * 
     * 根据单据主键id获取出入库信息
     * @param int $id
     */
    function get_io_data($params);

}