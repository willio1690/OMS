<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_mdl_product_storagelife_history extends dbeav_model{

    //是否有导出配置
    var $has_export_cnf = true;
    var $export_name = '保质期批次历史';
    
    function modifier_act_type($row){
        if($row == '1'){
            return "出库";
        }elseif($row == '2'){
            return '退入';
        }else{
            return '未知';
        }
    }

    function modifier_bill_type($row){
        if($row == '1'){
            return "发货单";
        }elseif($row == '2'){
            return '退货单';
        }else{
            return '未知';
        }
    }
    
    function export_extra_cols(){
        return array(
                'column_ship_name' => array('label'=>'收货人','width'=>'100','func_suffix'=>'ship_name'),
                'column_ship_area' => array('label'=>'收货区域','width'=>'100','func_suffix'=>'ship_area'),
                'column_ship_addr' => array('label'=>'收货地址','width'=>'100','func_suffix'=>'ship_addr'),
                'column_ship_tel' => array('label'=>'电话','width'=>'100','func_suffix'=>'ship_tel'),
                'column_ship_mobile' => array('label'=>'手机','width'=>'100','func_suffix'=>'ship_mobile'),
        );
    }
    
    //获取扩展字段 收货人
    function export_extra_ship_name($rows){
        return kernel::single('ome_exportextracolumn_product_storagelife_history_shipname')->process($rows);
    }
    
    //获取扩展字段 收货区域
    function export_extra_ship_area($rows){
        return kernel::single('ome_exportextracolumn_product_storagelife_history_shiparea')->process($rows);
    }
    
    //获取扩展字段 收货地址
    function export_extra_ship_addr($rows){
        return kernel::single('ome_exportextracolumn_product_storagelife_history_shipaddr')->process($rows);
    }
    //获取扩展字段 电话
    function export_extra_ship_tel($rows){
        return kernel::single('ome_exportextracolumn_product_storagelife_history_shiptel')->process($rows);
    }
    //获取扩展字段 手机
    function export_extra_ship_mobile($rows){
        return kernel::single('ome_exportextracolumn_product_storagelife_history_shipmobile')->process($rows);
    }

    function searchOptions(){
        $parentOptions = parent::searchOptions();
        $childOptions = array(
            'order_bn'=>app::get('base')->_('订单号'),
        );
        return array_merge($childOptions,$parentOptions);
    }

    function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = 1;
        if(isset($filter['order_bn'])){

            $delivery_bns = $this->get_delivery_list($filter['order_bn']);
            $reship_bns = $this->get_reship_list($filter['order_bn']);
            $bill_list = array();
            $bill_list[] = 0;
            if ($delivery_bns){
                $bill_list = array_merge($bill_list,$delivery_bns);
            }
            if ($reship_bns){
                $bill_list = array_merge($bill_list,$reship_bns);
            }
            if ($bill_list){
                $where.=" AND bill_no in (".implode(',',$bill_list).")";
            }
            unset($filter['order_bn']);
        }


        return $where ." AND ".parent::_filter($filter,$tableAlias,$baseWhere);
    }

    /**
     * 获取_delivery_list
     * @param mixed $order_bn order_bn
     * @return mixed 返回结果
     */
    public function get_delivery_list($order_bn){

        static $delivery_list ;
        if ($delivery_list[$order_bn]) return $delivery_list[$order_bn];
        $order_id = $this->get_order_detail($order_bn);
        $sql = "SELECT d.delivery_bn
                FROM sdb_ome_delivery_order as deo
                LEFT JOIN sdb_ome_delivery AS d ON deo.delivery_id = d.delivery_id
                WHERE deo.order_id={$order_id}
                AND (d.parent_id=0 OR d.is_bind='true')
         
                AND d.status IN('succ')";
        $delivery = kernel::database()->select($sql);
        if ($delivery){
            $delivery_list[$order_bn] = array_map('current', $delivery);
            return $delivery_list[$order_bn];
        }
    }


    /**
     * 获取_reship_list
     * @param mixed $order_bn order_bn
     * @return mixed 返回结果
     */
    public function get_reship_list($order_bn){
        static $reship_list;
        if ($reship_list[$order_bn]) return $reship_list[$order_bn];
        $order_id = $this->get_order_detail($order_bn);

        $sql = "SELECT reship_bn FROM sdb_ome_reship WHERE order_id=".$order_id."";
        $reship = $this->db->select($sql);
        if ($reship){
            $reship_list[$order_bn] = array_map('current',$reship);
            return $reship_list[$order_bn];
        }


    }

    /**
     * 获取_order_detail
     * @param mixed $order_bn order_bn
     * @return mixed 返回结果
     */
    public function get_order_detail($order_bn){
        static $order_detail;
        if ($order_detail[$order_bn]) return $order_detail[$order_bn];

        $order_info = app::get('ome')->model('orders')->select()->columns('order_id')->where('order_bn=?',$order_bn)->instance()->fetch_row();
        if ($order_info){
            $order_detail[$order_bn] = $order_info['order_id'];
            return $order_detail[$order_bn];
        }
    }

    /**
     * 获取_ordersBydeliverybn
     * @param mixed $delivery_bn delivery_bn
     * @return mixed 返回结果
     */
    public function get_ordersBydeliverybn($delivery_bn){
        $sql = "SELECT DO.order_id FROM `sdb_ome_delivery_order` as DO  LEFT JOIN sdb_ome_delivery as d on DO.delivery_id=d.delivery_id WHERE d.delivery_bn ='".$delivery_bn."' AND d.parent_id=0";

        $delivery = $this->db->selectrow($sql);
        $order_id = $delivery['order_id'];
        return $this->get_orders($order_id);
    }

    /**
     * 获取_ordersByreshipbn
     * @param mixed $reship_bn reship_bn
     * @return mixed 返回结果
     */
    public function get_ordersByreshipbn($reship_bn){
        $sql = "SELECT o.order_id FROM sdb_ome_reship WHERE reship_bn='".$reship_bn."'";
        $reship_detail = $this->db->selectrow($sql);
        $order_id = $reship_detail['order_id'];
        return $this->get_orders($order_id);
    }

    /**
     * 获取_orders
     * @param mixed $order_id ID
     * @return mixed 返回结果
     */
    public function get_orders($order_id){
        static $orders;
        if ($orders[$order_id]) return $orders[$order_id];
        $orders_detail = $this->db->selectrow("SELECT order_bn FROM sdb_ome_orders where order_id=".$order_id);

        $orders[$order_id] = $orders_detail['order_bn'];

        return $orders[$order_id];
    }
    
}