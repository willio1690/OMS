<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 物流包裹单Finder类
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: Z
 */
class console_finder_delivery_bill
{
    var $detail_delivery = '物流包裹单列表';
    
    var $addon_cols = 'order_id,delivery_id';
    
    public $_deliveryObj = null;

    static $_orderList = null;
    
    static $_refundList = null;
    
    public $pay_status_list = null;
    
    public $refund_status_list = null;
    public $refund_refer_list = null;
    
    function __construct()
    {
        $this->_deliveryObj = app::get('ome')->model('delivery');
        
        $this->pay_status_list = array (
                0 => '未支付',
                1 => '已支付',
                2 => '处理中',
                3 => '部分付款',
                4 => '部分退款',
                5 => '全额退款',
                6 => '退款申请中',
                7 => '退款中',
                8 => '支付中',
        );
        
        $this->refund_status_list = array(
                0 => '未审核',
                1 => '审核中',
                2 => '已接受申请',
                3 => '已拒绝',
                4 => '已退款',
                5 => '退款中',
                6 => '退款失败',
        );
        
        $this->refund_refer_list = array(
                0 => '普通退款',
                1 => '售后退款',
        );
    }
    
    var $column_pay_status = '订单付款状态';
    var $column_pay_status_width = 120;
    var $column_pay_status_order = 18;
    function column_pay_status($row, $list)
    {
        if(empty(self::$_orderList)){
            $order_ids = array();
            foreach ($list as $key => $val)
            {
                $order_id = $val[$this->col_prefix.'order_id'];
                
                $order_ids[$order_id] = $order_id;
            }
            
            //关联订单
            $sql = "SELECT order_id,order_bn,pay_status,ship_status FROM sdb_ome_orders WHERE order_id IN(". implode(',', $order_ids) .")";
            $tempList = $this->_deliveryObj->db->select($sql);
            if($tempList){
                foreach ($tempList as $key => $val)
                {
                    $order_id = $val['order_id'];
                    
                    self::$_orderList[$order_id] = $val;
                }
            }
            
            //退款申请单类型
            $tempList = array();
            if($order_ids){
                $sql = "SELECT apply_id,order_id,status,refund_refer FROM sdb_ome_refund_apply WHERE order_id IN(". implode(',', $order_ids) .")";
                $tempList = $this->_deliveryObj->db->select($sql);
                if($tempList){
                    foreach ($tempList as $key => $val)
                    {
                        $order_id = $val['order_id'];
                        
                        self::$_refundList[$order_id] = $val;
                    }
                }
            }
            
            unset($tempList, $order_ids);
        }
        
        //订单信息
        $order_id = $row[$this->col_prefix.'order_id'];
        
        $orderInfo = self::$_orderList[$order_id];
        
        return $this->pay_status_list[$orderInfo['pay_status']];
    }
    
    var $column_refund_status = '订单退款状态';
    var $column_refund_status_width = 120;
    var $column_refund_status_order = 27;
    function column_refund_status($row)
    {
        $order_id = $row[$this->col_prefix.'order_id'];
        
        if(self::$_refundList[$order_id]){
            $status = self::$_refundList[$order_id]['status'];
            return $this->refund_status_list[$status];
        }
        
        return ' - ';
    }
    
    var $column_refund_type = '订退款类型';
    var $column_refund_type_width = 120;
    var $column_refund_type_order = 28;
    function column_refund_type($row)
    {
        $order_id = $row[$this->col_prefix.'order_id'];
        
        if(self::$_refundList[$order_id]){
            $refund_refer = self::$_refundList[$order_id]['refund_refer'];
            return $this->refund_refer_list[$refund_refer];
        }
        
        return ' - ';
    }
    
}
?>