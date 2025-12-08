<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_inventory{

    
    var $addon_cols = "inventory_id,status";
    var $column_edit = "操作";
    var $column_edit_width = 150;
    var $column_edit_order = 1;
    function column_edit($row){
        $finder_id = $_GET['_finder']['finder_id'];
        $inventory_id = intval($row[$this->col_prefix.'inventory_id']);
        $status = intval($row[$this->col_prefix.'status']);
        $button = array();
        $check = '<a class="lnk" href="index.php?app=o2o&ctl=admin_inventory&act=detail_inventory&p[0]='.$inventory_id.'&finder_id='.$finder_id.'" target="_blank">查看</a>';
        //全状态下都可查看
        $button[] = $check;
        if($status == 1){
           
            $confirm = '<a class="lnk" href="index.php?app=o2o&ctl=admin_inventory&act=confirm_inventory&p[0]='.$inventory_id.'&finder_id='.$finder_id.'" target="_blank">确认</a>';
            $button[] = $confirm;
            $cancel = '<a class="lnk" href="index.php?app=o2o&ctl=admin_inventory&act=doCancel&inventory_id='.$inventory_id.'&finder_id='.$finder_id.'">取消</a>';
            $button[] = $cancel;
        }
        return implode(" | ",$button);
    }
    
    //盘点操作日志
    var $detail_operate_logs = '盘点操作日志';
    function detail_operate_logs($id){
        $render = app::get('o2o')->render();
        $logObj = app::get('ome')->model('operation_log');
        $logData = $logObj->read_log(array('obj_id'=>$id, 'obj_type'=>'inventory@o2o'), 0, -1);
        foreach($logData as $k=>$v){
            $logData[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }
        $render->pagedata['datalist'] = $logData;
        return $render->fetch('admin/inventory/detail_log.html');
    }

}