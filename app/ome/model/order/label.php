<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单标签
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class ome_mdl_order_label extends dbeav_model
{

// ====================================================
// == 此表已废弃 请转用sdb_ome_bill_label表 2023.09.26 ==
// == 此表已废弃 请转用sdb_ome_bill_label表 2023.09.26 ==
// == 此表已废弃 请转用sdb_ome_bill_label表 2023.09.26 ==
// ====================================================

    /**
     * 获取订单标记列表
     * 
     * @param array $orderId
     * @return mixed
     */
    public function getOrderLabelList($orderIds)
    {
        if(empty($orderIds)){
            return array();
        }
        
        $sql = "SELECT a.*, b.label_code, b.label_color FROM sdb_ome_order_label AS a LEFT JOIN sdb_omeauto_order_labels AS b ON a.label_id=b.label_id ";
        $sql .= " WHERE a.order_id IN (". implode(',', $orderIds) .")  ORDER BY a.label_id DESC";
        $labelList = $this->db->select($sql);
        
        return $labelList;
    }
}