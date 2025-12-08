<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_delivery{
    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct(&$app)
    {
        $this->app = $app;

    }

    /**
     * 添加发货单
     * @access public
     * @param int $delivery_id 发货单ID
     */
    public function delivery($delivery_id){

    }
    
    /**
     * 更改发货单状态
     * @access public
     * @param int $delivery_id 发货单ID
     * @param string $status 发货单状态
     * @param boolean $queue true：进队列  false：立即发起
     */
    public function update_status($delivery_id,$status='',$queue=false){

    }
    
    /**
     * 更改发货物流信息
     * @access public
     * @param int int $delivery_id 发货单ID
     * @param int $parent_id 合并发货单ID
     * @param boolean $queue true：进队列  false：立即发起
     */
    public function update_logistics_info($delivery_id, $parent_id='',$queue=false){

    }
    #订阅华强宝物流信息
    public function get_hqepay_logistics($delivery_id){

    }
}