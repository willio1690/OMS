<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_mdl_delivery_log extends dbeav_model{
    /**
     * 获取DeliveryIdByLogiNO
     * @param mixed $logiNO logiNO
     * @return mixed 返回结果
     */
    public function getDeliveryIdByLogiNO($logiNO){
        $sql = 'SELECT delivery_id FROM sdb_wms_delivery_log WHERE logi_no=\'' . addslashes($logiNO) .
            '\' GROUP BY delivery_id';
        $rows = $this->db->select($sql);
        return $rows;
    }
}