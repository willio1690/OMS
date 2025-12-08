<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯品会JIT出库事件Lib类
 * 
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 1.0 vopick.php 2017-03-10
 */
class wms_event_trigger_vopoutstorage extends wms_event_trigger_stockoutabstract
{
    /**
     * 组织参数
     */
    function getStockOutData($data)
    {
        $stockout_id    = $data['iso_id'];
        
        $stockoutObj    = app::get('purchase')->model('pick_stockout_bills');
        $stockitemObj   = app::get('purchase')->model('pick_stockout_bill_items');
        $pickObj        = app::get('purchase')->model('pick_bills');
        
        //出库单
        $row            = $stockoutObj->dump(array('stockout_id'=>$stockout_id, 'confirm_status'=>2, 'o_status'=>1), '*');
        
        //出库仓
        $branchObj    = app::get('ome')->model('branch');
        $branchInfo   = $branchObj->dump(array('branch_id'=>$row['branch_id']), 'branch_bn');
        
        //出库状态
        $io_status  = 'FINISH';//默认全部出库
        $io_type    = purchase_purchase_stockout::_io_type;//出库类型
        $io_source  = 'selfwms';//来源
        
        $row['supplier_bn']  = ($row['supplier_bn'] ? $row['supplier_bn'] : '');
        $row['memo']         = ($row['memo'] ? $row['memo'] : '');
        
        $data    = array(
                'io_type'=>$io_type,
                'io_bn'=>$row['stockout_no'],
                'io_source'=>$io_source,
                'io_status'=>$io_status,
                'branch_id'=>$row['branch_id'],
                'branch_bn'=>$branchInfo['branch_bn'],
                'supplier_bn'=>$row['supplier_bn'],//供应商
                'memo'=>$row['memo'],
                'logi_no'=>$row['delivery_no'],//运单号
        );
        
        //装箱信息
        $sql     = "SELECT a.*, b.bn, b.product_name FROM sdb_purchase_pick_stockout_bill_item_boxs AS a 
                   LEFT JOIN sdb_purchase_pick_stockout_bill_items AS b ON a.stockout_item_id=b.stockout_item_id 
                   WHERE b.stockout_id=". $stockout_id;
        $boxList = $stockoutObj->db->select($sql);
        
        $temp_bn     = array();
        $temp_bn_num = array();
        $boxItem     = array();
        foreach($boxList as $key => $val)
        {
            $bn    = $val['bn'];
            $num   = $val['num'];
            
            //拣货单号和PO采购单单号
            $bill_id     = $val['bill_id'];
            $pickInfo    = $pickObj->dump(array('bill_id'=>$bill_id), 'pick_no, po_bn');
            
            //组织数据
            $boxItem[]    = array(
                        'po_bn'=>$pickInfo['po_bn'],//采购单单号
                        'pick_bn'=>$pickInfo['pick_no'],//拣货单单号
                        'box_no'=>$val['box_no'],//装箱箱号
                        'bn'=>$bn,//货品编码
                        'num'=>$num,//数量
            );
            
            $temp_bn[$bill_id][$bn]      = $bn;
            $temp_bn_num[$bill_id][$bn]  += $num;
        }
        $data['items']    = $boxItem;
        
        unset($row, $boxItem, $boxList, $sql);
        
        return $data;
    }
}
?>