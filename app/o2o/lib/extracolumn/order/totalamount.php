<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店发货单的扩展字段订单总金额
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class o2o_extracolumn_order_totalamount extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'delivery_id';

    protected $__extra_column = 'column_order_total_amount';

    /**
     *
     * 获取发货单相关的订单总金额信息
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //根据发货单ids获取相应的备注信息
        $dlyOrderObj = app::get('ome')->model('delivery_order');
        $orders = $dlyOrderObj->db->select('select o.total_amount,'.$this->__pkey.' from  sdb_ome_delivery_order AS do LEFT JOIN sdb_ome_orders AS o ON do.order_id = o.order_id where do.delivery_id in ('.implode(',',$ids).')');

        $order_bns = array();
        foreach($orders as $order){
            if(isset($order_bns[$order[$this->__pkey]])){
                $order_bns[$order[$this->__pkey]] =+ $order['total_amount'];
            }else{
                $order_bns[$order[$this->__pkey]] = $order['total_amount'];
            }
        }

        $tmp_array= array();
        foreach($order_bns as $k=>$val){
             $tmp_array[$k] = '￥'.$val;
        }
        return $tmp_array;
    }

}