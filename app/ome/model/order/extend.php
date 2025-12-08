<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_order_extend extends dbeav_model {

    /**
     * 添加RouterNum
     * @param mixed $orderId ID
     * @return mixed 返回值
     */
    public function addRouterNum($orderId) {
        $extend_detail = $this->db->selectrow("SELECT order_id FROM sdb_ome_order_extend WHERE order_id='".$orderId."'");
        if ($extend_detail){
            $sql = 'update sdb_ome_order_extend set router_num = router_num + 1 where order_id = "'.$orderId.'"';
        }else{
            $sql = "insert into sdb_ome_order_extend(order_id,router_num) VALUES('$orderId','1')";
        }
        $this->db->exec($sql);
    }

    /**
     * 更新BoolExtendStatus
     * @param mixed $orderId ID
     * @param mixed $boolStatus boolStatus
     * @return mixed 返回值
     */
    public function updateBoolExtendStatus($orderId, $boolStatus) {
        if($boolStatus > 0) {
        	$extend_detail = $this->db->selectrow("SELECT order_id FROM sdb_ome_order_extend WHERE order_id='".$orderId."'");
        	if ($extend_detail){
        		$sql = 'update sdb_ome_order_extend set bool_extendstatus = bool_extendstatus | ' . intval($boolStatus) . ' where order_id = "' . $orderId . '"';
        	}else{
        		$sql = "insert into sdb_ome_order_extend(order_id,bool_extendstatus) VALUES('$orderId','$boolStatus')";
        	}
            
            $this->db->exec($sql);
        }
    }
}