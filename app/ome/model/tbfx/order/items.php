<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_tbfx_order_items extends dbeav_model{

    /**
     * 获取OrderByOrderId
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function getOrderByOrderId($data){
		$filter = array('item_id'=>$data['item_id'],'obj_id'=>$data['obj_id']);
		return $this->getList('buyer_payment',$filter);
	}

    /**
     * 获取CostitemByOrderId
     * @param mixed $order_id ID
     * @return mixed 返回结果
     */
    public function getCostitemByOrderId($order_id){
		$filter = array('order_id'=>$order_id);
		return $this->getList('SUM(buyer_payment) as cost_items',$filter);		
	}
}