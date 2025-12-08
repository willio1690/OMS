<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_service_order_edit_goods{
    
    /**
     * 获取goods的显示定义
     * @access public
     */
    public function get_view(){
        return kernel::single("ome_order_edit_goods")->get_config();
    }
    
    /**
     * 处理订单编辑时提交的数据
     * @access public
     * @param array $data 订单编辑的数据
     */
    public function process($data){
        return kernel::single("ome_order_edit_goods")->process($data);
    }
    /**
     * 判断这次提交的数据在处理完成后，是否还存在有正常的数据。
     * @param array $data 订单编辑的数据  //POST
     */
    public function is_null($data){
        return kernel::single("ome_order_edit_goods")->is_null($data);
    }
    
    /**
     * 校验订单编辑时提交的数据
     * @param array $data 订单编辑的数据  //POST
     */
    public function valid($data){
        return kernel::single("ome_order_edit_goods")->valid($data);
    }
    
    /**
     * 判断订单上商品明细是否被修改
     *
     * @param array $data 订单编辑的数据
     * @return bool
     */
    public function is_edit_product($data){
        return kernel::single("ome_order_edit_goods")->is_edit_product($data);
    }
}